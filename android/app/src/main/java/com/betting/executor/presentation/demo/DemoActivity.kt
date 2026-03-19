package com.betting.executor.presentation.demo

import android.annotation.SuppressLint
import android.content.Intent
import android.os.Bundle
import android.provider.Settings
import android.view.View
import android.webkit.JavascriptInterface
import android.webkit.WebChromeClient
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.betting.executor.R
import com.betting.executor.domain.executor.GestureExecutor
import com.betting.executor.presentation.service.BettingAccessibilityService
import com.betting.executor.util.AccessibilityUtils
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import timber.log.Timber

/**
 * Demo activity that shows a balloon pop game in a WebView and uses the
 * AccessibilityService + GestureExecutor to automatically tap balloons.
 *
 * This demonstrates the full execution pipeline:
 * 1. Game runs in WebView (simulates a betting provider website)
 * 2. Android queries balloon positions via JS bridge
 * 3. RoundExecutor-style coordinate tapping pops balloons
 * 4. Results are tracked and displayed
 *
 * No backend or WebSocket needed — everything runs locally.
 */
@AndroidEntryPoint
class DemoActivity : AppCompatActivity() {

    private lateinit var webView: WebView
    private lateinit var tvStatus: TextView
    private lateinit var tvStats: TextView
    private lateinit var tvServiceStatus: TextView

    private var autoBotJob: Job? = null
    private var isAutoBotRunning = false
    private var botPops = 0
    private var botAttempts = 0

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_demo)

        webView = findViewById(R.id.webView)
        tvStatus = findViewById(R.id.tvDemoStatus)
        tvStats = findViewById(R.id.tvDemoStats)
        tvServiceStatus = findViewById(R.id.tvDemoServiceStatus)

        // Setup WebView
        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            setSupportZoom(false)
        }
        webView.webChromeClient = WebChromeClient()
        webView.webViewClient = WebViewClient()
        webView.addJavascriptInterface(GameBridge(), "GameBridge")
        webView.loadUrl("file:///android_asset/balloon_game.html")

        // Buttons
        findViewById<View>(R.id.btnStartBot).setOnClickListener { toggleAutoBot() }
        findViewById<View>(R.id.btnSingleTap).setOnClickListener { singleBotTap() }
        findViewById<View>(R.id.btnEnableDemoService).setOnClickListener {
            startActivity(Intent(Settings.ACTION_ACCESSIBILITY_SETTINGS))
        }

        updateServiceStatus()
    }

    override fun onResume() {
        super.onResume()
        updateServiceStatus()
    }

    private fun updateServiceStatus() {
        val isRunning = AccessibilityUtils.isAccessibilityServiceEnabled(this) &&
                BettingAccessibilityService.isRunning()

        if (isRunning) {
            tvServiceStatus.text = "Accessibility Service: ACTIVE"
            tvServiceStatus.setTextColor(getColor(android.R.color.holo_green_dark))
            findViewById<View>(R.id.btnEnableDemoService).visibility = View.GONE
            findViewById<View>(R.id.btnStartBot).isEnabled = true
            findViewById<View>(R.id.btnSingleTap).isEnabled = true
        } else {
            tvServiceStatus.text = "Accessibility Service: NOT ACTIVE"
            tvServiceStatus.setTextColor(getColor(android.R.color.holo_red_dark))
            findViewById<View>(R.id.btnEnableDemoService).visibility = View.VISIBLE
            findViewById<View>(R.id.btnStartBot).isEnabled = false
            findViewById<View>(R.id.btnSingleTap).isEnabled = false
        }
    }

    private fun getGestureExecutor(): GestureExecutor? {
        return BettingAccessibilityService.getInstance()?.getGestureExecutor()
    }

    /**
     * Query the WebView for active balloon positions via JavaScript,
     * pick the best target, and tap it with GestureExecutor.
     */
    private fun singleBotTap() {
        val executor = getGestureExecutor()
        if (executor == null) {
            Toast.makeText(this, "Enable Accessibility Service first", Toast.LENGTH_SHORT).show()
            return
        }

        setBotStatus("Scanning for targets...")

        // Query balloon positions from the game via JS
        webView.evaluateJavascript("JSON.stringify(window.getActiveBalloons())") { result ->
            if (result == null || result == "null" || result == "\"\"") {
                setBotStatus("No balloons found")
                return@evaluateJavascript
            }

            try {
                // Parse balloon data
                val json = result.trim('"').replace("\\\"", "\"").replace("\\\\", "\\")
                val balloons = parseBalloons(json)

                if (balloons.isEmpty()) {
                    setBotStatus("No active balloons")
                    return@evaluateJavascript
                }

                // Pick the best target: prefer gold, then lowest on screen (about to escape)
                val target = balloons
                    .sortedWith(compareByDescending<BalloonInfo> { it.isGold }.thenByDescending { it.screenY })
                    .first()

                val targetLabel = if (target.isGold) "GOLD balloon" else "${target.color} balloon"
                setBotStatus("Target: $targetLabel at (${target.screenX.toInt()}, ${target.screenY.toInt()})")

                // Execute tap via AccessibilityService gesture injection
                lifecycleScope.launch {
                    botAttempts++
                    val tapResult = executor.performTapAt(target.screenX, target.screenY)

                    tapResult.onSuccess {
                        botPops++
                        setBotStatus("Popped $targetLabel!")
                        updateStats()
                    }.onFailure { error ->
                        setBotStatus("Tap failed: ${error.message}")
                        updateStats()
                    }
                }

            } catch (e: Exception) {
                Timber.e(e, "Failed to parse balloon data")
                setBotStatus("Parse error: ${e.message}")
            }
        }
    }

    private fun toggleAutoBot() {
        if (isAutoBotRunning) {
            stopAutoBot()
        } else {
            startAutoBot()
        }
    }

    private fun startAutoBot() {
        val executor = getGestureExecutor()
        if (executor == null) {
            Toast.makeText(this, "Enable Accessibility Service first", Toast.LENGTH_SHORT).show()
            return
        }

        isAutoBotRunning = true
        findViewById<com.google.android.material.button.MaterialButton>(R.id.btnStartBot).text = "Stop Auto-Bot"
        setBotStatus("Auto-bot started!")

        autoBotJob = lifecycleScope.launch {
            while (isActive && isAutoBotRunning) {
                // Small random delay to look natural (300-800ms)
                val thinkTime = (300L + (Math.random() * 500).toLong())
                delay(thinkTime)

                if (!isActive || !isAutoBotRunning) break

                // Query and tap
                queryAndTap(executor)

                // Wait for tap to complete and balloon to animate
                delay(200)
            }
        }
    }

    private fun stopAutoBot() {
        isAutoBotRunning = false
        autoBotJob?.cancel()
        autoBotJob = null
        findViewById<com.google.android.material.button.MaterialButton>(R.id.btnStartBot).text = "Start Auto-Bot"
        setBotStatus("Auto-bot stopped")
    }

    private suspend fun queryAndTap(executor: GestureExecutor) {
        // Use suspendCancellableCoroutine to bridge the JS callback
        val balloons = kotlinx.coroutines.suspendCancellableCoroutine<List<BalloonInfo>> { cont ->
            runOnUiThread {
                webView.evaluateJavascript("JSON.stringify(window.getActiveBalloons())") { result ->
                    try {
                        if (result == null || result == "null" || result == "\"\"") {
                            cont.resume(emptyList()) {}
                            return@evaluateJavascript
                        }
                        val json = result.trim('"').replace("\\\"", "\"").replace("\\\\", "\\")
                        cont.resume(parseBalloons(json)) {}
                    } catch (e: Exception) {
                        cont.resume(emptyList()) {}
                    }
                }
            }
        }

        if (balloons.isEmpty()) {
            runOnUiThread { setBotStatus("Scanning... no targets") }
            return
        }

        // Target selection: gold first, then lowest (most urgent)
        val target = balloons
            .sortedWith(compareByDescending<BalloonInfo> { it.isGold }.thenByDescending { it.screenY })
            .first()

        val label = if (target.isGold) "GOLD" else target.color
        runOnUiThread { setBotStatus("Tapping $label at (${target.screenX.toInt()}, ${target.screenY.toInt()})") }

        botAttempts++
        val result = executor.performTapAt(target.screenX, target.screenY)

        result.onSuccess {
            botPops++
            runOnUiThread {
                setBotStatus("Popped $label!")
                updateStats()
            }
        }.onFailure {
            runOnUiThread {
                setBotStatus("Miss!")
                updateStats()
            }
        }
    }

    private fun setBotStatus(msg: String) {
        tvStatus.text = msg
        // Also update the WebView status bar
        webView.evaluateJavascript("window.setBotStatus('$msg')", null)
    }

    private fun updateStats() {
        val accuracy = if (botAttempts > 0) (botPops * 100 / botAttempts) else 0
        tvStats.text = "Bot: $botPops pops / $botAttempts attempts ($accuracy% accuracy)"
    }

    /**
     * Simple JSON array parser for balloon data.
     *
     * The JS getBoundingClientRect() returns CSS pixels. The WebView maps these
     * to physical screen pixels by multiplying by devicePixelRatio (density).
     * We add the WebView's on-screen offset to get absolute screen coordinates
     * that GestureExecutor can tap.
     */
    private fun parseBalloons(json: String): List<BalloonInfo> {
        val balloons = mutableListOf<BalloonInfo>()
        if (!json.startsWith("[")) return balloons

        val density = resources.displayMetrics.density

        // WebView's position on screen (accounts for the control panel above it)
        val location = IntArray(2)
        webView.getLocationOnScreen(location)
        val webViewLeft = location[0]
        val webViewTop = location[1]

        val regex = Regex("""\{[^}]+}""")
        for (match in regex.findAll(json)) {
            val obj = match.value
            val id = extractInt(obj, "id") ?: continue
            val color = extractString(obj, "color") ?: "unknown"
            val isGold = obj.contains("\"isGold\":true")
            val cssX = extractFloat(obj, "\"x\"") ?: continue
            val cssY = extractFloat(obj, "\"y\"") ?: continue

            // CSS pixels → screen pixels: multiply by density, add WebView offset
            val screenX = webViewLeft + (cssX * density)
            val screenY = webViewTop + (cssY * density)

            balloons.add(BalloonInfo(id, color, isGold, screenX, screenY))
        }
        return balloons
    }

    private fun extractInt(json: String, key: String): Int? {
        val pattern = Regex(""""$key"\s*:\s*(\d+)""")
        return pattern.find(json)?.groupValues?.get(1)?.toIntOrNull()
    }

    private fun extractString(json: String, key: String): String? {
        val pattern = Regex(""""$key"\s*:\s*"([^"]+)"""")
        return pattern.find(json)?.groupValues?.get(1)
    }

    private fun extractFloat(json: String, key: String): Float? {
        val pattern = Regex("""$key\s*:\s*([0-9.]+)""")
        return pattern.find(json)?.groupValues?.get(1)?.toFloatOrNull()
    }

    data class BalloonInfo(
        val id: Int,
        val color: String,
        val isGold: Boolean,
        val screenX: Float,
        val screenY: Float
    )

    /**
     * JavaScript interface for the game to communicate back to Android.
     */
    inner class GameBridge {
        @JavascriptInterface
        fun onBalloonPopped(id: Int, score: Int, x: Float, y: Float) {
            Timber.d("Game bridge: balloon %d popped, score=%d at (%.0f,%.0f)", id, score, x, y)
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        stopAutoBot()
        webView.destroy()
    }
}
