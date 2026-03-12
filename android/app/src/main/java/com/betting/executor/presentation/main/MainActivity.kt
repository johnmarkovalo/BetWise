package com.betting.executor.presentation.main

import android.content.Intent
import android.os.Bundle
import android.provider.Settings
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

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setupUI()
        observeViewModel()

        addLog("MainActivity created")
    }

    private fun setupUI() {
        binding.btnEnableService.setOnClickListener {
            openAccessibilitySettings()
        }

        binding.btnTestTouch.setOnClickListener {
            viewModel.testRandomTouch()
            addLog("Test touch button clicked")
        }
    }

    private fun observeViewModel() {
        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                // Observe service status
                launch {
                    viewModel.serviceStatus.collect { isRunning ->
                        updateServiceStatus(isRunning)
                    }
                }

                // Observe connection status
                launch {
                    viewModel.connectionStatus.collect { status ->
                        updateConnectionStatus(status)
                    }
                }

                // Observe logs
                launch {
                    viewModel.logs.collect { log ->
                        if (log.isNotEmpty()) {
                            addLog(log)
                        }
                    }
                }

                // Observe test button state
                launch {
                    viewModel.testButtonEnabled.collect { enabled ->
                        binding.btnTestTouch.isEnabled = enabled
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

        // Auto-scroll to bottom
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
