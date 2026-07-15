<?php

namespace Tests\Support;

use App\Models\User;

/**
 * BUG HISTORY: tests that make multiple HTTP calls as DIFFERENT users
 * within a single test method (e.g. Alice creates a conversation,
 * then Eve tries to access it) were silently authenticating every
 * request as whichever user's token was used FIRST.
 *
 * Root cause: Laravel's AuthManager caches resolved guard instances
 * for the lifetime of the test's application container, which is NOT
 * rebuilt between $this->postJson()/$this->getJson() calls within
 * the same test method (unlike real requests, which each get a fresh
 * process). Sanctum's RequestGuard additionally caches the resolved
 * user on itself once resolved. Net effect: the second HTTP call in a
 * test reused the FIRST call's already-resolved user, ignoring the
 * new Bearer token entirely — a false pass/fail depending on which
 * user happened to be used first, not real authorization behavior.
 *
 * Fix: forget cached guards before generating a token for the next
 * request, forcing Sanctum to re-resolve the user from that request's
 * actual Authorization header.
 */
trait ActsAsUsers
{
    protected function tokenFor(User $user): string
    {
        $this->app['auth']->forgetGuards();

        return $user->createToken('api')->plainTextToken;
    }
}
