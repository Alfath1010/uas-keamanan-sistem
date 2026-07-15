<?php

namespace App\Repositories;

use App\Models\ALSSession;
use App\Models\User;
use App\Repositories\Contracts\ALSSessionRepositoryInterface;
use Illuminate\Support\Facades\Date;

/**
 * Reference: architecture.md §3.5, db_design.md §5.4 (als_sessions),
 * db_design.md §5.8 (Data Retention Policy)
 */
class ALSSessionRepository implements ALSSessionRepositoryInterface
{
    public function findByUuid(string $uuid): ?ALSSession
    {
        return ALSSession::where('uuid', $uuid)->first();
    }

    public function activeForUser(User $user): ?ALSSession
    {
        return $user->alsSessions()
            ->where('expires_at', '>', Date::now())
            ->latest('created_at')
            ->first();
    }

    public function create(User $user, string $sessionKey, \DateTimeInterface $expiresAt): ALSSession
    {
        return ALSSession::create([
            'user_id' => $user->id,
            'session_key' => $sessionKey,
            'expires_at' => $expiresAt,
        ]);
    }

    public function purgeExpired(): int
    {
        return ALSSession::where('expires_at', '<=', Date::now())->delete();
    }
}
