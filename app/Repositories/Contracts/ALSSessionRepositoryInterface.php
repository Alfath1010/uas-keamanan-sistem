<?php

namespace App\Repositories\Contracts;

use App\Models\ALSSession;
use App\Models\User;

interface ALSSessionRepositoryInterface
{
    public function findByUuid(string $uuid): ?ALSSession;

    /**
     * The user's most recently created, still-unexpired session, if any.
     */
    public function activeForUser(User $user): ?ALSSession;

    public function create(User $user, string $sessionKey, \DateTimeInterface $expiresAt): ALSSession;

    /**
     * Remove expired sessions. Used by the scheduled maintenance job
     * described in db_design.md §5.8.
     */
    public function purgeExpired(): int;
}
