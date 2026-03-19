package com.betting.executor.data.remote

import com.betting.executor.data.model.ClientMessage
import com.squareup.moshi.Moshi
import kotlinx.coroutines.channels.Channel
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.receiveAsFlow
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

    private val messageChannel = Channel<String>(Channel.UNLIMITED)
    private val statusChannel = Channel<ConnectionStatus>(Channel.CONFLATED)

    private val clientMessageAdapter = moshi.adapter(ClientMessage::class.java)

    fun connect() {
        Timber.d("Connecting to WebSocket: %s", serverUrl)
        statusChannel.trySend(ConnectionStatus.CONNECTING)

        val request = Request.Builder()
            .url(serverUrl)
            .addHeader("Authorization", "Bearer $authToken")
            .build()

        webSocket = client.newWebSocket(request, object : WebSocketListener() {
            override fun onOpen(webSocket: WebSocket, response: Response) {
                Timber.d("WebSocket connected")
                statusChannel.trySend(ConnectionStatus.CONNECTED)
            }

            override fun onMessage(webSocket: WebSocket, text: String) {
                Timber.d("WebSocket message received: %s", text)
                messageChannel.trySend(text)
            }

            override fun onClosing(webSocket: WebSocket, code: Int, reason: String) {
                Timber.d("WebSocket closing: code=%d reason=%s", code, reason)
                webSocket.close(1000, null)
                statusChannel.trySend(ConnectionStatus.DISCONNECTED)
            }

            override fun onClosed(webSocket: WebSocket, code: Int, reason: String) {
                Timber.d("WebSocket closed: code=%d reason=%s", code, reason)
                statusChannel.trySend(ConnectionStatus.DISCONNECTED)
            }

            override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) {
                Timber.e(t, "WebSocket failure")
                statusChannel.trySend(ConnectionStatus.ERROR)
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

    fun messages(): Flow<String> = messageChannel.receiveAsFlow()

    fun status(): Flow<ConnectionStatus> = statusChannel.receiveAsFlow()
}
