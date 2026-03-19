package com.betting.executor.presentation.main

import android.content.Intent
import android.os.Bundle
import android.provider.Settings
import com.betting.executor.presentation.demo.DemoActivity
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import com.betting.executor.databinding.ActivityMainBinding
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch
import timber.log.Timber

@AndroidEntryPoint
class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private val viewModel: MainViewModel by viewModels()
    private var tapCount = 0

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setupUI()
        observeViewModel()

        addLog("MainActivity created")
    }

    private fun setupUI() {
        binding.btnLaunchDemo.setOnClickListener {
            startActivity(Intent(this, DemoActivity::class.java))
        }

        binding.btnEnableService.setOnClickListener {
            openAccessibilitySettings()
        }

        binding.btnConnect.setOnClickListener {
            val serverUrl = binding.etServerUrl.text.toString()
            if (serverUrl.isNotBlank()) {
                viewModel.connectWebSocket(serverUrl)
                addLog("Connecting to $serverUrl")
            }
        }

        binding.btnSyncTime.setOnClickListener {
            val baseUrl = binding.etTimeSyncUrl.text.toString()
            if (baseUrl.isNotBlank()) {
                viewModel.syncTime(baseUrl)
                addLog("Time sync requested: $baseUrl")
            }
        }

        binding.btnTestTouch.setOnClickListener {
            viewModel.testRandomTouch()
            addLog("Test touch button clicked")
        }

        binding.btnTestFindAndTap.setOnClickListener {
            // Get Target Button 1's actual screen position and tap its center
            val location = IntArray(2)
            binding.btnTarget1.getLocationOnScreen(location)
            val centerX = location[0] + binding.btnTarget1.width / 2f
            val centerY = location[1] + binding.btnTarget1.height / 2f
            val isVisible = binding.btnTarget1.isShown
            addLog("Target1 pos=(${location[0]},${location[1]}) size=${binding.btnTarget1.width}x${binding.btnTarget1.height} visible=$isVisible")
            addLog("Tapping center at (${centerX.toInt()}, ${centerY.toInt()})")
            viewModel.tapAtCoordinates(centerX, centerY, "Target Button 1")
        }

        // Target buttons — these are tap targets for the coordinate test
        binding.btnTarget1.setOnClickListener {
            tapCount++
            binding.tvLastTapped.text = "Last tapped: Target Button 1"
            updateTapCount()
            addLog("Target Button 1 tapped by gesture")
        }

        binding.btnTarget2.setOnClickListener {
            tapCount++
            binding.tvLastTapped.text = "Last tapped: Target Button 2"
            updateTapCount()
            addLog("Target Button 2 tapped by gesture")
        }

        binding.btnTarget3.setOnClickListener {
            tapCount++
            binding.tvLastTapped.text = "Last tapped: Target Button 3"
            updateTapCount()
            addLog("Target Button 3 tapped by gesture")
        }

        binding.btnResetCounter.setOnClickListener {
            tapCount = 0
            binding.tvLastTapped.text = "Last tapped: None"
            updateTapCount()
            addLog("Tap counter reset")
        }

        binding.btnAbortRound.setOnClickListener {
            viewModel.abortRound()
            addLog("Abort round requested")
        }
    }

    private fun updateTapCount() {
        binding.tvTapCount.text = "Tap Count: $tapCount"
    }

    private fun observeViewModel() {
        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch {
                    viewModel.serviceStatus.collect { isRunning ->
                        updateServiceStatus(isRunning)
                    }
                }

                launch {
                    viewModel.connectionStatus.collect { status ->
                        updateConnectionStatus(status)
                    }
                }

                launch {
                    viewModel.timeSyncStatus.collect { status ->
                        updateTimeSyncStatus(status)
                    }
                }

                launch {
                    viewModel.roundStatus.collect { status ->
                        updateRoundStatus(status)
                    }
                }

                launch {
                    viewModel.logs.collect { log ->
                        if (log.isNotEmpty()) {
                            addLog(log)
                        }
                    }
                }

                launch {
                    viewModel.testButtonEnabled.collect { enabled ->
                        binding.btnTestTouch.isEnabled = enabled
                        binding.btnTestFindAndTap.isEnabled = enabled
                    }
                }
            }
        }
    }

    private fun updateServiceStatus(isRunning: Boolean) {
        binding.tvServiceStatus.text = if (isRunning) "Running" else "Not Running"
        binding.tvServiceStatus.setTextColor(
            if (isRunning)
                getColor(android.R.color.holo_green_dark)
            else
                getColor(android.R.color.holo_red_dark)
        )
        binding.btnEnableService.text = if (isRunning) "Service Enabled" else "Enable Service"
        binding.btnEnableService.isEnabled = !isRunning
    }

    private fun updateTimeSyncStatus(status: String) {
        binding.tvTimeSyncStatus.text = status
        val color = when {
            status.startsWith("Offset:") -> getColor(android.R.color.holo_green_dark)
            status == "Syncing..." -> getColor(android.R.color.holo_orange_dark)
            status == "Sync failed" -> getColor(android.R.color.holo_red_dark)
            else -> getColor(android.R.color.darker_gray)
        }
        binding.tvTimeSyncStatus.setTextColor(color)

        // Update server time display
        if (status.startsWith("Offset:")) {
            val serverTime = viewModel.getServerTime()
            val formatted = java.text.SimpleDateFormat("HH:mm:ss.SSS", java.util.Locale.getDefault())
                .format(java.util.Date(serverTime))
            binding.tvServerTime.text = formatted
        }
    }

    private fun updateRoundStatus(status: String) {
        binding.tvRoundStatus.text = status
        val color = when {
            status.startsWith("COMPLETED") -> getColor(android.R.color.holo_green_dark)
            status.startsWith("EXECUTING") -> getColor(android.R.color.holo_blue_dark)
            status.startsWith("WAITING") || status.startsWith("ACKNOWLEDGED") -> getColor(android.R.color.holo_orange_dark)
            status.startsWith("FAILED") || status.startsWith("ABORTED") -> getColor(android.R.color.holo_red_dark)
            else -> getColor(android.R.color.darker_gray)
        }
        binding.tvRoundStatus.setTextColor(color)
    }

    private fun updateConnectionStatus(status: String) {
        binding.tvConnectionStatus.text = status
        binding.tvConnectionStatus.setTextColor(
            when (status) {
                "Connected" -> getColor(android.R.color.holo_green_dark)
                "Connecting..." -> getColor(android.R.color.holo_orange_dark)
                else -> getColor(android.R.color.darker_gray)
            }
        )
    }

    private fun addLog(message: String) {
        val timestamp = java.text.SimpleDateFormat("HH:mm:ss", java.util.Locale.getDefault())
            .format(java.util.Date())
        binding.tvLogs.append("[$timestamp] $message\n")

        binding.tvLogs.post {
            binding.scrollLogs.fullScroll(android.view.View.FOCUS_DOWN)
        }
    }

    private fun openAccessibilitySettings() {
        startActivity(Intent(Settings.ACTION_ACCESSIBILITY_SETTINGS))
        addLog("Opened accessibility settings")
    }

    override fun onResume() {
        super.onResume()
        viewModel.checkServiceStatus()
    }
}
