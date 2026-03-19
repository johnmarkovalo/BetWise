package com.betting.executor.domain.executor

import com.betting.executor.data.remote.TimeSyncApi
import kotlinx.coroutines.delay
import timber.log.Timber
import java.time.Duration
import java.time.Instant

class TimeSyncManager(
    private val timeSyncApi: TimeSyncApi
) {

    data class Sample(
        val offset: Long,
        val rtt: Long
    )

    data class TimeSyncResult(
        val offset: Long,
        val precision: Long,
        val samples: List<Sample>,
        val syncedAt: Instant
    )

    @Volatile
    private var offset: Long = 0

    @Volatile
    var lastSync: Instant? = null
        private set

    @Volatile
    var lastResult: TimeSyncResult? = null
        private set

    private var baseUrl: String = ""
    private var authToken: String = ""

    fun configure(baseUrl: String, authToken: String) {
        this.baseUrl = baseUrl
        this.authToken = authToken
    }

    suspend fun synchronize(): TimeSyncResult {
        require(baseUrl.isNotBlank()) { "TimeSyncManager not configured. Call configure() first." }

        Timber.d("Starting time synchronization (5 samples)...")

        val samples = mutableListOf<Sample>()
        repeat(5) { i ->
            val sample = measureOffset()
            samples.add(sample)
            Timber.d("Sample %d: offset=%dms, rtt=%dms", i + 1, sample.offset, sample.rtt)

            // Small delay between samples to avoid burst
            if (i < 4) delay(100)
        }

        // Use median offset to filter outliers
        val sortedOffsets = samples.map { it.offset }.sorted()
        offset = sortedOffsets[sortedOffsets.size / 2]

        // Precision estimate: half of minimum RTT
        val precision = samples.minOf { it.rtt } / 2

        lastSync = Instant.now()

        val result = TimeSyncResult(
            offset = offset,
            precision = precision,
            samples = samples,
            syncedAt = lastSync!!
        )
        lastResult = result

        Timber.d("Time sync complete: offset=%dms, precision=±%dms", offset, precision)

        return result
    }

    private suspend fun measureOffset(): Sample {
        val t0 = System.currentTimeMillis()
        val response = timeSyncApi.getServerTime(baseUrl, authToken)
        val t1 = System.currentTimeMillis()

        val rtt = t1 - t0
        // Server time corresponds to midpoint of request
        val sampleOffset = response.server_time - (t0 + rtt / 2)

        return Sample(offset = sampleOffset, rtt = rtt)
    }

    fun getServerTime(): Long {
        return System.currentTimeMillis() + offset
    }

    fun getOffset(): Long = offset

    fun shouldSync(): Boolean {
        val lastSyncTime = lastSync ?: return true
        val elapsed = Duration.between(lastSyncTime, Instant.now())
        // Sync every 5 minutes
        return elapsed.toMinutes() >= 5
    }

    fun shouldPreRoundSync(executeAtMs: Long): Boolean {
        val serverNow = getServerTime()
        val timeUntilExecution = executeAtMs - serverNow
        // Sync if round execution < 10 seconds away and last sync > 1 minute ago
        if (timeUntilExecution in 0..10_000) {
            val lastSyncTime = lastSync ?: return true
            val elapsed = Duration.between(lastSyncTime, Instant.now())
            return elapsed.toSeconds() >= 60
        }
        return false
    }
}
