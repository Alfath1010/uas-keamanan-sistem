<?php

use Illuminate\Support\Facades\Route;

/**
 * These routes only render Blade shells — all actual data flows through
 * the existing JSON API (routes/api.php) via client-side JS (public/js/).
 * Authentication is enforced client-side (see layouts/app.blade.php's
 * redirect-if-logged-out script) since the API itself uses Sanctum bearer
 * tokens, not server-side sessions, for this demo.
 */
Route::get('/', fn () => redirect('/conversations'));

Route::get('/register', fn () => view('auth.register'));
Route::get('/login', fn () => view('auth.login'));

Route::get('/keys', fn () => view('keys.index'));

Route::get('/conversations', fn () => view('conversations.index'));
Route::get('/conversations/{conversation_uuid}', fn (string $conversation_uuid) => view(
    'conversations.show',
    ['conversationUuid' => $conversation_uuid],
));
