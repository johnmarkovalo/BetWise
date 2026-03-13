package com.betting.executor.presentation.main

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import com.betting.executor.presentation.service.BettingAccessibilityService
import com.betting.executor.util.AccessibilityUtils
import timber.log.Timber
import javax.inject.Inject

@HiltViewModel
class MainViewModel @Inject constructor(
    application: Application
) : AndroidViewModel(application) {

    private val _serviceStatus = MutableStateFlow(false)
    val serviceStatus: StateFlow<Boolean> = _serviceStatus.asStateFlow()

    private val _connectionStatus = MutableStateFlow("Disconnected")
    val connectionStatus: StateFlow<String> = _connectionStatus.asStateFlow()

    private val _logs = MutableStateFlow("")
    val logs: StateFlow<String> = _logs.asStateFlow()

    private val _testButtonEnabled = MutableStateFlow(false)
    val testButtonEnabled: StateFlow<Boolean> = _testButtonEnabled.asStateFlow()

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

    private suspend fun emitLog(message: String) {
        Timber.d(message)
        _logs.emit(message)
    }
}
