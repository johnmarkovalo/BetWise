package com.betting.executor.data.remote

import com.squareup.moshi.JsonClass
import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.Request
import timber.log.Timber
import java.io.IOException

class TimeSyncApi(
    private val httpClient: OkHttpClient,
    private val moshi: Moshi
) {

    @JsonClass(generateAdapter = true)
    data class TimeSyncResponse(
        val server_time: Long,
        val precision_ms: Long = 0
    )

    suspend fun getServerTime(baseUrl: String, authToken: String): TimeSyncResponse {
        return withContext(Dispatchers.IO) {
            val request = Request.Builder()
                .url("$baseUrl/time/sync")
                .addHeader("Authorization", "Bearer $authToken")
                .get()
                .build()

            val response = httpClient.newCall(request).execute()

            if (!response.isSuccessful) {
                throw IOException("Time sync request failed: ${response.code}")
            }

            val body = response.body?.string()
                ?: throw IOException("Empty response from time sync endpoint")

            Timber.d("Time sync response: %s", body)

            val adapter = moshi.adapter(TimeSyncResponse::class.java)
            adapter.fromJson(body)
                ?: throw IOException("Failed to parse time sync response")
        }
    }
}
