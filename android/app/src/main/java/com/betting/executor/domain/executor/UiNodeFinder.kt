package com.betting.executor.domain.executor

import android.util.DisplayMetrics
import timber.log.Timber

/**
 * Converts relative screen positions (percentages) to absolute pixel coordinates.
 * Provider adapters define element locations as TapTargets with relative x/y,
 * and this calculator resolves them to actual screen pixels.
 */
class ScreenCoordinateCalculator(private val displayMetrics: DisplayMetrics) {

    val screenWidth: Int get() = displayMetrics.widthPixels
    val screenHeight: Int get() = displayMetrics.heightPixels

    /**
     * A tap target defined as a relative position on screen.
     * @param label Human-readable name for logging
     * @param relativeX X position as fraction of screen width (0.0 = left, 1.0 = right)
     * @param relativeY Y position as fraction of screen height (0.0 = top, 1.0 = bottom)
     */
    data class TapTarget(
        val label: String,
        val relativeX: Float,
        val relativeY: Float
    ) {
        init {
            require(relativeX in 0f..1f) { "relativeX must be between 0 and 1, got $relativeX" }
            require(relativeY in 0f..1f) { "relativeY must be between 0 and 1, got $relativeY" }
        }
    }

    data class ScreenPoint(
        val x: Float,
        val y: Float,
        val label: String
    )

    fun resolve(target: TapTarget): ScreenPoint {
        val x = target.relativeX * screenWidth
        val y = target.relativeY * screenHeight

        Timber.d(
            "Resolved \"%s\": relative(%.2f, %.2f) → absolute(%.0f, %.0f) on %dx%d screen",
            target.label, target.relativeX, target.relativeY, x, y, screenWidth, screenHeight
        )

        return ScreenPoint(x, y, target.label)
    }

    fun resolveAll(targets: List<TapTarget>): List<ScreenPoint> {
        return targets.map { resolve(it) }
    }
}
