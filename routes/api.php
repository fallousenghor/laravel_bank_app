<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CompteController;
use App\Http\Controllers\API\TransactionController;

Route::get('/test', function() {
    return ['message' => 'API is working'];
});

Route::get('/health', function() {
    return response()->json(['status' => 'healthy'], 200);
});

// Versioning des routes API -> final: /api/v1/...
Route::prefix('v1')->group(function () {
    // Routes pour les utilisateurs
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);

    // Routes pour les comptes
    Route::middleware(['throttle:api', 'rating'])->group(function () {
    // Temporarily disable authentication for local/testing (no token required)
    // NOTE: Re-enable `->middleware('auth:sanctum')` when moving back to secured mode.
    Route::get('comptes', [CompteController::class, 'index']);
    Route::get('comptes/mine', [CompteController::class, 'mine']);
        Route::get('comptes/{id}', [CompteController::class, 'show']);
    });

    // Routes pour les transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
});
