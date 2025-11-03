<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CompteController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\AuthController;

Route::get('/test', function() {
    return ['message' => 'API is working'];
});

Route::get('/health', function() {
    return response()->json(['status' => 'healthy'], 200);
});

Route::group(['prefix' => 'v1'], function () {

    // Auth routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);
    });

    // User routes - require authentication and proper authorization
    Route::middleware('auth:api')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::patch('/users/{id}', [UserController::class, 'update']);
    });


    // Comptes endpoints now require authentication (token) — no more admin_id/user_id query fallbacks.
    Route::group(['prefix' => 'senghorfallou/v1'], function () {
        Route::middleware(['throttle:api', 'rating', 'auth:api'])->group(function () {
            Route::get('comptes', [CompteController::class, 'index']);
            Route::post('comptes', [CompteController::class, 'store']);
            Route::get('comptes/mine', [CompteController::class, 'mine']);
            // Recherche d'un client (par téléphone ou NCI) — réservé aux admins
            Route::get('clients/search', [CompteController::class, 'searchClient']);
            // Show by account number (numero) — route param named 'numero'
            Route::get('comptes/{numero}', [CompteController::class, 'show']);
            Route::patch('comptes/{compteId}', [CompteController::class, 'update']);
            Route::post('comptes/{compteId}/bloquer', [CompteController::class, 'bloquer']);
            Route::delete('comptes/{id}', [CompteController::class, 'destroy']);

            Route::get('/transactions', [TransactionController::class, 'index']);
            Route::post('/transactions', [TransactionController::class, 'store']);
            Route::get('/transactions/{id}', [TransactionController::class, 'show']);
        });
    });
});
