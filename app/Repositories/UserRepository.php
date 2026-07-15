<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * Reference: architecture.md §3.5 — Repositories encapsulate database
 * access only. No password hashing, validation, or business rules
 * belong here; that is the responsibility of the Business Layer
 * (e.g. AuthService).
 */
class UserRepository implements UserRepositoryInterface
{
    public function findByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $attributes): User
    {
        return User::create($attributes);
    }
}
