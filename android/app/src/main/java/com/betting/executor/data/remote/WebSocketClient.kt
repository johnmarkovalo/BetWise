package com.betting.executor.data.remote

import com.betting.executor.data.model.ClientMessage
import com.squareup.moshi.Moshi
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.Response
import okhttp3.WebSocket
import okhttp3.WebSocketListener
import timber.log.Timber
import java.util.concurrent.TimeUnit

class WebSocketClient(
    private val serverUrl: String,
    private val authToken: String,
    private val moshi: Moshi
) {

    enum class ConnectionStatus {
        CONNECTING,
        CONNECTED,
        DISCONNECTED,
        ERROR
    }

    private val client = OkHttpClient.Builder()
        .pingInterval(30, TimeUnit.SECONDS)
        .build()

    private var webSocket: WebSocket? = null

    private val messageFlow = MutableSharedFlow<String>(extraBufferCapacity = 64)
    private val statusFlow = MutableStateFlow(ConnectionStatus.DISCONNECTED)

    private val clientMessageAdapter = moshi.adapter(ClientMessage::class.java)

    fun connect() {
        Timber.d("Connecting to WebSocket: %s", serverUrl)
        statusFlow.tryEmit(ConnectionStatus.CONNECTING)

        val request = Request.Builder()
            .url(serverUrl)
            .addHeader("Authorization", "Bearer $authToken")
            .build()

        webSocket = client.newWebSocket(request, object : WebSocketListener() {
            override fun onOpen(webSocket: WebSocket, response: Response) {
                Timber.d("WebSocket connected")
                statusFlow.tryEmit(ConnectionStatus.CONNECTED)
            }

            override fun onMessage(webSocket: WebSocket, text: String) {
                Timber.d("WebSocket message received: %s", text)
                messageFlow.tryEmit(text)
            }

            override fun onClosing(webSocket: WebSocket, code: Int, reason: String) {
                Timber.d("WebSocket closing: code=%d reason=%s", code, reason)
                webSocket.close(1000, null)
                statusFlow.tryEmit(ConnectionStatus.DISCONNECTED)
            }

            override fun onClosed(webSocket: WebSocket, code: Int, reason: String) {
                Timber.d("WebSocket closed: code=%d reason=%s", code, reason)
                statusFlow.tryEmit(ConnectionStatus.DISCONNECTED)
            }

            override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) {
                Timber.e(t, "WebSocket failure")
                statusFlow.tryEmit(ConnectionStatus.ERROR)
            }
        })
    }

    fun disconnect() {
        Timber.d("Disconnecting WebSocket")
        webSocket?.close(1000, "Client disconnect")
        webSocket = null
    }

    fun send(message: ClientMessage): Boolean {
        val json = clientMessageAdapter.toJson(message)
        Timber.d("Sending WebSocket message: %s", json)
        return webSocket?.send(json) ?: false
    }

    fun sendRaw(json: String): Boolean {
        Timber.d("Sending raw WebSocket message: %s", json)
        return webSocket?.send(json) ?: false
    }

    fun messages(): Flow<String> = messageFlow.asSharedFlow()

    fun status(): Flow<ConnectionStatus> = statusFlow.asStateFlow()
}
