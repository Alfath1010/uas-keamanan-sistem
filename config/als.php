<?php

/**
 * Reference: crypto_design.md §4.2.6 (Session Expiration),
 * SEC-006 ("ALS sessions SHALL expire automatically after a
 * configurable lifetime").
 */
return [
    'session_lifetime_minutes' => env('ALS_SESSION_LIFETIME_MINUTES', 15),
];
