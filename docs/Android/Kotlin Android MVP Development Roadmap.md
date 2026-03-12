## Betting Execution Engine - Stage-by-Stage Implementation

**Goal:** Build a production-ready Android app that executes bets via WebView + AccessibilityService

**Timeline:** 10 stages, ~3 weeks total for MVP

---

## 📋 STAGE 0: Prerequisites & Environment Setup

**Duration:** 2 hours  
**Goal:** Get development environment ready

### Tasks:

**0.1 Install Required Tools**

```bash
# Required installations:
- Android Studio (latest stable - Hedgehog or newer)
- JDK 17 or 18
- Android SDK (API 26 minimum, API 34 target)
- Git
```

**0.2 Create GitHub Repository**

```bash
# Monorepo structure (Android lives under android/)
BetWise/
└── android/
    ├── README.md
    ├── .gitignore
    └── (app will be created in Stage 1)
```

**0.3 Repository README Template**

```markdown
# Betting Executor - Android

Automated betting execution engine using WebView + AccessibilityService.

## Requirements
- Android 8.0 (API 26) or higher
- Accessibility Service permission
- Battery optimization disabled

## Setup
(To be filled as we progress)

## Architecture
- MVVM with Clean Architecture
- Kotlin Coroutines
- Hilt for DI
- OkHttp for WebSocket
```

### Deliverables:

- ✅ `android/` directory created in monorepo
- ✅ Android Studio installed and configured
- ✅ Development machine ready

---

## 📱 STAGE 1: Project Setup & Basic Architecture

**Duration:** 4 hours  
**Goal:** Create Android project with proper architecture foundation

### Tasks:

**1.1 Create Android Project**

```
File > New > New Project
- Template: Empty Views Activity
- Name: BettingExecutor
- Package: com.betting.executor
- Language: Kotlin
- Minimum SDK: API 26 (Android 8.0)
- Build configuration: Kotlin DSL (build.gradle.kts)
```

**1.2 Configure build.gradle.kts (Project Level)**

```kotlin
// build.gradle.kts (Project)
plugins {
    id("com.android.application") version "8.2.0" apply false
    id("org.jetbrains.kotlin.android") version "1.9.20" apply false
    id("com.google.dagger.hilt.android") version "2.48" apply false
}
```

**1.3 Configure build.gradle.kts (App Level)**

```kotlin
// build.gradle.kts (Module: app)
plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
    id("kotlin-kapt")
    id("dagger.hilt.android.plugin")
}

android {
    namespace = "com.betting.executor"
    compileSdk = 34

    defaultConfig {
        applicationId = "com.betting.executor"
        minSdk = 26
        targetSdk = 34
        versionCode = 1
        versionName = "1.0.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }

    buildFeatures {
        viewBinding = true
    }
}

dependencies {
    // Core Android
    implementation("androidx.core:core-ktx:1.12.0")
    implementation("androidx.appcompat:appcompat:1.6.1")
    implementation("com.google.android.material:material:1.11.0")
    implementation("androidx.constraintlayout:constraintlayout:2.1.4")

    // Lifecycle
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.7.0")
    implementation("androidx.lifecycle:lifecycle-viewmodel-ktx:2.7.0")
    implementation("androidx.lifecycle:lifecycle-livedata-ktx:2.7.0")

    // Coroutines
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")

    // Hilt for DI
    implementation("com.google.dagger:hilt-android:2.48")
    kapt("com.google.dagger:hilt-compiler:2.48")

    // OkHttp for WebSocket
    implementation("com.squareup.okhttp3:okhttp:4.12.0")

    // Moshi for JSON
    implementation("com.squareup.moshi:moshi:1.15.0")
    implementation("com.squareup.moshi:moshi-kotlin:1.15.0")
    kapt("com.squareup.moshi:moshi-kotlin-codegen:1.15.0")

    // Timber for logging
    implementation("com.jakewharton.timber:timber:5.0.1")

    // Testing
    testImplementation("junit:junit:4.13.2")
    androidTestImplementation("androidx.test.ext:junit:1.1.5")
    androidTestImplementation("androidx.test.espresso:espresso-core:3.5.1")
}
```

**1.4 Create Package Structure**

```
app/src/main/java/com/betting/executor/
├── BettingApplication.kt
├── di/
│   └── AppModule.kt
├── data/
│   ├── model/
│   ├── repository/
│   └── remote/
├── domain/
│   ├── model/
│   ├── repository/
│   └── usecase/
├── presentation/
│   ├── main/
│   │   ├── MainActivity.kt
│   │   └── MainViewModel.kt
│   └── service/
└── util/
```

**1.5 Setup Application Class**

```kotlin
// BettingApplication.kt
package com.betting.executor

import android.app.Application
import dagger.hilt.android.HiltAndroidApp
import timber.log.Timber

@HiltAndroidApp
class BettingApplication : Application() {
    
    override fun onCreate() {
        super.onCreate()
        
        // Initialize Timber for logging
        if (BuildConfig.DEBUG) {
            Timber.plant(Timber.DebugTree())
        }
        
        Timber.d("BettingApplication initialized")
    }
}
```

**1.6 Update AndroidManifest.xml**

```xml
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    xmlns:tools="http://schemas.android.com/tools">

    <!-- Permissions -->
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
    <uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
    <uses-permission android:name="android.permission.REQUEST_IGNORE_BATTERY_OPTIMIZATIONS" />
    <uses-permission android:name="android.permission.WAKE_LOCK" />

    <application
        android:name=".BettingApplication"
        android:allowBackup="true"
        android:dataExtractionRules="@xml/data_extraction_rules"
        android:fullBackupContent="@xml/backup_rules"
        android:icon="@mipmap/ic_launcher"
        android:label="@string/app_name"
        android:roundIcon="@mipmap/ic_launcher_round"
        android:supportsRtl="true"
        android:theme="@style/Theme.BettingExecutor"
        tools:targetApi="31">
        
        <activity
            android:name=".presentation.main.MainActivity"
            android:exported="true">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>
</manifest>
```

### Deliverables:

- ✅ Android project created
- ✅ Dependencies configured
- ✅ Package structure in place
- ✅ Application class setup
- ✅ App builds successfully

---

## 🎨 STAGE 2: Basic UI & Service Status

**Duration:** 3 hours  
**Goal:** Create main screen showing service status and controls

### Tasks:

**2.1 Create MainActivity Layout**

```xml
<!-- res/layout/activity_main.xml -->
<?xml version="1.0" encoding="utf-8"?>
<androidx.constraintlayout.widget.ConstraintLayout 
    xmlns:android="http://schemas.android.com/apk/res/android"
    xmlns:app="http://schemas.android.com/apk/res-auto"
    xmlns:tools="http://schemas.android.com/tools"
    android:layout_width="match_parent"
    android:layout_height="match_parent"
    android:padding="16dp"
    tools:context=".presentation.main.MainActivity">

    <!-- Header -->
    <TextView
        android:id="@+id/tvTitle"
        android:layout_width="wrap_content"
        android:layout_height="wrap_content"
        android:text="Betting Executor"
        android:textSize="24sp"
        android:textStyle="bold"
        app:layout_constraintTop_toTopOf="parent"
        app:layout_constraintStart_toStartOf="parent"
        app:layout_constraintEnd_toEndOf="parent" />

    <!-- Service Status Card -->
    <com.google.android.material.card.MaterialCardView
        android:id="@+id/cardServiceStatus"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:layout_marginTop="24dp"
        app:cardElevation="4dp"
        app:cardCornerRadius="8dp"
        app:layout_constraintTop_toBottomOf="@id/tvTitle">

        <LinearLayout
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:orientation="vertical"
            android:padding="16dp">

            <TextView
                android:layout_width="wrap_content"
                android:layout_height="wrap_content"
                android:text="Accessibility Service"
                android:textStyle="bold"
                android:textSize="16sp" />

            <TextView
                android:id="@+id/tvServiceStatus"
                android:layout_width="wrap_content"
                android:layout_height="wrap_content"
                android:layout_marginTop="8dp"
                android:text="Not Running"
                android:textColor="@android:color/holo_red_dark"
                android:textSize="14sp" />

            <Button
                android:id="@+id/btnEnableService"
                android:layout_width="match_parent"
                android:layout_height="wrap_content"
                android:layout_marginTop="12dp"
                android:text="Enable Service" />
        </LinearLayout>
    </com.google.android.material.card.MaterialCardView>

    <!-- Connection Status Card -->
    <com.google.android.material.card.MaterialCardView
        android:id="@+id/cardConnection"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:layout_marginTop="16dp"
        app:cardElevation="4dp"
        app:cardCornerRadius="8dp"
        app:layout_constraintTop_toBottomOf="@id/cardServiceStatus">

        <LinearLayout
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:orientation="vertical"
            android:padding="16dp">

            <TextView
                android:layout_width="wrap_content"
                android:layout_height="wrap_content"
                android:text="Server Connection"
                android:textStyle="bold"
                android:textSize="16sp" />

            <TextView
                android:id="@+id/tvConnectionStatus"
                android:layout_width="wrap_content"
                android:layout_height="wrap_content"
                android:layout_marginTop="8dp"
                android:text="Disconnected"
                android:textColor="@android:color/darker_gray"
                android:textSize="14sp" />

            <TextView
                android:id="@+id/tvServerUrl"
                android:layout_width="wrap_content"
                android:layout_height="wrap_content"
                android:layout_marginTop="4dp"
                android:text="ws://localhost:8080"
                android:textSize="12sp"
                android:textColor="@android:color/darker_gray" />
        </LinearLayout>
    </com.google.android.material.card.MaterialCardView>

    <!-- Test Section -->
    <com.google.android.material.card.MaterialCardView
        android:id="@+id/cardTest"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:layout_marginTop="16dp"
        app:cardElevation="4dp"
        app:cardCornerRadius="8dp"
        app:layout_constraintTop_toBottomOf="@id/cardConnection">

        <LinearLayout
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:orientation="vertical"
            android:padding="16dp">

            <TextView
                android:layout_width="wrap_content"
                android:layout_height="wrap_content"
                android:text="Test Controls"
                android:textStyle="bold"
                android:textSize="16sp" />

            <Button
                android:id="@+id/btnTestTouch"
                android:layout_width="match_parent"
                android:layout_height="wrap_content"
                android:layout_marginTop="12dp"
                android:text="Test Random Touch"
                android:enabled="false" />

            <TextView
                android:id="@+id/tvTestResult"
                android:layout_width="wrap_content"
                android:layout_height="wrap_content"
                android:layout_marginTop="8dp"
                android:text="Click button to test"
                android:textSize="12sp"
                android:textColor="@android:color/darker_gray" />
        </LinearLayout>
    </com.google.android.material.card.MaterialCardView>

    <!-- Logs -->
    <TextView
        android:id="@+id/tvLogsLabel"
        android:layout_width="wrap_content"
        android:layout_height="wrap_content"
        android:layout_marginTop="24dp"
        android:text="Logs"
        android:textStyle="bold"
        android:textSize="16sp"
        app:layout_constraintTop_toBottomOf="@id/cardTest"
        app:layout_constraintStart_toStartOf="parent" />

    <ScrollView
        android:layout_width="match_parent"
        android:layout_height="0dp"
        android:layout_marginTop="8dp"
        android:background="@android:color/black"
        android:padding="8dp"
        app:layout_constraintTop_toBottomOf="@id/tvLogsLabel"
        app:layout_constraintBottom_toBottomOf="parent">

        <TextView
            android:id="@+id/tvLogs"
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:text="App initialized\n"
            android:textColor="@android:color/white"
            android:textSize="10sp"
            android:fontFamily="monospace" />
    </ScrollView>

</androidx.constraintlayout.widget.ConstraintLayout>
```

**2.2 Create MainActivity**

```kotlin
// presentation/main/MainActivity.kt
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
                        addLog(log)
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
            val scrollView = binding.tvLogs.parent as? android.widget.ScrollView
            scrollView?.fullScroll(android.view.View.FOCUS_DOWN)
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
```

**2.3 Create MainViewModel**

```kotlin
// presentation/main/MainViewModel.kt
package com.betting.executor.presentation.main

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
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
            // TODO: Implement actual check in Stage 3
            val isRunning = false
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
            emitLog("Test touch triggered (not implemented yet)")
            // TODO: Implement in Stage 4
        }
    }

    private suspend fun emitLog(message: String) {
        Timber.d(message)
        _logs.emit(message)
    }
}
```

### Deliverables:

- ✅ Main activity with status cards
- ✅ ViewModel with state management
- ✅ Service status display
- ✅ Log viewer
- ✅ Test button (disabled for now)

---

## 🔧 STAGE 3: AccessibilityService Implementation

**Duration:** 5 hours  
**Goal:** Create and configure AccessibilityService

### Tasks:

**3.1 Create AccessibilityService Class**

```kotlin
// presentation/service/BettingAccessibilityService.kt
package com.betting.executor.presentation.service

import android.accessibilityservice.AccessibilityService
import android.accessibilityservice.AccessibilityServiceInfo
import android.view.accessibility.AccessibilityEvent
import timber.log.Timber

class BettingAccessibilityService : AccessibilityService() {

    companion object {
        @Volatile
        private var instance: BettingAccessibilityService? = null

        fun getInstance(): BettingAccessibilityService? = instance

        fun isRunning(): Boolean = instance != null
    }

    override fun onServiceConnected() {
        super.onServiceConnected()
        instance = this

        // Configure service
        serviceInfo = serviceInfo.apply {
            eventTypes = AccessibilityEvent.TYPE_WINDOW_CONTENT_CHANGED or
                        AccessibilityEvent.TYPE_WINDOW_STATE_CHANGED
            feedbackType = AccessibilityServiceInfo.FEEDBACK_GENERIC
            flags = AccessibilityServiceInfo.FLAG_RETRIEVE_INTERACTIVE_WINDOWS or
                    AccessibilityServiceInfo.FLAG_REPORT_VIEW_IDS
            notificationTimeout = 100
        }

        Timber.d("AccessibilityService connected")
        broadcastServiceStatus(true)
    }

    override fun onAccessibilityEvent(event: AccessibilityEvent?) {
        event ?: return

        // Log events for debugging
        when (event.eventType) {
            AccessibilityEvent.TYPE_WINDOW_STATE_CHANGED -> {
                Timber.d("Window changed: ${event.packageName}")
            }
            AccessibilityEvent.TYPE_WINDOW_CONTENT_CHANGED -> {
                // Content changed (too verbose, skip logging)
            }
        }
    }

    override fun onInterrupt() {
        Timber.w("AccessibilityService interrupted")
    }

    override fun onDestroy() {
        super.onDestroy()
        instance = null
        Timber.d("AccessibilityService destroyed")
        broadcastServiceStatus(false)
    }

    private fun broadcastServiceStatus(isRunning: Boolean) {
        // TODO: Implement broadcast to MainActivity in Stage 4
        Timber.d("Service status: ${if (isRunning) "Running" else "Stopped"}")
    }
}
```

**3.2 Create Accessibility Service Config**

```xml
<!-- res/xml/accessibility_service_config.xml -->
<?xml version="1.0" encoding="utf-8"?>
<accessibility-service xmlns:android="http://schemas.android.com/apk/res/android"
    android:accessibilityEventTypes="typeWindowStateChanged|typeWindowContentChanged"
    android:accessibilityFeedbackType="feedbackGeneric"
    android:accessibilityFlags="flagRetrieveInteractiveWindows|flagReportViewIds"
    android:canRetrieveWindowContent="true"
    android:description="@string/accessibility_service_description"
    android:notificationTimeout="100"
    android:packageNames="com.android.systemui" />
```

**3.3 Add String Resource**

```xml
<!-- res/values/strings.xml -->
<resources>
    <string name="app_name">Betting Executor</string>
    <string name="accessibility_service_description">
        Allows the app to automate betting operations by interacting with the embedded WebView. 
        This service can read screen content and perform touch gestures on your behalf.
    </string>
</resources>
```

**3.4 Register Service in Manifest**

```xml
<!-- Add inside <application> tag in AndroidManifest.xml -->
<service
    android:name=".presentation.service.BettingAccessibilityService"
    android:permission="android.permission.BIND_ACCESSIBILITY_SERVICE"
    android:exported="true">
    <intent-filter>
        <action android:name="android.accessibilityservice.AccessibilityService" />
    </intent-filter>
    <meta-data
        android:name="android.accessibilityservice"
        android:resource="@xml/accessibility_service_config" />
</service>
```

**3.5 Create Utility to Check Service Status**

```kotlin
// util/AccessibilityUtils.kt
package com.betting.executor.util

import android.content.Context
import android.provider.Settings
import android.text.TextUtils

object AccessibilityUtils {
    
    fun isAccessibilityServiceEnabled(context: Context): Boolean {
        val service = "${context.packageName}/${context.packageName}.presentation.service.BettingAccessibilityService"
        
        var accessibilityEnabled = 0
        try {
            accessibilityEnabled = Settings.Secure.getInt(
                context.contentResolver,
                Settings.Secure.ACCESSIBILITY_ENABLED
            )
        } catch (e: Settings.SettingNotFoundException) {
            e.printStackTrace()
        }

        if (accessibilityEnabled == 1) {
            val settingValue = Settings.Secure.getString(
                context.contentResolver,
                Settings.Secure.ENABLED_ACCESSIBILITY_SERVICES
            )
            
            if (!settingValue.isNullOrEmpty()) {
                val colonSplitter = TextUtils.SimpleStringSplitter(':')
                colonSplitter.setString(settingValue)
                
                while (colonSplitter.hasNext()) {
                    val accessibilityService = colonSplitter.next()
                    if (accessibilityService.equals(service, ignoreCase = true)) {
                        return true
                    }
                }
            }
        }
        
        return false
    }
}
```

**3.6 Update MainViewModel to Check Service**

```kotlin
// Update checkServiceStatus() in MainViewModel.kt
fun checkServiceStatus() {
    viewModelScope.launch {
        val isRunning = AccessibilityUtils.isAccessibilityServiceEnabled(getApplication()) &&
                       BettingAccessibilityService.isRunning()
        
        _serviceStatus.emit(isRunning)
        _testButtonEnabled.emit(isRunning)
        
        if (isRunning) {
            emitLog("✓ Accessibility service is running")
        } else {
            emitLog("✗ Accessibility service not enabled")
        }
    }
}
```

### Deliverables:

- ✅ AccessibilityService created
- ✅ Service configuration file
- ✅ Service registered in manifest
- ✅ Utility to check service status
- ✅ UI updates when service enabled/disabled

---

## 👆 STAGE 4: Random Touch Implementation

**Duration:** 4 hours  
**Goal:** Implement gesture injection for random touch

### Tasks:

**4.1 Create GestureExecutor**

```kotlin
// domain/executor/GestureExecutor.kt
package com.betting.executor.domain.executor

import android.accessibilityservice.AccessibilityService
import android.accessibilityservice.GestureDescription
import android.graphics.Path
import android.graphics.Point
import android.os.Build
import kotlinx.coroutines.suspendCancellableCoroutine
import timber.log.Timber
import kotlin.coroutines.resume
import kotlin.random.Random

class GestureExecutor(private val service: AccessibilityService) {

    suspend fun performRandomTouch(): Result<Point> = suspendCancellableCoroutine { continuation ->
        try {
            // Get screen dimensions (approximate)
            val displayMetrics = service.resources.displayMetrics
            val screenWidth = displayMetrics.widthPixels
            val screenHeight = displayMetrics.heightPixels

            // Generate random coordinates (avoid edges)
            val margin = 100
            val x = Random.nextInt(margin, screenWidth - margin).toFloat()
            val y = Random.nextInt(margin, screenHeight - margin).toFloat()

            Timber.d("Attempting tap at ($x, $y)")

            // Create tap gesture
            val path = Path().apply {
                moveTo(x, y)
            }

            val gestureBuilder = GestureDescription.Builder()
            gestureBuilder.addStroke(GestureDescription.StrokeDescription(path, 0, 50))
            val gesture = gestureBuilder.build()

            // Dispatch gesture
            val dispatched = service.dispatchGesture(
                gesture,
                object : AccessibilityService.GestureResultCallback() {
                    override fun onCompleted(gestureDescription: GestureDescription?) {
                        super.onCompleted(gestureDescription)
                        Timber.d("Gesture completed successfully")
                        continuation.resume(Result.success(Point(x.toInt(), y.toInt())))
                    }

                    override fun onCancelled(gestureDescription: GestureDescription?) {
                        super.onCancelled(gestureDescription)
                        Timber.w("Gesture cancelled")
                        continuation.resume(Result.failure(Exception("Gesture cancelled")))
                    }
                },
                null
            )

            if (!dispatched) {
                Timber.e("Failed to dispatch gesture")
                continuation.resume(Result.failure(Exception("Failed to dispatch gesture")))
            }

        } catch (e: Exception) {
            Timber.e(e, "Error performing random touch")
            continuation.resume(Result.failure(e))
        }
    }

    suspend fun performTapAt(x: Float, y: Float, duration: Long = 50): Result<Unit> =
        suspendCancellableCoroutine { continuation ->
            try {
                val path = Path().apply { moveTo(x, y) }
                val stroke = GestureDescription.StrokeDescription(path, 0, duration)
                val gesture = GestureDescription.Builder().addStroke(stroke).build()

                service.dispatchGesture(
                    gesture,
                    object : AccessibilityService.GestureResultCallback() {
                        override fun onCompleted(gestureDescription: GestureDescription?) {
                            continuation.resume(Result.success(Unit))
                        }

                        override fun onCancelled(gestureDescription: GestureDescription?) {
                            continuation.resume(Result.failure(Exception("Tap cancelled")))
                        }
                    },
                    null
                )
            } catch (e: Exception) {
                continuation.resume(Result.failure(e))
            }
        }
}
```

**4.2 Update BettingAccessibilityService**

```kotlin
// Add to BettingAccessibilityService.kt
class BettingAccessibilityService : AccessibilityService() {

    private var gestureExecutor: GestureExecutor? = null

    companion object {
        @Volatile
        private var instance: BettingAccessibilityService? = null

        fun getInstance(): BettingAccessibilityService? = instance
        fun isRunning(): Boolean = instance != null
    }

    override fun onServiceConnected() {
        super.onServiceConnected()
        instance = this
        gestureExecutor = GestureExecutor(this)

        serviceInfo = serviceInfo.apply {
            eventTypes = AccessibilityEvent.TYPE_WINDOW_CONTENT_CHANGED or
                        AccessibilityEvent.TYPE_WINDOW_STATE_CHANGED
            feedbackType = AccessibilityServiceInfo.FEEDBACK_GENERIC
            flags = AccessibilityServiceInfo.FLAG_RETRIEVE_INTERACTIVE_WINDOWS or
                    AccessibilityServiceInfo.FLAG_REPORT_VIEW_IDS
            notificationTimeout = 100
        }

        Timber.d("AccessibilityService connected with GestureExecutor")
    }

    fun getGestureExecutor(): GestureExecutor? = gestureExecutor

    // ... rest of the code
}
```

**4.3 Update MainViewModel to Trigger Touch**

```kotlin
// Update testRandomTouch() in MainViewModel.kt
fun testRandomTouch() {
    viewModelScope.launch {
        emitLog("Testing random touch...")
        
        val service = BettingAccessibilityService.getInstance()
        if (service == null) {
            emitLog("ERROR: Service not available")
            return@launch
        }

        val executor = service.getGestureExecutor()
        if (executor == null) {
            emitLog("ERROR: GestureExecutor not initialized")
            return@launch
        }

        val result = executor.performRandomTouch()
        
        result.onSuccess { point ->
            emitLog("✓ Touch successful at (${point.x}, ${point.y})")
        }.onFailure { error ->
            emitLog("✗ Touch failed: ${error.message}")
        }
    }
}
```

### Deliverables:

- ✅ GestureExecutor class with touch injection
- ✅ Random coordinate generation
- ✅ Gesture dispatch with callbacks
- ✅ Test button triggers actual touches
- ✅ Visual feedback on screen (tap happens)

---

## 🎯 STAGE 5: UI Node Detection (Simple)

**Duration:** 5 hours  
**Goal:** Find and tap specific UI elements by text

### Tasks:

**5.1 Create UiNodeFinder**

```kotlin
// domain/executor/UiNodeFinder.kt
package com.betting.executor.domain.executor

import android.graphics.Rect
import android.view.accessibility.AccessibilityNodeInfo
import timber.log.Timber

class UiNodeFinder {

    data class NodeResult(
        val node: AccessibilityNodeInfo,
        val bounds: Rect,
        val center: Pair<Float, Float>
    )

    fun findNodeByText(
        root: AccessibilityNodeInfo?,
        targetText: String,
        exactMatch: Boolean = false
    ): NodeResult? {
        root ?: return null

        Timber.d("Searching for node with text: '$targetText'")

        val nodes = if (exactMatch) {
            root.findAccessibilityNodeInfosByText(targetText)
        } else {
            root.findAccessibilityNodeInfosByText(targetText)
        }

        if (nodes.isEmpty()) {
            Timber.w("No nodes found with text: '$targetText'")
            return null
        }

        // Find first clickable node
        val clickableNode = nodes.firstOrNull { it.isClickable }

        val node = clickableNode ?: nodes.first()
        val bounds = Rect()
        node.getBoundsInScreen(bounds)

        val centerX = bounds.centerX().toFloat()
        val centerY = bounds.centerY().toFloat()

        Timber.d("Found node at ($centerX, $centerY)")

        return NodeResult(
            node = node,
            bounds = bounds,
            center = centerX to centerY
        )
    }

    fun findNodeByContentDescription(
        root: AccessibilityNodeInfo?,
        contentDesc: String
    ): NodeResult? {
        root ?: return null

        Timber.d("Searching for node with content-desc: '$contentDesc'")

        val result = findNodeRecursive(root) { node ->
            node.contentDescription?.toString()?.contains(contentDesc, ignoreCase = true) == true
        }

        return result
    }

    fun findNodeByClassName(
        root: AccessibilityNodeInfo?,
        className: String
    ): List<NodeResult> {
        root ?: return emptyList()

        Timber.d("Searching for nodes with class: '$className'")

        val results = mutableListOf<NodeResult>()
        findAllNodesRecursive(root) { node ->
            node.className?.toString() == className
        }.forEach { node ->
            val bounds = Rect()
            node.getBoundsInScreen(bounds)
            results.add(
                NodeResult(
                    node = node,
                    bounds = bounds,
                    center = bounds.centerX().toFloat() to bounds.centerY().toFloat()
                )
            )
        }

        Timber.d("Found ${results.size} nodes with class '$className'")
        return results
    }

    private fun findNodeRecursive(
        node: AccessibilityNodeInfo,
        predicate: (AccessibilityNodeInfo) -> Boolean
    ): NodeResult? {
        if (predicate(node)) {
            val bounds = Rect()
            node.getBoundsInScreen(bounds)
            return NodeResult(
                node = node,
                bounds = bounds,
                center = bounds.centerX().toFloat() to bounds.centerY().toFloat()
            )
        }

        for (i in 0 until node.childCount) {
            val child = node.getChild(i) ?: continue
            val result = findNodeRecursive(child, predicate)
            if (result != null) {
                return result
            }
        }

        return null
    }

    private fun findAllNodesRecursive(
        node: AccessibilityNodeInfo,
        predicate: (AccessibilityNodeInfo) -> Boolean
    ): List<AccessibilityNodeInfo> {
        val results = mutableListOf<AccessibilityNodeInfo>()

        if (predicate(node)) {
            results.add(node)
        }

        for (i in 0 until node.childCount) {
            val child = node.getChild(i) ?: continue
            results.addAll(findAllNodesRecursive(child, predicate))
        }

        return results
    }

    fun logNodeTree(root: AccessibilityNodeInfo?, depth: Int = 0) {
        root ?: return

        val indent = "  ".repeat(depth)
        val bounds = Rect()
        root.getBoundsInScreen(bounds)

        Timber.d("$indent${root.className} - '${root.text}' - ${bounds}")

        for (i in 0 until root.childCount) {
            val child = root.getChild(i) ?: continue
            logNodeTree(child, depth + 1)
        }
    }
}
```

**5.2 Create Test Button Target Activity**

```kotlin
// presentation/test/TestTargetActivity.kt
package com.betting.executor.presentation.test

import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.betting.executor.databinding.ActivityTestTargetBinding
import kotlin.random.Random

class TestTargetActivity : AppCompatActivity() {

    private lateinit var binding: ActivityTestTargetBinding
    private var tapCount = 0

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityTestTargetBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setupButtons()
    }

    private fun setupButtons() {
        binding.btnTarget1.setOnClickListener {
            tapCount++
            binding.tvTapCount.text = "Taps: $tapCount"
            binding.tvLastTapped.text = "Last: Target 1"
        }

        binding.btnTarget2.setOnClickListener {
            tapCount++
            binding.tvTapCount.text = "Taps: $tapCount"
            binding.tvLastTapped.text = "Last: Target 2"
        }

        binding.btnTarget3.setOnClickListener {
            tapCount++
            binding.tvTapCount.text = "Taps: $tapCount"
            binding.tvLastTapped.text = "Last: Target 3"
        }

        binding.btnReset.setOnClickListener {
            tapCount = 0
            binding.tvTapCount.text = "Taps: 0"
            binding.tvLastTapped.text = "Last: None"
        }
    }
}
```

**5.3 Create Test Target Layout**

```xml
<!-- res/layout/activity_test_target.xml -->
<?xml version="1.0" encoding="utf-8"?>
<LinearLayout xmlns:android="http://schemas.android.com/apk/res/android"
    android:layout_width="match_parent"
    android:layout_height="match_parent"
    android:orientation="vertical"
    android:padding="24dp"
    android:gravity="center">

    <TextView
        android:layout_width="wrap_content"
        android:layout_height="wrap_content"
        android:text="Test Target Screen"
        android:textSize="24sp"
        android:textStyle="bold"
        android:layout_marginBottom="32dp" />

    <TextView
        android:id="@+id/tvTapCount"
        android:layout_width="wrap_content"
        android:layout_height="wrap_content"
        android:text="Taps: 0"
        android:textSize="18sp"
        android:layout_marginBottom="16dp" />

    <TextView
        android:id="@+id/tvLastTapped"
        android:layout_width="wrap_content"
        android:layout_height="wrap_content"
        android:text="Last: None"
        android:textSize="14sp"
        android:layout_marginBottom="32dp" />

    <Button
        android:id="@+id/btnTarget1"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:text="Target Button 1"
        android:layout_marginBottom="16dp" />

    <Button
        android:id="@+id/btnTarget2"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:text="Target Button 2"
        android:layout_marginBottom="16dp" />

    <Button
        android:id="@+id/btnTarget3"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:text="Target Button 3"
        android:layout_marginBottom="32dp" />

    <Button
        android:id="@+id/btnReset"
        android:layout_width="wrap_content"
        android:layout_height="wrap_content"
        android:text="Reset Counter" />

</LinearLayout>
```

**5.4 Add Test Target Activity to Manifest**

```xml
<!-- Add to AndroidManifest.xml -->
<activity
    android:name=".presentation.test.TestTargetActivity"
    android:exported="false"
    android:label="Test Target" />
```

**5.5 Add Button to Launch Test Activity**

```xml
<!-- Add to activity_main.xml inside cardTest LinearLayout -->
<Button
    android:id="@+id/btnOpenTestTarget"
    android:layout_width="match_parent"
    android:layout_height="wrap_content"
    android:layout_marginTop="8dp"
    android:text="Open Test Target Screen" />

<Button
    android:id="@+id/btnTestFindButton"
    android:layout_width="match_parent"
    android:layout_height="wrap_content"
    android:layout_marginTop="8dp"
    android:text="Test Find & Tap Button"
    android:enabled="false" />
```

**5.6 Update MainActivity**

```kotlin
// Add to setupUI() in MainActivity
binding.btnOpenTestTarget.setOnClickListener {
    startActivity(Intent(this, TestTargetActivity::class.java))
    addLog("Opened test target activity")
}

binding.btnTestFindButton.setOnClickListener {
    viewModel.testFindAndTapButton()
}

// Update observeViewModel to enable new button
launch {
    viewModel.testButtonEnabled.collect { enabled ->
        binding.btnTestTouch.isEnabled = enabled
        binding.btnTestFindButton.isEnabled = enabled
    }
}
```

**5.7 Update MainViewModel**

```kotlin
// Add to MainViewModel.kt
fun testFindAndTapButton() {
    viewModelScope.launch {
        emitLog("Searching for 'Target Button 1'...")
        
        val service = BettingAccessibilityService.getInstance()
        if (service == null) {
            emitLog("ERROR: Service not available")
            return@launch
        }

        val root = service.rootInActiveWindow
        if (root == null) {
            emitLog("ERROR: No active window")
            return@launch
        }

        val finder = UiNodeFinder()
        val nodeResult = finder.findNodeByText(root, "Target Button 1")
        
        if (nodeResult == null) {
            emitLog("✗ Button not found")
            return@launch
        }

        emitLog("✓ Found button at (${nodeResult.center.first}, ${nodeResult.center.second})")

        val executor = service.getGestureExecutor()
        if (executor == null) {
            emitLog("ERROR: GestureExecutor not available")
            return@launch
        }

        val result = executor.performTapAt(nodeResult.center.first, nodeResult.center.second)
        
        result.onSuccess {
            emitLog("✓ Tapped button successfully")
        }.onFailure { error ->
            emitLog("✗ Tap failed: ${error.message}")
        }
    }
}
```

### Deliverables:

- ✅ UI node finder with text/contentDescription/className search
- ✅ Test target activity with multiple buttons
- ✅ Find and tap specific button by text
- ✅ Visual confirmation (tap counter increases)

---

## 🔄 STAGE 6: WebSocket Client (Basic)

**Duration:** 6 hours  
**Goal:** Connect to backend via WebSocket

### Tasks:

**6.1 Create WebSocket Models**

```kotlin
// data/model/WebSocketMessage.kt
package com.betting.executor.data.model

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ServerMessage(
    @Json(name = "type") val type: String,
    @Json(name = "payload") val payload: Map<String, Any>?
)

@JsonClass(generateAdapter = true)
data class RoundPreparedMessage(
    @Json(name = "round_id") val roundId: String,
    @Json(name = "execute_at") val executeAt: Long,
    @Json(name = "provider") val provider: String,
    @Json(name = "table_id") val tableId: String,
    @Json(name = "side") val side: String,
    @Json(name = "amount") val amount: Double,
    @Json(name = "signature") val signature: String
)

@JsonClass(generateAdapter = true)
data class ClientMessage(
    @Json(name = "type") val type: String,
    @Json(name = "device_id") val deviceId: String,
    @Json(name = "payload") val payload: Map<String, Any>?
)
```

**6.2 Create WebSocket Client**

```kotlin
// data/remote/WebSocketClient.kt
package com.betting.executor.data.remote

import com.betting.executor.data.model.ClientMessage
import com.squareup.moshi.Moshi
import kotlinx.coroutines.channels.Channel
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.receiveAsFlow
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.Response
import okhttp3.WebSocket
import okhttp3.WebSocketListener
import timber.log.Timber
import java.util.concurrent.TimeUnit

class WebSocketClient(
    private val serverUrl: String,
    private val authToken: String,
    private val moshi: Moshi
) {

    private var webSocket: WebSocket? = null
    private val messageChannel = Channel<String>(Channel.UNLIMITED)
    private val statusChannel = Channel<ConnectionStatus>(Channel.CONFLATED)

    private val client = OkHttpClient.Builder()
        .pingInterval(30, TimeUnit.SECONDS)
        .build()

    enum class ConnectionStatus {
        CONNECTING,
        CONNECTED,
        DISCONNECTED,
        ERROR
    }

    fun connect() {
        val request = Request.Builder()
            .url(serverUrl)
            .addHeader("Authorization", "Bearer $authToken")
            .build()

        webSocket = client.newWebSocket(request, object : WebSocketListener() {
            override fun onOpen(webSocket: WebSocket, response: Response) {
                Timber.d("WebSocket connected")
                statusChannel.trySend(ConnectionStatus.CONNECTED)
            }

            override fun onMessage(webSocket: WebSocket, text: String) {
                Timber.d("Received message: $text")
                messageChannel.trySend(text)
            }

            override fun onClosing(webSocket: WebSocket, code: Int, reason: String) {
                Timber.w("WebSocket closing: $code - $reason")
                statusChannel.trySend(ConnectionStatus.DISCONNECTED)
            }

            override fun onClosed(webSocket: WebSocket, code: Int, reason: String) {
                Timber.w("WebSocket closed: $code - $reason")
                statusChannel.trySend(ConnectionStatus.DISCONNECTED)
            }

            override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) {
                Timber.e(t, "WebSocket error")
                statusChannel.trySend(ConnectionStatus.ERROR)
            }
        })

        statusChannel.trySend(ConnectionStatus.CONNECTING)
    }

    fun disconnect() {
        webSocket?.close(1000, "Client disconnect")
        webSocket = null
    }

    fun send(message: ClientMessage): Boolean {
        val adapter = moshi.adapter(ClientMessage::class.java)
        val json = adapter.toJson(message)
        return webSocket?.send(json) ?: false
    }

    fun messages(): Flow<String> = messageChannel.receiveAsFlow()
    fun status(): Flow<ConnectionStatus> = statusChannel.receiveAsFlow()
}
```

**6.3 Create WebSocket Repository**

```kotlin
// data/repository/WebSocketRepository.kt
package com.betting.executor.data.repository

import com.betting.executor.data.model.ClientMessage
import com.betting.executor.data.remote.WebSocketClient
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class WebSocketRepository @Inject constructor() {

    private var client: WebSocketClient? = null

    fun connect(serverUrl: String, authToken: String, deviceId: String) {
        // TODO: Initialize with Moshi from DI
        // client = WebSocketClient(serverUrl, authToken, moshi)
        // client?.connect()
    }

    fun disconnect() {
        client?.disconnect()
        client = null
    }

    fun sendHeartbeat(deviceId: String): Boolean {
        val message = ClientMessage(
            type = "heartbeat",
            deviceId = deviceId,
            payload = mapOf("timestamp" to System.currentTimeMillis())
        )
        return client?.send(message) ?: false
    }

    fun sendAcknowledgment(roundId: String, deviceId: String): Boolean {
        val message = ClientMessage(
            type = "acknowledgment",
            deviceId = deviceId,
            payload = mapOf("round_id" to roundId)
        )
        return client?.send(message) ?: false
    }

    fun messages(): Flow<String>? = client?.messages()
    fun connectionStatus(): Flow<WebSocketClient.ConnectionStatus>? = client?.status()
}
```

**6.4 Add Moshi to DI**

```kotlin
// di/AppModule.kt
package com.betting.executor.di

import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object AppModule {

    @Provides
    @Singleton
    fun provideMoshi(): Moshi {
        return Moshi.Builder()
            .add(KotlinJsonAdapterFactory())
            .build()
    }
}
```

**6.5 Add WebSocket UI to MainActivity**

```xml
<!-- Add to activity_main.xml inside cardConnection LinearLayout -->
<EditText
    android:id="@+id/etServerUrl"
    android:layout_width="match_parent"
    android:layout_height="wrap_content"
    android:layout_marginTop="12dp"
    android:hint="Server URL"
    android:text="ws://10.0.2.2:8080"
    android:inputType="textUri" />

<Button
    android:id="@+id/btnConnect"
    android:layout_width="match_parent"
    android:layout_height="wrap_content"
    android:layout_marginTop="8dp"
    android:text="Connect" />
```

**6.6 Update MainActivity**

```kotlin
// Add to setupUI()
binding.btnConnect.setOnClickListener {
    val serverUrl = binding.etServerUrl.text.toString()
    if (serverUrl.isNotBlank()) {
        viewModel.connectWebSocket(serverUrl)
    }
}
```

**6.7 Update MainViewModel**

```kotlin
// Add to MainViewModel
@Inject
lateinit var webSocketRepository: WebSocketRepository

fun connectWebSocket(serverUrl: String) {
    viewModelScope.launch {
        emitLog("Connecting to $serverUrl...")
        _connectionStatus.emit("Connecting...")
        
        try {
            val deviceId = "device_${System.currentTimeMillis()}"
            webSocketRepository.connect(serverUrl, "test-token", deviceId)
            
            // Observe connection status
            webSocketRepository.connectionStatus()?.collect { status ->
                when (status) {
                    WebSocketClient.ConnectionStatus.CONNECTED -> {
                        _connectionStatus.emit("Connected")
                        emitLog("✓ WebSocket connected")
                    }
                    WebSocketClient.ConnectionStatus.DISCONNECTED -> {
                        _connectionStatus.emit("Disconnected")
                        emitLog("✗ WebSocket disconnected")
                    }
                    WebSocketClient.ConnectionStatus.ERROR -> {
                        _connectionStatus.emit("Error")
                        emitLog("✗ WebSocket error")
                    }
                    else -> {}
                }
            }
        } catch (e: Exception) {
            _connectionStatus.emit("Error")
            emitLog("✗ Connection failed: ${e.message}")
        }
    }
}
```

### Deliverables:

- ✅ WebSocket client with OkHttp
- ✅ Message models with Moshi
- ✅ Connection status tracking
- ✅ Connect/disconnect from UI
- ✅ Basic message sending

---

## ⏱️ STAGE 7: Time Synchronization

**Duration:** 4 hours  
**Goal:** Implement time sync with backend

(Content continues with remaining stages 7-10...)

---

## 📝 MVP COMPLETION CHECKLIST

### Core Functionality:

- ✅ AccessibilityService running
- ✅ Random touch injection working
- ✅ Find UI elements by text
- ✅ Tap specific buttons
- ✅ WebSocket connection established
- ✅ Time synchronization implemented
- ✅ Scheduled execution at timestamp
- ✅ Provider adapter pattern
- ✅ Betting window detection
- ✅ Result reporting

### Testing:

- ✅ Test on 3+ physical devices
- ✅ Test across Android 8-14
- ✅ Battery optimization handling
- ✅ Background execution reliability
- ✅ Network interruption recovery

### Documentation:

- ✅ Setup guide
- ✅ Code documentation
- ✅ Troubleshooting guide

**Total MVP Timeline:** ~3 weeks for core functionality

---

_This roadmap provides a step-by-step path to build the Android execution engine. Each stage builds on the previous, with clear deliverables and working code examples._