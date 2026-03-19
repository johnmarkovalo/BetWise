package com.betting.executor.data.model

import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ServerMessage(
    val type: String,
    val payload: Map<String, Any>? = null
)

@JsonClass(generateAdapter = true)
data class RoundPreparedMessage(
    val roundId: String,
    val executeAt: Long,
    val provider: String,
    val tableId: String,
    val side: String,
    val amount: Double,
    val signature: String
)

@JsonClass(generateAdapter = true)
data class ClientMessage(
    val type: String,
    val deviceId: String,
    val payload: Map<String, Any>? = null
)
