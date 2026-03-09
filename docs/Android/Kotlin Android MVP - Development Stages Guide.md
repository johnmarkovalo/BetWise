## Instructions for Claude Code (or AI Pair Programmer)

**Project:** Betting Executor Android App  
**Schedule:** 3 hours/day, Mon-Fri, 5 weeks total  
**Repository:** `betting-executor-android`

---

## How to Use This Guide

When working with Claude Code or any AI coding assistant, use these exact commands:

```bash
# Start a new stage
"Start Stage 1"

# Get stage details
"Show Stage 3 requirements"

# Mark complete
"Finish Stage 2"

# Get next steps
"What's next after Stage 5?"

# Review progress
"Show completed stages"
```

Each stage is a complete, testable unit of work that builds on the previous stage.

---

## 📋 STAGE 0: Prerequisites & Setup

**Duration:** 2 hours (Monday Week 1, first session)  
**Goal:** Development environment ready, repository created

### Context

This is the foundation. No coding yet, just setting up tools and workspace.

### Requirements

1. Install Android Studio (latest stable - Hedgehog or newer)
2. Install JDK 17 or 18
3. Install Android SDK (API 26 minimum, API 34 target)
4. Install Git
5. Create GitHub repository named `betting-executor-android`
6. Initialize with README.md, .gitignore (Android template), and LICENSE

### Files to Create

```
betting-executor-android/
├── README.md
├── .gitignore
└── LICENSE
```

### README.md Template

```markdown
# Betting Executor - Android

Automated betting execution engine using AccessibilityService.

## Requirements
- Android 8.0 (API 26) or higher
- Accessibility Service permission
- Battery optimization disabled

## Status
🚧 In Development - Stage 0 Complete

## Setup
(To be filled as we progress)

## Architecture
- MVVM with Clean Architecture
- Kotlin Coroutines
- Hilt for DI
- OkHttp for WebSocket

## License
MIT
```

### Verification Checklist

- [ ] Android Studio opens without errors
- [ ] Can create new Android project
- [ ] GitHub repository created and cloned locally
- [ ] Git commands work (git status, git commit)

### Definition of Done

- Repository exists on GitHub
- Local clone ready for development
- Android Studio can create projects
- README.md committed to main branch

**Command to Finish:** `"Finish Stage 0"`

---

## 📱 STAGE 1: Project Setup & Architecture

**Duration:** 4 hours (Monday-Tuesday Week 1)  
**Goal:** Android project created with proper architecture foundation

### Context

Create the Android project with all dependencies configured. This is the skeleton that everything else builds upon.

### Prerequisites

- Stage 0 completed
- Android Studio running

### Tasks

#### Task 1.1: Create Android Project (1 hour)

```
In Android Studio:
1. File > New > New Project
2. Select: Empty Views Activity
3. Configure:
   - Name: BettingExecutor
   - Package: com.betting.executor
   - Save location: [your cloned repo folder]
   - Language: Kotlin
   - Minimum SDK: API 26 (Android 8.0)
   - Build configuration: Kotlin DSL (build.gradle.kts)
4. Click Finish
5. Wait for Gradle sync
```

#### Task 1.2: Configure build.gradle.kts Files (1.5 hours)

**File:** `build.gradle.kts` (Project level)

```kotlin
plugins {
    id("com.android.application") version "8.2.0" apply false
    id("org.jetbrains.kotlin.android") version "1.9.20" apply false
    id("com.google.dagger.hilt.android") version "2.48" apply false
}
```

**File:** `build.gradle.kts` (Module: app)

```kotlin
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

#### Task 1.3: Create Package Structure (1 hour)

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

Create empty `.gitkeep` files in empty directories.

#### Task 1.4: Create Application Class (30 min)

**File:** `app/src/main/java/com/betting/executor/BettingApplication.kt`

```kotlin
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

#### Task 1.5: Update AndroidManifest.xml (30 min)

**File:** `app/src/main/AndroidManifest.xml`

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

#### Task 1.6: Create DI Module (30 min)

**File:** `app/src/main/java/com/betting/executor/di/AppModule.kt`

```kotlin
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

### Verification Checklist

- [ ] Project builds successfully (no Gradle errors)
- [ ] App installs on device/emulator
- [ ] App launches and shows MainActivity
- [ ] Timber logs appear in Logcat
- [ ] All packages created with correct structure
- [ ] Hilt compiles without errors

### Definition of Done

- App builds and runs
- Timber logging works
- Hilt dependency injection configured
- Package structure in place
- All files committed to Git

**Command to Finish:** `"Finish Stage 1"`

---

## 🎨 STAGE 2: Basic UI & Service Status

**Duration:** 3 hours (Wednesday Week 1)  
**Goal:** Main screen showing service status and controls

### Context

Build the user interface that shows system status and provides controls. This is what the user sees when they open the app.

### Prerequisites

- Stage 1 completed
- App builds and runs

### Tasks

#### Task 2.1: Create MainActivity Layout (1 hour)

**File:** `app/src/main/res/layout/activity_main.xml`

Create a layout with:

- Header title
- Service status card (shows if AccessibilityService is running)
- Connection status card (shows WebSocket connection)
- Test controls card (test buttons)
- Logs section (scrollable text view for debugging)

**Full layout code provided in technical documentation.**

Key components needed:

- MaterialCardView for status cards
- TextViews for status display
- Buttons for actions (Enable Service, Connect, Test Touch)
- ScrollView with TextView for logs

#### Task 2.2: Create MainActivity (1 hour)

**File:** `app/src/main/java/com/betting/executor/presentation/main/MainActivity.kt`

```kotlin
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

#### Task 2.3: Create MainViewModel (1 hour)

**File:** `app/src/main/java/com/betting/executor/presentation/main/MainViewModel.kt`

```kotlin
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

### Verification Checklist

- [ ] UI displays correctly on device
- [ ] Status cards show proper colors
- [ ] Buttons are visible and clickable
- [ ] "Enable Service" button opens Accessibility Settings
- [ ] Logs append to bottom of screen
- [ ] Auto-scroll works in log viewer
- [ ] Test button shows as disabled
- [ ] No crashes when rotating screen

### Definition of Done

- UI fully functional
- ViewModel manages state correctly
- Logs display with timestamps
- Status updates work
- Clean architecture (MVVM) implemented

**Command to Finish:** `"Finish Stage 2"`

---

## 🔧 STAGE 3: AccessibilityService Implementation

**Duration:** 5 hours (Thursday-Friday Week 1)  
**Goal:** AccessibilityService running and detectable

### Context

This is the core technology that enables touch injection. The AccessibilityService gives our app permission to interact with other apps on the device.

### Prerequisites

- Stage 2 completed
- UI shows service status

### Tasks

#### Task 3.1: Create AccessibilityService Class (2 hours)

**File:** `app/src/main/java/com/betting/executor/presentation/service/BettingAccessibilityService.kt`

```kotlin
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
    }
}
```

#### Task 3.2: Create Service Configuration (1 hour)

**File:** `app/src/main/res/xml/accessibility_service_config.xml`

Create the `xml` folder if it doesn't exist: `app/src/main/res/xml/`

```xml
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

**File:** `app/src/main/res/values/strings.xml` (add)

```xml
<string name="accessibility_service_description">
    Allows the app to automate betting operations by interacting with betting apps. 
    This service can read screen content and perform touch gestures on your behalf.
</string>
```

#### Task 3.3: Register Service in Manifest (30 min)

**File:** `app/src/main/AndroidManifest.xml` (add inside `<application>` tag)

```xml
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

#### Task 3.4: Create Utility to Check Service Status (1 hour)

**File:** `app/src/main/java/com/betting/executor/util/AccessibilityUtils.kt`

```kotlin
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

#### Task 3.5: Update MainViewModel (30 min)

Update `checkServiceStatus()` in `MainViewModel.kt`:

```kotlin
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

### Verification Checklist

- [ ] Service appears in Accessibility Settings
- [ ] Service description shows correctly
- [ ] Enabling service from settings works
- [ ] App detects when service is enabled
- [ ] Status card turns green when enabled
- [ ] Test button becomes enabled
- [ ] Logs show service connected message
- [ ] Service survives app restart

### Testing Steps

1. Build and install app
2. Open app
3. Click "Enable Service" button
4. Find "Betting Executor" in Accessibility list
5. Toggle it ON
6. Accept permission dialog
7. Return to app
8. Verify status shows "Running" in green

### Definition of Done

- AccessibilityService runs successfully
- App detects service status accurately
- UI updates when service enabled/disabled
- Service description visible in settings
- No crashes when toggling service

**Command to Finish:** `"Finish Stage 3"`

---

## 👆 STAGE 4: Random Touch Implementation

**Duration:** 4 hours (Monday-Tuesday Week 2)  
**Goal:** App can inject random touches on screen

### Context

Now that AccessibilityService is running, implement gesture injection. This proves we can tap anywhere on the screen programmatically.

### Prerequisites

- Stage 3 completed
- AccessibilityService enabled and running

### Tasks

#### Task 4.1: Create GestureExecutor (2 hours)

**File:** `app/src/main/java/com/betting/executor/domain/executor/GestureExecutor.kt`

```kotlin
package com.betting.executor.domain.executor

import android.accessibilityservice.AccessibilityService
import android.accessibilityservice.GestureDescription
import android.graphics.Path
import android.graphics.Point
import kotlinx.coroutines.suspendCancellableCoroutine
import timber.log.Timber
import kotlin.coroutines.resume
import kotlin.random.Random

class GestureExecutor(private val service: AccessibilityService) {

    suspend fun performRandomTouch(): Result<Point> = suspendCancellableCoroutine { continuation ->
        try {
            // Get screen dimensions
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

#### Task 4.2: Update BettingAccessibilityService (1 hour)

Add to `BettingAccessibilityService.kt`:

```kotlin
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

    // ... rest of the code remains the same
}
```

#### Task 4.3: Update MainViewModel