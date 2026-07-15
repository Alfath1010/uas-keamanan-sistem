<?php

/**
 * Reference: architecture.md §3.11 (Logging Policy) — logs MAY
 * contain UUIDs, error codes, timestamps, etc., but SHALL NEVER
 * contain plaintext, private keys, shared secrets, or session keys.
 * This config controls WHERE logs go; ensuring WHAT gets logged
 * stays policy-compliant is the responsibility of application code
 * (never log raw request bodies on ALS-protected routes, etc.).
 */
return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
    ],
];
