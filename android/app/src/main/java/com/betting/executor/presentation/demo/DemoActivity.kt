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
 * 3. GestureExecutor injects real touch events to pop balloons
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
    private var gameReady = false

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
            useWideViewPort = false
            loadWithOverviewMode = false
        }
        webView.webChromeClient = WebChromeClient()
        webView.webViewClient = object : WebViewClient() {
            override fun onPageFinished(view: WebView?, url: String?) {
                super.onPageFinished(view, url)
                gameReady = true
                Timber.d("Game page loaded, WebView size: %dx%d, scale: %.2f",
                    webView.width, webView.height, webView.scale)
                setBotStatus("Game loaded. Ready!")
            }
        }
        webView.addJavascriptInterface(GameBridge(), "GameBridge")
        webView.loadUrl("file:///android_asset/balloon_game.html")

        // Buttons
        findViewById<View>(R.id.btnStartBot).setOnClickListener { toggleAutoBot() }
        findViewById<View>(R.id.btnSingleTap).setOnClickListener { singleBotTap() }
        findViewById<View>(R.id.btnDebugCoords).setOnClickListener { debugCoordinates() }
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
            tvServiceStatus.text = "Accessibility Service: NOT ACTIVE — enable to use bot"
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
     * Debug: log coordinate mapping info so we can verify the math.
     */
    private fun debugCoordinates() {
        val location = IntArray(2)
        webView.getLocationOnScreen(location)
        val density = resources.displayMetrics.density
        val scale = webView.scale

        val info = "WebView: pos=(${location[0]},${location[1]}) " +
                "size=${webView.width}x${webView.height} " +
                "density=$density scale=$scale"
        Timber.d(info)
        setBotStatus(info)

        // Query a balloon to see raw JS values + coordinate info
        webView.evaluateJavascript(
            """(function() {
                try {
                    var hasFn = typeof window.getActiveBalloons === 'function';
                    if (!hasFn) return JSON.stringify({error: 'getActiveBalloons not defined'});
                    var b = window.getActiveBalloons();
                    if (b.length > 0) {
                        return JSON.stringify({
                            count: b.length,
                            first: b[0],
                            dpr: window.devicePixelRatio,
                            vw: window.innerWidth,
                            vh: window.innerHeight
                        });
                    }
                    return JSON.stringify({count: 0, dpr: window.devicePixelRatio});
                } catch(e) {
                    return JSON.stringify({error: e.message});
                }
            })()"""
        ) { result ->
            Timber.d("Debug JS result: %s", result)
            val clean = unescapeJsResult(result ?: "null")
            tvStats.text = "JS: $clean"
        }
    }

    /**
     * Query the WebView for active balloon positions via JavaScript,
     * pick the best target, and tap it with GestureExecutor.
     */
    private fun singleBotTap() {
        if (!gameReady) {
            setBotStatus("Game still loading...")
            return
        }

        val executor = getGestureExecutor()
        if (executor == null) {
            Toast.makeText(this, "Enable Accessibility Service first", Toast.LENGTH_SHORT).show()
            return
        }

        setBotStatus("Scanning for targets...")

        queryBalloonsFromJs { balloons ->
            if (balloons.isEmpty()) {
                setBotStatus("No active balloons")
                return@queryBalloonsFromJs
            }

            // Pick the best target: prefer gold, then lowest on screen (about to escape)
            val target = balloons
                .sortedWith(compareByDescending<BalloonInfo> { it.isGold }.thenByDescending { it.screenY })
                .first()

            val targetLabel = if (target.isGold) "GOLD balloon" else "${target.color} balloon"
            setBotStatus("Target: $targetLabel at (${target.screenX.toInt()}, ${target.screenY.toInt()})")

            lifecycleScope.launch {
                botAttempts++
                updateStats()

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
        if (!gameReady) {
            Toast.makeText(this, "Wait for game to load", Toast.LENGTH_SHORT).show()
            return
        }

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

                queryAndTap(executor)

                // Wait for gesture to complete
                delay(150)
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
        val balloons = queryBalloonsFromJsSuspend()

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

    /**
     * Query balloon positions from the JS game.
     * The JS returns CSS pixel coordinates via getBoundingClientRect().
     * We need to convert these to absolute screen coordinates for GestureExecutor.
     *
     * Coordinate mapping:
     * - JS getBoundingClientRect() returns positions relative to the WebView viewport in CSS pixels
     * - WebView internally scales CSS pixels to Android view pixels by its current scale factor
     * - We then offset by the WebView's position on screen
     *
     * screenX = webViewLeft + (cssX * webView.scale)
     * screenY = webViewTop + (cssY * webView.scale)
     */
    private fun queryBalloonsFromJs(callback: (List<BalloonInfo>) -> Unit) {
        // Use an IIFE to safely check if the function exists
        val js = """(function() {
            try {
                if (typeof window.getActiveBalloons !== 'function') return '[]';
                var arr = window.getActiveBalloons();
                return JSON.stringify(arr || []);
            } catch(e) {
                return '{"error":"' + e.message + '"}';
            }
        })()"""
        webView.evaluateJavascript(js) { result ->
            try {
                Timber.d("evaluateJavascript raw result: %s", result)

                if (result == null || result == "null" || result == "\"\"" || result == "\"[]\"") {
                    callback(emptyList())
                    return@evaluateJavascript
                }

                // evaluateJavascript returns a JSON-encoded string.
                // For JSON.stringify output, the result is a JSON string containing JSON,
                // e.g.: "[{\"id\":0,\"color\":\"red\",...}]"
                // We need to unwrap the outer JSON string encoding.
                val json = unescapeJsResult(result)
                Timber.d("Unescaped balloon JSON: %s", json)

                if (json.isEmpty() || json == "[]") {
                    callback(emptyList())
                    return@evaluateJavascript
                }

                val balloons = parseBalloons(json)
                Timber.d("Parsed %d balloons", balloons.size)
                callback(balloons)
            } catch (e: Exception) {
                Timber.e(e, "Failed to parse balloon data from: %s", result)
                callback(emptyList())
            }
        }
    }

    /**
     * Unescape the result from WebView.evaluateJavascript().
     * The result is a JSON-encoded value. If the JS returned a string (from JSON.stringify),
     * the result looks like: "[{\"id\":0,...}]" — a JSON string literal.
     * We strip the outer quotes and unescape the contents.
     */
    private fun unescapeJsResult(raw: String): String {
        var s = raw
        // Strip outer double quotes if present
        if (s.startsWith("\"") && s.endsWith("\"")) {
            s = s.substring(1, s.length - 1)
        }
        // Unescape JSON string escapes
        s = s.replace("\\\"", "\"")
            .replace("\\\\", "\\")
            .replace("\\/", "/")
            .replace("\\n", "\n")
            .replace("\\r", "\r")
            .replace("\\t", "\t")
        return s
    }

    private suspend fun queryBalloonsFromJsSuspend(): List<BalloonInfo> {
        return kotlinx.coroutines.suspendCancellableCoroutine { cont ->
            runOnUiThread {
                queryBalloonsFromJs { balloons ->
                    if (cont.isActive) {
                        cont.resume(balloons) {}
                    }
                }
            }
        }
    }

    private fun parseBalloons(json: String): List<BalloonInfo> {
        val balloons = mutableListOf<BalloonInfo>()
        if (!json.startsWith("[")) return balloons

        // Get WebView's position on screen
        val location = IntArray(2)
        webView.getLocationOnScreen(location)
        val webViewLeft = location[0].toFloat()
        val webViewTop = location[1].toFloat()

        // The WebView scale converts CSS pixels to Android view pixels.
        // On most devices with viewport width=device-width, scale ≈ density.
        val scale = webView.scale

        Timber.d("parseBalloons: webViewPos=(%d,%d) scale=%.2f", location[0], location[1], scale)

        val regex = Regex("""\{[^\}]+\}""")
        for (match in regex.findAll(json)) {
            val obj = match.value
            val id = extractInt(obj, "id") ?: continue
            val color = extractString(obj, "color") ?: "unknown"
            val isGold = obj.contains("\"isGold\":true")
            val cssX = extractFloat(obj, "\"x\"") ?: continue
            val cssY = extractFloat(obj, "\"y\"") ?: continue

            // CSS px → screen px: scale by WebView's zoom, then offset by WebView position
            val screenX = webViewLeft + (cssX * scale)
            val screenY = webViewTop + (cssY * scale)

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
        val pattern = Regex("""$key\s*:\s*([\d.]+)""")
        return pattern.find(json)?.groupValues?.get(1)?.toFloatOrNull()
    }

    private fun setBotStatus(msg: String) {
        tvStatus.text = msg
        // Escape single quotes for JS string
        val escaped = msg.replace("'", "\\'")
        webView.evaluateJavascript("window.setBotStatus('$escaped')", null)
    }

    private fun updateStats() {
        val accuracy = if (botAttempts > 0) (botPops * 100 / botAttempts) else 0
        tvStats.text = "Bot: $botPops pops / $botAttempts attempts ($accuracy% accuracy)"
    }

    data class BalloonInfo(
        val id: Int,
        val color: String,
        val isGold: Boolean,
        val screenX: Float,
        val screenY: Float
    )

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
