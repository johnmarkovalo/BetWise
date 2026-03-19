package com.betting.executor.data.model

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ServerMessage(
    val type: String,
    val payload: Map<String, Any>? = null
)

@JsonClass(generateAdapter = true)
data class RoundPreparedMessage(
    @Json(name = "round_id") val roundId: String,
    @Json(name = "matchup_id") val matchupId: String = "",
    @Json(name = "execute_at") val executeAt: Long,
    val provider: String,
    @Json(name = "table_id") val tableId: String = "",
    val side: String,
    val amount: Double,
    val signature: String
)

@JsonClass(generateAdapter = true)
data class RoundResultMessage(
    val type: String = "round.result",
    @Json(name = "round_id") val roundId: String,
    @Json(name = "device_id") val deviceId: String,
    @Json(name = "bet_placed") val betPlaced: Boolean,
    @Json(name = "bet_confirmed") val betConfirmed: Boolean,
    val outcome: String,
    val payout: Double,
    @Json(name = "execution_time_ms") val executionTimeMs: Long,
    @Json(name = "time_drift_ms") val timeDriftMs: Long
)

@JsonClass(generateAdapter = true)
data class ClientMessage(
    val type: String,
    @Json(name = "device_id") val deviceId: String,
    val payload: Map<String, Any>? = null
)
