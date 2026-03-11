# Betting Executor - Android

Automated betting execution engine using AccessibilityService.

## Requirements
- Android 8.0 (API 26) or higher
- Accessibility Service permission
- Battery optimization disabled

## Setup
1. Open `android/` directory in Android Studio
2. Wait for Gradle sync
3. Build and run on device/emulator

## Architecture
- **MVVM + Clean Architecture**
- **Hilt** for dependency injection
- **OkHttp** for WebSocket communication
- **Moshi** for JSON serialization
- **Timber** for logging
