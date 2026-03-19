package com.betting.executor.data.repository

import com.betting.executor.data.model.ClientMessage
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

    fun sendHeartbeat(deviceId: String): Boolean {
        val message = ClientMessage(
            type = "heartbeat",
            deviceId = deviceId,
            payload = mapOf("timestamp" to System.currentTimeMillis())
        )
        return client?.send(message) ?: false
    }

    fun sendAcknowledgment(roundId: String, deviceId: String): Boolean {
        val message = ClientMessage(
            type = "acknowledgment",
            deviceId = deviceId,
            payload = mapOf("roundId" to roundId)
        )
        return client?.send(message) ?: false
    }

    fun messages(): Flow<String>? = client?.messages()

    fun status(): Flow<ConnectionStatus>? = client?.status()
}
