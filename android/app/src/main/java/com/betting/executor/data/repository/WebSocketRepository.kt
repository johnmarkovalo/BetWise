package com.betting.executor.data.repository

import com.betting.executor.data.model.ClientMessage
import com.betting.executor.data.model.RoundResultMessage
import com.betting.executor.data.remote.WebSocketClient
import com.betting.executor.data.remote.WebSocketClient.ConnectionStatus
import com.squareup.moshi.Moshi
import kotlinx.coroutines.flow.Flow
import timber.log.Timber
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class WebSocketRepository @Inject constructor(
    private val moshi: Moshi
) {

    private var client: WebSocketClient? = null
    private var currentDeviceId: String? = null

    private val roundResultAdapter by lazy { moshi.adapter(RoundResultMessage::class.java) }

    fun connect(serverUrl: String, authToken: String, deviceId: String) {
        disconnect()

        currentDeviceId = deviceId
        client = WebSocketClient(serverUrl, authToken, moshi).also {
            it.connect()
        }

        Timber.d("WebSocketRepository: connecting as device %s", deviceId)
    }

    fun disconnect() {
        client?.disconnect()
        client = null
        currentDeviceId = null
        Timber.d("WebSocketRepository: disconnected")
    }

    fun getDeviceId(): String? = currentDeviceId

    fun sendHeartbeat(deviceId: String): Boolean {
        val message = ClientMessage(
            type = "heartbeat",
            deviceId = deviceId,
            payload = mapOf("timestamp" to System.currentTimeMillis())
        )
        return client?.send(message) ?: false
    }

    fun sendRoundAcknowledgment(roundId: String, deviceId: String): Boolean {
        val message = ClientMessage(
            type = "round.acknowledged",
            deviceId = deviceId,
            payload = mapOf(
                "round_id" to roundId,
                "timestamp" to System.currentTimeMillis()
            )
        )
        return client?.send(message) ?: false
    }

    fun sendRoundResult(result: RoundResultMessage): Boolean {
        val json = roundResultAdapter.toJson(result)
        Timber.d("Sending round result: %s", json)
        val ws = client ?: return false
        return ws.sendRaw(json)
    }

    fun messages(): Flow<String>? = client?.messages()

    fun status(): Flow<ConnectionStatus>? = client?.status()
}
