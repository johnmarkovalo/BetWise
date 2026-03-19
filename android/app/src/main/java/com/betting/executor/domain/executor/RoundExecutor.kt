package com.betting.executor.domain.executor

import com.betting.executor.presentation.service.BettingAccessibilityService
import timber.log.Timber

/**
 * Executes a single bet by injecting touch gestures via the AccessibilityService.
 * In the MVP, this performs a tap at the provider's known bet button coordinates.
 * In later stages, ProviderAdapters will supply the correct coordinates per provider.
 */
class RoundExecutor {

    enum class BetOutcome {
        WIN,
        LOSS,
        TIE,
        PUSH,
        UNKNOWN,
        ERROR
    }

    /**
     * Execute a bet placement via touch injection.
     *
     * @param provider The betting provider identifier (e.g., "evolution", "pragmatic")
     * @param side The bet side ("banker", "player", "tie")
     * @param amount The bet amount
     * @return Result containing the bet outcome on success, or error on failure
     */
    suspend fun execute(
        provider: String,
        side: String,
        amount: Double
    ): Result<BetOutcome> {
        Timber.d("RoundExecutor: executing bet provider=%s, side=%s, amount=%.2f", provider, side, amount)

        val service = BettingAccessibilityService.getInstance()
            ?: return Result.failure(IllegalStateException("AccessibilityService not available"))

        val executor = service.getGestureExecutor()
            ?: return Result.failure(IllegalStateException("GestureExecutor not initialized"))

        val calculator = ScreenCoordinateCalculator(service.resources.displayMetrics)

        // Resolve tap target based on provider and side
        // MVP: Use relative coordinates that provider adapters will supply in Stage 9
        val target = resolveTarget(provider, side, calculator)

        Timber.d(
            "RoundExecutor: tapping at (%.0f, %.0f) for %s/%s",
            target.x, target.y, provider, side
        )

        // Execute the tap
        val tapResult = executor.performTapAt(target.x, target.y)

        return tapResult.fold(
            onSuccess = {
                Timber.d("RoundExecutor: tap successful for %s/%s", provider, side)
                // In MVP, we can't detect the actual outcome from the UI yet.
                // That will come with provider adapters in Stage 9.
                Result.success(BetOutcome.UNKNOWN)
            },
            onFailure = { error ->
                Timber.e(error, "RoundExecutor: tap failed for %s/%s", provider, side)
                Result.failure(error)
            }
        )
    }

    /**
     * Resolve screen coordinates for a bet target.
     * MVP implementation uses hardcoded relative positions.
     * Stage 9 will replace this with ProviderAdapter.getBetTarget().
     */
    private fun resolveTarget(
        provider: String,
        side: String,
        calculator: ScreenCoordinateCalculator
    ): ScreenCoordinateCalculator.ScreenPoint {
        // Default relative positions for common betting sides.
        // These are placeholder positions that provider adapters will override.
        val target = when (side.lowercase()) {
            "player" -> ScreenCoordinateCalculator.TapTarget(
                label = "$provider/player",
                relativeX = 0.25f,
                relativeY = 0.7f
            )
            "banker" -> ScreenCoordinateCalculator.TapTarget(
                label = "$provider/banker",
                relativeX = 0.75f,
                relativeY = 0.7f
            )
            "tie" -> ScreenCoordinateCalculator.TapTarget(
                label = "$provider/tie",
                relativeX = 0.5f,
                relativeY = 0.7f
            )
            else -> ScreenCoordinateCalculator.TapTarget(
                label = "$provider/$side",
                relativeX = 0.5f,
                relativeY = 0.5f
            )
        }

        return calculator.resolve(target)
    }
}
