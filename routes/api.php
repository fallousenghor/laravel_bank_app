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

Route::group(['prefix' => 'v1'], function () {

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);


    Route::middleware(['throttle:api', 'rating'])->group(function () {
        Route::get('comptes', [CompteController::class, 'index']);
        Route::post('comptes', [CompteController::class, 'store']);
        Route::get('comptes/mine', [CompteController::class, 'mine']);
        Route::get('comptes/{id}', [CompteController::class, 'show']);
        Route::patch('comptes/{compteId}', [CompteController::class, 'update']);
        Route::post('comptes/{compteId}/bloquer', [CompteController::class, 'bloquer']);
        Route::delete('comptes/{id}', [CompteController::class, 'destroy']);

        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    });
});
