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

    companion object {
        private const val EDGE_MARGIN = 100
        private const val DEFAULT_TAP_DURATION = 50L
    }

    suspend fun performRandomTouch(): Result<Point> {
        val metrics = service.resources.displayMetrics
        val screenWidth = metrics.widthPixels
        val screenHeight = metrics.heightPixels

        val x = Random.nextInt(EDGE_MARGIN, screenWidth - EDGE_MARGIN).toFloat()
        val y = Random.nextInt(EDGE_MARGIN, screenHeight - EDGE_MARGIN).toFloat()

        Timber.d("Attempting random tap at (%.0f, %.0f) on %dx%d screen", x, y, screenWidth, screenHeight)

        return performTapAt(x, y).map { Point(x.toInt(), y.toInt()) }
    }

    suspend fun performTapAt(x: Float, y: Float, duration: Long = DEFAULT_TAP_DURATION): Result<Unit> {
        Timber.d("Attempting tap at (%.0f, %.0f) duration=%dms", x, y, duration)

        val path = Path().apply { moveTo(x, y) }
        val stroke = GestureDescription.StrokeDescription(path, 0, duration)
        val gesture = GestureDescription.Builder()
            .addStroke(stroke)
            .build()

        return suspendCancellableCoroutine { continuation ->
            val callback = object : AccessibilityService.GestureResultCallback() {
                override fun onCompleted(gestureDescription: GestureDescription?) {
                    Timber.d("Gesture completed successfully at (%.0f, %.0f)", x, y)
                    if (continuation.isActive) {
                        continuation.resume(Result.success(Unit))
                    }
                }

                override fun onCancelled(gestureDescription: GestureDescription?) {
                    Timber.w("Gesture cancelled at (%.0f, %.0f)", x, y)
                    if (continuation.isActive) {
                        continuation.resume(Result.failure(GestureException("Gesture cancelled")))
                    }
                }
            }

            val dispatched = service.dispatchGesture(gesture, callback, null)
            if (!dispatched) {
                Timber.e("Failed to dispatch gesture at (%.0f, %.0f)", x, y)
                if (continuation.isActive) {
                    continuation.resume(Result.failure(GestureException("Failed to dispatch gesture")))
                }
            }
        }
    }

    class GestureException(message: String) : Exception(message)
}
