<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CompteController;
use App\Http\Controllers\API\TransactionController;

Route::get('/test', function() {
    return ['message' => 'API is working'];
});

Route::get('/health', function() {
    return response()->json(['status' => 'healthy'], 200);
});

// Passport password client health-check (no secret exposed)
Route::get('/health/passport-client', function () {
    try {
        $row = DB::table('oauth_clients')->where('password_client', 1)->first();
        return response()->json([
            'password_client_exists' => (bool) $row,
            'client_id' => $row->id ?? null,
        ], 200);
    } catch (\Throwable $e) {
        return response()->json(['error' => 'db_error', 'message' => $e->getMessage()], 500);
    }
});

Route::group(['prefix' => 'v1'], function () {

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show'])->whereUuid('id');

    // Authentication endpoints
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Protected routes require authentication and role resolution.
    Route::middleware(['throttle:api', 'rating', 'auth:api', 'role'])->group(function () {
        // Protected user lookup - only admin should be able to search users by tel or nci
        Route::get('users/find', [UserController::class, 'find']);
        Route::get('comptes', [CompteController::class, 'index']);
        Route::post('comptes', [CompteController::class, 'store']);
    Route::get('comptes/mine', [CompteController::class, 'mine']);
    // Find compte by numero (must be before the {id} route)
    Route::get('comptes/find', [CompteController::class, 'findByNumero']);
    Route::get('comptes/{id}', [CompteController::class, 'show'])->whereUuid('id');
        Route::patch('comptes/{compteId}', [CompteController::class, 'update']);
        Route::post('comptes/{compteId}/bloquer', [CompteController::class, 'bloquer']);
        Route::delete('comptes/{id}', [CompteController::class, 'destroy']);

        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    });
});
