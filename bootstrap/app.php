<?php

use App\Exceptions\Business\BusinessException;
use App\Exceptions\Crypto\CryptographicException;
use App\Http\Middleware\DecryptAlsPayload;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Reference: architecture.md §3.8 — 'als' runs strictly after
        // 'auth:sanctum' wherever both are applied to a route, per
        // routes/api.php's nested groups.
        $middleware->alias([
            'als' => DecryptAlsPayload::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /**
         * Reference: architecture.md §3.10 (Error Handling
         * Architecture), api_spec.md §6.2 (Standard API Response),
         * ADR-004/ADR-005 — every error response, regardless of
         * origin, SHALL use the standard envelope with a
         * machine-readable error_code. No endpoint should ever fall
         * through to Laravel's default HTML/plain error pages.
         */
        $exceptions->render(function (CryptographicException $e, Request $request) {
            return new JsonResponse($e->toResponseArray(), $e->statusCode());
        });

        $exceptions->render(function (BusinessException $e, Request $request) {
            return new JsonResponse($e->toResponseArray(), $e->statusCode());
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Validasi gagal.',
                'error_code' => 'VAL001',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
                'error_code' => 'AUTH001',
                'errors' => [],
            ], 401);
        });
    })->create();
