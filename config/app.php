<?php

return [
    'name' => env('APP_NAME', 'SecureMe'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => env('APP_LOCALE', 'id'),
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',

    /**
     * APP_KEY is used, among other things, by the 'encrypted' cast on
     * ALSSession::session_key (db_design.md's "Implementation Note").
     * Generate via `php artisan key:generate`.
     */
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],
];
