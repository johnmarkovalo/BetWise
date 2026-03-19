package com.betting.executor.data.repository

import com.betting.executor.domain.executor.TimeSyncManager
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import timber.log.Timber
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class TimeSyncRepository @Inject constructor(
    val timeSyncManager: TimeSyncManager
) {

    private var periodicSyncJob: Job? = null

    fun configure(serverBaseUrl: String, authToken: String) {
        timeSyncManager.configure(serverBaseUrl, authToken)
    }

    suspend fun sync(): TimeSyncManager.TimeSyncResult {
        return timeSyncManager.synchronize()
    }

    fun getServerTime(): Long = timeSyncManager.getServerTime()

    fun getOffset(): Long = timeSyncManager.getOffset()

    fun isSynced(): Boolean = timeSyncManager.lastSync != null

    fun startPeriodicSync(scope: CoroutineScope) {
        stopPeriodicSync()

        periodicSyncJob = scope.launch(Dispatchers.IO) {
            while (isActive) {
                delay(5 * 60 * 1000L) // 5 minutes
                if (timeSyncManager.shouldSync()) {
                    try {
                        Timber.d("Periodic time sync triggered")
                        timeSyncManager.synchronize()
                    } catch (e: Exception) {
                        Timber.e(e, "Periodic time sync failed")
                    }
                }
            }
        }

        Timber.d("Periodic time sync started (every 5 minutes)")
    }

    fun stopPeriodicSync() {
        periodicSyncJob?.cancel()
        periodicSyncJob = null
    }
}
