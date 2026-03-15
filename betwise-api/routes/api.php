<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\MatchupController;
use App\Http\Controllers\Api\V1\TeamController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Teams
    Route::apiResource('teams', TeamController::class);
    Route::post('teams/{team}/accounts', [TeamController::class, 'assignAccounts']);
    Route::get('teams/{team}/stats', [TeamController::class, 'stats']);

    // Accounts
    Route::apiResource('accounts', AccountController::class);
    Route::post('accounts/{account}/balance', [AccountController::class, 'updateBalance']);

    // Matchups
    Route::post('matchups/auto-generate', [MatchupController::class, 'autoGenerate']);
    Route::apiResource('matchups', MatchupController::class)->only(['index', 'store', 'show', 'update']);
});
