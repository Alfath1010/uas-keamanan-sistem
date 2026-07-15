<?php

use App\Http\Controllers\Api\V1\AlsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\PublicKeyController;
use App\Http\Controllers\Api\V1\SchnorrParameterController;
use Illuminate\Support\Facades\Route;

/**
 * Reference: api_spec.md (all of Chapter 6)
 *
 * Middleware layering follows architecture.md §3.8:
 *   auth:sanctum (Authentication Middleware) -> als (ALS Middleware,
 *   only on the two endpoints api_spec.md explicitly marks
 *   "ALS Required") -> Controller
 */
Route::prefix('v1')->group(function () {
    // 6.3 Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // 6.4 Application Layer Security
        Route::post('/als/handshake', [AlsController::class, 'handshake']);
        Route::post('/als/renew', [AlsController::class, 'renew']);

        // 6.5 Conversations
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::post('/conversations', [ConversationController::class, 'store']);
        Route::get('/conversations/{conversation_uuid}', [ConversationController::class, 'show']);

        // 6.6 Messages — ALS Required per api_spec.md §6.6
        Route::middleware('als')->group(function () {
            Route::post('/messages', [MessageController::class, 'store']);
            Route::get('/messages/{conversation_uuid}', [MessageController::class, 'index']);
        });

        // 6.7 Public Key Management
        Route::get('/users/{user_uuid}/keys', [PublicKeyController::class, 'show']);
        Route::post('/users/keys', [PublicKeyController::class, 'store']);
    });

    // 6.8 Schnorr Parameters — public, no auth required (matches the
    // spec's plain GET with no stated auth requirement, and the
    // parameters are non-secret by design).
    Route::get('/schnorr/parameters', [SchnorrParameterController::class, 'index']);
});
