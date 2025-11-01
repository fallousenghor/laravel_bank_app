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

/**
 * Internal test route to trigger a mail or the ClientCreated event.
 * Protect this endpoint by setting INTERNAL_TEST_KEY in your environment
 * and passing it as header 'X-Internal-Key' or query param 'key'.
 */
Route::post('/internal/test-mail', function(Request $request) {
    $secret = env('INTERNAL_TEST_KEY');
    $provided = $request->header('X-Internal-Key') ?? $request->get('key');

    if (!$secret || $provided !== $secret) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $to = $request->get('to', 'fgallas345@gmail.com');
    $subject = $request->get('subject', 'App test mail');
    $body = $request->get('body', 'Test mail from internal route');

    // If 'event' param provided, dispatch ClientCreated event for a user with this email
    if ($request->get('event')) {
        $user = \App\Models\User::where('email', $to)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        event(new \App\Events\ClientCreated($user, 'TempPass123', '000000'));
        return response()->json(['status' => 'event dispatched']);
    }

    // Otherwise send a raw mail
    try {
        Mail::raw($body, function($m) use ($to, $subject) {
            $m->to($to)->subject($subject);
        });
        return response()->json(['status' => 'mail sent']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'send_failed', 'message' => $e->getMessage()], 500);
    }
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
            Route::get('/transactions/{id}', [TransactionController::class, 'show']);
        });
    });
});
