package com.betting.executor.domain.executor

import com.betting.executor.data.model.RoundPreparedMessage
import com.betting.executor.data.model.RoundResultMessage
import com.betting.executor.data.model.ServerMessage
import com.betting.executor.data.repository.WebSocketRepository
import com.squareup.moshi.Moshi
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import timber.log.Timber

/**
 * Receives round.prepared messages from WebSocket, validates signatures,
 * sends acknowledgments, and delegates execution to RoundExecutor.
 */
class RoundScheduler(
    private val moshi: Moshi,
    private val webSocketRepository: WebSocketRepository,
    private val timeSyncManager: TimeSyncManager,
    private val roundExecutor: RoundExecutor,
    private val deviceId: String
) {

    enum class RoundState {
        IDLE,
        RECEIVED,
        ACKNOWLEDGED,
        WAITING,
        EXECUTING,
        COMPLETED,
        FAILED,
        ABORTED
    }

    data class RoundStatus(
        val state: RoundState,
        val roundId: String? = null,
        val message: String = ""
    )

    private val _roundState = MutableStateFlow(RoundStatus(RoundState.IDLE))
    val roundState: StateFlow<RoundStatus> = _roundState.asStateFlow()

    private val _roundLogs = MutableSharedFlow<String>(extraBufferCapacity = 64)
    val roundLogs: SharedFlow<String> = _roundLogs.asSharedFlow()

    private var signatureVerifier: SignatureVerifier? = null
    private var currentRoundJob: Job? = null
    private var currentRound: RoundPreparedMessage? = null

    private val serverMessageAdapter = moshi.adapter(ServerMessage::class.java)
    private val roundPreparedAdapter = moshi.adapter(RoundPreparedMessage::class.java)

    fun configureHmacKey(hmacKey: String) {
        signatureVerifier = SignatureVerifier(hmacKey)
        log("HMAC key configured")
    }

    /**
     * Start listening for incoming WebSocket messages and processing round commands.
     */
    fun startListening(scope: CoroutineScope) {
        scope.launch {
            webSocketRepository.messages()?.collect { rawMessage ->
                handleMessage(rawMessage, scope)
            }
        }
        log("Round scheduler listening for messages")
    }

    private suspend fun handleMessage(rawMessage: String, scope: CoroutineScope) {
        try {
            val serverMessage = serverMessageAdapter.fromJson(rawMessage)
            if (serverMessage == null) {
                log("Failed to parse server message")
                return
            }

            when (serverMessage.type) {
                "round.prepared" -> handleRoundPrepared(rawMessage, scope)
                "round.abort" -> handleRoundAbort(serverMessage)
                else -> Timber.d("Unhandled message type: %s", serverMessage.type)
            }
        } catch (e: Exception) {
            Timber.e(e, "Error handling message: %s", rawMessage)
            log("Error processing message: ${e.message}")
        }
    }

    private suspend fun handleRoundPrepared(rawMessage: String, scope: CoroutineScope) {
        val roundData = roundPreparedAdapter.fromJson(rawMessage)
        if (roundData == null) {
            log("Failed to parse round.prepared message")
            return
        }

        log("Received round.prepared: round=${roundData.roundId}, side=${roundData.side}, amount=${roundData.amount}")
        _roundState.emit(RoundStatus(RoundState.RECEIVED, roundData.roundId, "Round received"))

        // Verify signature if HMAC key is configured
        val verifier = signatureVerifier
        if (verifier != null) {
            // Build signable content: message without the signature field
            val signableData = buildSignableData(roundData)
            if (!verifier.verify(signableData, roundData.signature)) {
                log("REJECTED: Invalid signature for round ${roundData.roundId}")
                _roundState.emit(RoundStatus(RoundState.FAILED, roundData.roundId, "Invalid signature"))
                return
            }
            log("Signature verified for round ${roundData.roundId}")
        } else {
            log("WARNING: No HMAC key configured, skipping signature verification")
        }

        // Send acknowledgment immediately
        val acked = webSocketRepository.sendRoundAcknowledgment(roundData.roundId, deviceId)
        if (acked) {
            log("Acknowledgment sent for round ${roundData.roundId}")
            _roundState.emit(RoundStatus(RoundState.ACKNOWLEDGED, roundData.roundId, "Acknowledged"))
        } else {
            log("Failed to send acknowledgment for round ${roundData.roundId}")
        }

        // Cancel any existing round execution
        currentRoundJob?.cancel()
        currentRound = roundData

        // Schedule execution
        currentRoundJob = scope.launch {
            executeRound(roundData)
        }
    }

    private suspend fun handleRoundAbort(serverMessage: ServerMessage) {
        val roundId = serverMessage.payload?.get("round_id")?.toString()
        log("Round abort received: round=$roundId")

        if (roundId != null && currentRound?.roundId == roundId) {
            currentRoundJob?.cancel()
            currentRound = null
            _roundState.emit(RoundStatus(RoundState.ABORTED, roundId, "Round aborted by server"))
            log("Round $roundId aborted")
        }
    }

    private suspend fun executeRound(roundData: RoundPreparedMessage) {
        val serverTime = timeSyncManager.getServerTime()
        val delay = roundData.executeAt - serverTime

        log("Scheduling execution: executeAt=${roundData.executeAt}, serverNow=$serverTime, delay=${delay}ms")

        if (delay < -5000) {
            log("Round ${roundData.roundId} expired (${-delay}ms late), skipping")
            _roundState.emit(RoundStatus(RoundState.FAILED, roundData.roundId, "Round expired"))
            reportResult(roundData, betPlaced = false, outcome = "expired", executionTimeMs = 0, timeDriftMs = delay)
            return
        }

        // Pre-round time sync if needed
        if (timeSyncManager.shouldPreRoundSync(roundData.executeAt)) {
            log("Pre-round time sync triggered")
            try {
                timeSyncManager.synchronize()
                log("Pre-round time sync complete, offset=${timeSyncManager.getOffset()}ms")
            } catch (e: Exception) {
                log("Pre-round sync failed (using existing offset): ${e.message}")
            }
        }

        _roundState.emit(RoundStatus(RoundState.WAITING, roundData.roundId, "Waiting for execute_at"))

        // Wait until execution time
        val recalculatedDelay = roundData.executeAt - timeSyncManager.getServerTime()
        if (recalculatedDelay > 0) {
            log("Waiting ${recalculatedDelay}ms until execution...")
            kotlinx.coroutines.delay(recalculatedDelay)
        }

        // Execute
        _roundState.emit(RoundStatus(RoundState.EXECUTING, roundData.roundId, "Executing bet"))
        val executionStart = System.currentTimeMillis()
        val actualServerTime = timeSyncManager.getServerTime()
        val timeDrift = actualServerTime - roundData.executeAt

        log("Executing bet: side=${roundData.side}, amount=${roundData.amount}, drift=${timeDrift}ms")

        val result = roundExecutor.execute(
            provider = roundData.provider,
            side = roundData.side,
            amount = roundData.amount
        )

        val executionTimeMs = System.currentTimeMillis() - executionStart

        result.onSuccess { outcome ->
            log("Bet executed successfully: outcome=$outcome, executionTime=${executionTimeMs}ms, drift=${timeDrift}ms")
            _roundState.emit(RoundStatus(RoundState.COMPLETED, roundData.roundId, "Completed: $outcome"))
            reportResult(
                roundData = roundData,
                betPlaced = true,
                outcome = outcome.name.lowercase(),
                executionTimeMs = executionTimeMs,
                timeDriftMs = timeDrift
            )
        }.onFailure { error ->
            log("Bet execution failed: ${error.message}")
            _roundState.emit(RoundStatus(RoundState.FAILED, roundData.roundId, "Failed: ${error.message}"))
            reportResult(
                roundData = roundData,
                betPlaced = false,
                outcome = "error",
                executionTimeMs = executionTimeMs,
                timeDriftMs = timeDrift
            )
        }

        currentRound = null
    }

    private fun reportResult(
        roundData: RoundPreparedMessage,
        betPlaced: Boolean,
        outcome: String,
        executionTimeMs: Long,
        timeDriftMs: Long
    ) {
        val result = RoundResultMessage(
            roundId = roundData.roundId,
            deviceId = deviceId,
            betPlaced = betPlaced,
            betConfirmed = betPlaced,
            outcome = outcome,
            payout = 0.0, // Payout detected by provider adapter in later stages
            executionTimeMs = executionTimeMs,
            timeDriftMs = timeDriftMs
        )

        val sent = webSocketRepository.sendRoundResult(result)
        if (sent) {
            log("Result reported for round ${roundData.roundId}")
        } else {
            log("Failed to report result for round ${roundData.roundId}")
        }
    }

    /**
     * Build the signable data from a RoundPreparedMessage.
     * Concatenates fields in a deterministic order for HMAC verification.
     */
    private fun buildSignableData(msg: RoundPreparedMessage): String {
        return "${msg.roundId}:${msg.matchupId}:${msg.executeAt}:${msg.provider}:${msg.tableId}:${msg.side}:${msg.amount}"
    }

    fun abort() {
        currentRoundJob?.cancel()
        currentRound = null
        _roundState.tryEmit(RoundStatus(RoundState.ABORTED, message = "Aborted by user"))
        log("Round execution aborted by user")
    }

    private fun log(message: String) {
        Timber.d(message)
        _roundLogs.tryEmit(message)
    }
}
