<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CompteController;
use App\Http\Controllers\API\TransactionController;

// Routes pour les utilisateurs
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);

// Routes pour les comptes
Route::apiResource('comptes', CompteController::class);

// Routes pour les transactions
Route::get('/transactions', [TransactionController::class, 'index']);
Route::get('/transactions/{id}', [TransactionController::class, 'show']);
