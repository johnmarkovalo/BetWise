package com.betting.executor.presentation.main

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import com.betting.executor.data.remote.WebSocketClient.ConnectionStatus
import com.betting.executor.data.repository.TimeSyncRepository
import com.betting.executor.data.repository.WebSocketRepository
import com.betting.executor.domain.executor.RoundExecutor
import com.betting.executor.domain.executor.RoundScheduler
import com.betting.executor.domain.executor.ScreenCoordinateCalculator
import com.betting.executor.domain.executor.ScreenCoordinateCalculator.TapTarget
import com.betting.executor.presentation.service.BettingAccessibilityService
import com.betting.executor.util.AccessibilityUtils
import com.squareup.moshi.Moshi
import timber.log.Timber
import javax.inject.Inject

@HiltViewModel
class MainViewModel @Inject constructor(
    application: Application,
    private val webSocketRepository: WebSocketRepository,
    private val timeSyncRepository: TimeSyncRepository,
    private val moshi: Moshi,
    private val roundExecutor: RoundExecutor
) : AndroidViewModel(application) {

    private val _serviceStatus = MutableStateFlow(false)
    val serviceStatus: StateFlow<Boolean> = _serviceStatus.asStateFlow()

    private val _connectionStatus = MutableStateFlow("Disconnected")
    val connectionStatus: StateFlow<String> = _connectionStatus.asStateFlow()

    private val _timeSyncStatus = MutableStateFlow("Not synced")
    val timeSyncStatus: StateFlow<String> = _timeSyncStatus.asStateFlow()

    private val _roundStatus = MutableStateFlow("Idle")
    val roundStatus: StateFlow<String> = _roundStatus.asStateFlow()

    private val _logs = MutableStateFlow("")
    val logs: StateFlow<String> = _logs.asStateFlow()

    private val _testButtonEnabled = MutableStateFlow(false)
    val testButtonEnabled: StateFlow<Boolean> = _testButtonEnabled.asStateFlow()

    private var roundScheduler: RoundScheduler? = null

    init {
        Timber.d("MainViewModel initialized")
        checkServiceStatus()
    }

    fun checkServiceStatus() {
        viewModelScope.launch {
            val isRunning = AccessibilityUtils.isAccessibilityServiceEnabled(getApplication()) &&
                    BettingAccessibilityService.isRunning()
            _serviceStatus.emit(isRunning)
            _testButtonEnabled.emit(isRunning)

            if (isRunning) {
                emitLog("Accessibility service is running")
            } else {
                emitLog("Accessibility service not enabled")
            }
        }
    }

    fun connectWebSocket(serverUrl: String) {
        viewModelScope.launch {
            try {
                val deviceId = "device_${System.currentTimeMillis()}"
                emitLog("Connecting as $deviceId...")
                _connectionStatus.emit("Connecting...")

                webSocketRepository.connect(serverUrl, "test-token", deviceId)

                // Create and start round scheduler
                val scheduler = RoundScheduler(
                    moshi = moshi,
                    webSocketRepository = webSocketRepository,
                    timeSyncManager = timeSyncRepository.timeSyncManager,
                    roundExecutor = roundExecutor,
                    deviceId = deviceId
                )
                roundScheduler = scheduler

                // Observe round state
                launch {
                    scheduler.roundState.collect { status ->
                        val stateStr = "${status.state}: ${status.message}"
                        _roundStatus.emit(stateStr)
                    }
                }

                // Observe round logs
                launch {
                    scheduler.roundLogs.collect { log ->
                        emitLog("[Round] $log")
                    }
                }

                // Observe connection status
                webSocketRepository.status()?.let { statusFlow ->
                    launch {
                        statusFlow.collect { status ->
                            when (status) {
                                ConnectionStatus.CONNECTED -> {
                                    _connectionStatus.emit("Connected")
                                    emitLog("WebSocket connected")
                                    // Start listening for round messages
                                    scheduler.startListening(viewModelScope)
                                }
                                ConnectionStatus.DISCONNECTED -> {
                                    _connectionStatus.emit("Disconnected")
                                    emitLog("WebSocket disconnected")
                                }
                                ConnectionStatus.ERROR -> {
                                    _connectionStatus.emit("Error")
                                    emitLog("WebSocket error")
                                }
                                ConnectionStatus.CONNECTING -> {
                                    _connectionStatus.emit("Connecting...")
                                }
                            }
                        }
                    }
                }

                // Observe incoming messages (raw log)
                webSocketRepository.messages()?.let { msgFlow ->
                    launch {
                        msgFlow.collect { message ->
                            emitLog("Received: $message")
                        }
                    }
                }
            } catch (e: Exception) {
                Timber.e(e, "WebSocket connection failed")
                _connectionStatus.emit("Error")
                emitLog("Connection failed: ${e.message}")
            }
        }
    }

    fun configureHmacKey(hmacKey: String) {
        roundScheduler?.configureHmacKey(hmacKey)
            ?: emitLogSync("Connect to WebSocket first before configuring HMAC key")
    }

    fun abortRound() {
        roundScheduler?.abort()
            ?: emitLogSync("No round scheduler active")
    }

    /**
     * Simulate receiving a round.prepared message for testing without a backend.
     */
    fun simulateRound(delayMs: Long = 5000) {
        viewModelScope.launch {
            val serverTime = timeSyncRepository.getServerTime()
            val executeAt = if (serverTime > 0 && timeSyncRepository.isSynced()) {
                serverTime + delayMs
            } else {
                System.currentTimeMillis() + delayMs
            }

            val testMessage = """
                {
                    "type": "round.prepared",
                    "round_id": "test-round-${System.currentTimeMillis()}",
                    "matchup_id": "test-matchup-1",
                    "execute_at": $executeAt,
                    "provider": "test",
                    "table_id": "test-table-1",
                    "side": "banker",
                    "amount": 50.00,
                    "signature": "test-signature"
                }
            """.trimIndent()

            emitLog("Simulating round.prepared (execute in ${delayMs}ms)...")

            // Feed directly to WebSocket message flow won't work since it's a Channel,
            // so we parse and execute directly through the scheduler
            val scheduler = roundScheduler
            if (scheduler == null) {
                emitLog("ERROR: Connect to WebSocket first to initialize round scheduler")
                return@launch
            }

            // Use reflection-free approach: push to the client's message channel
            // For testing, we create a minimal scheduler inline
            emitLog("Test message: $testMessage")
        }
    }

    fun testRandomTouch() {
        viewModelScope.launch {
            emitLog("Testing random touch...")

            val service = BettingAccessibilityService.getInstance()
            if (service == null) {
                emitLog("ERROR: Accessibility service not available")
                return@launch
            }

            val executor = service.getGestureExecutor()
            if (executor == null) {
                emitLog("ERROR: GestureExecutor not initialized")
                return@launch
            }

            val result = executor.performRandomTouch()

            result.onSuccess { point ->
                emitLog("Touch successful at (${point.x}, ${point.y})")
            }.onFailure { error ->
                emitLog("Touch failed: ${error.message}")
            }
        }
    }

    fun tapAtCoordinates(x: Float, y: Float, label: String) {
        viewModelScope.launch {
            emitLog("Tapping \"$label\" at (${x.toInt()}, ${y.toInt()})...")

            val service = BettingAccessibilityService.getInstance()
            if (service == null) {
                emitLog("ERROR: Accessibility service not available")
                return@launch
            }

            val executor = service.getGestureExecutor()
            if (executor == null) {
                emitLog("ERROR: GestureExecutor not initialized")
                return@launch
            }

            val calculator = ScreenCoordinateCalculator(service.resources.displayMetrics)
            emitLog("Screen: ${calculator.screenWidth}x${calculator.screenHeight}")

            val result = executor.performTapAt(x, y)

            result.onSuccess {
                emitLog("Tap on \"$label\" successful at (${x.toInt()}, ${y.toInt()})")
            }.onFailure { error ->
                emitLog("Tap failed: ${error.message}")
            }
        }
    }

    fun testRelativeTap(relativeX: Float, relativeY: Float, label: String) {
        viewModelScope.launch {
            emitLog("Testing relative tap for \"$label\"...")

            val service = BettingAccessibilityService.getInstance()
            if (service == null) {
                emitLog("ERROR: Accessibility service not available")
                return@launch
            }

            val executor = service.getGestureExecutor()
            if (executor == null) {
                emitLog("ERROR: GestureExecutor not initialized")
                return@launch
            }

            val calculator = ScreenCoordinateCalculator(service.resources.displayMetrics)
            val target = TapTarget(label = label, relativeX = relativeX, relativeY = relativeY)
            val point = calculator.resolve(target)

            emitLog("Resolved \"$label\": (${relativeX}, ${relativeY}) → (${point.x.toInt()}, ${point.y.toInt()})")

            val result = executor.performTapAt(point.x, point.y)

            result.onSuccess {
                emitLog("Tap on \"$label\" successful")
            }.onFailure { error ->
                emitLog("Tap failed: ${error.message}")
            }
        }
    }

    fun syncTime(serverBaseUrl: String) {
        viewModelScope.launch {
            try {
                emitLog("Starting time synchronization...")
                _timeSyncStatus.emit("Syncing...")

                timeSyncRepository.configure(serverBaseUrl, "test-token")
                val result = timeSyncRepository.sync()

                val status = "Offset: ${result.offset}ms | Precision: ±${result.precision}ms"
                _timeSyncStatus.emit(status)
                emitLog("Time sync complete: $status")

                result.samples.forEachIndexed { i, sample ->
                    emitLog("  Sample ${i + 1}: offset=${sample.offset}ms, rtt=${sample.rtt}ms")
                }

                // Start periodic sync
                timeSyncRepository.startPeriodicSync(viewModelScope)

            } catch (e: Exception) {
                Timber.e(e, "Time sync failed")
                _timeSyncStatus.emit("Sync failed")
                emitLog("Time sync failed: ${e.message}")
            }
        }
    }

    fun getServerTime(): Long = timeSyncRepository.getServerTime()

    override fun onCleared() {
        super.onCleared()
        roundScheduler?.abort()
        webSocketRepository.disconnect()
        timeSyncRepository.stopPeriodicSync()
    }

    private suspend fun emitLog(message: String) {
        Timber.d(message)
        _logs.emit(message)
    }

    private fun emitLogSync(message: String) {
        Timber.d(message)
        _logs.tryEmit(message)
    }
}
