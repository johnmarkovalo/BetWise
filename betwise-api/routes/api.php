<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\DeviceIpController;
use App\Http\Controllers\Api\V1\IpConflictController;
use App\Http\Controllers\Api\V1\IpConflictRuleController;
use App\Http\Controllers\Api\V1\MatchupController;
use App\Http\Controllers\Api\V1\ProxyController;
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

    // IP Conflict Detection
    Route::post('ip-conflicts/check', [IpConflictController::class, 'check']);

    // Device IP Rotation
    Route::post('devices/{device}/ip/rotate', [DeviceIpController::class, 'rotate']);

    // IP Conflict Rules CRUD
    Route::apiResource('ip-conflict-rules', IpConflictRuleController::class);

    // Proxy Pool CRUD
    Route::apiResource('proxies', ProxyController::class);
});
