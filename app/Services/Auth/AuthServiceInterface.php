<?php

namespace App\Services\Auth;

use App\Models\User;

/**
 * Reference: FR-001 (User Registration), FR-002 (User Authentication)
 *
 * Business Layer service — coordinates validation-adjacent business
 * rules (uniqueness, credential checking) and delegates persistence
 * to UserRepositoryInterface. Password hashing itself is handled by
 * the User model's 'hashed' cast (db_design.md notes), not here.
 */
interface AuthServiceInterface
{
    /**
     * @throws \App\Exceptions\Business\EmailAlreadyExistsException
     */
    public function register(string $name, string $email, string $password): User;

    /**
     * @return array{user: User, token: string}
     *
     * @throws \App\Exceptions\Business\InvalidCredentialsException
     */
    public function login(string $email, string $password): array;

    public function logout(User $user): void;
}
