<?php

return [
    'stateful' => [],

    /**
     * BUG HISTORY: this was previously (incorrectly) set to
     * ['sanctum']. Sanctum's Guard::__invoke() uses this value as a
     * fallback SESSION guard to check for cookie-based SPA auth after
     * bearer-token lookup fails — pointing it back at 'sanctum'
     * itself caused Auth::guard('sanctum') to resolve Sanctum's own
     * RequestGuard again inside its own resolution, an infinite
     * mutual recursion between RequestGuard->user() and
     * Sanctum\Guard::__invoke() (stack overflow / OOM in tests).
     */
    'guard' => ['web'],

    'expiration' => null,

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
