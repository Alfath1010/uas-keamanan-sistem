<?php

namespace App\Repositories\Contracts;

use App\Models\PublicKey;
use App\Models\User;

interface PublicKeyRepositoryInterface
{
    public function forUser(User $user): ?PublicKey;

    /**
     * Create or replace the given user's public keys (FT-KEY-002).
     */
    public function upsert(User $user, string $eccPublicKey, string $schnorrPublicKey): PublicKey;
}
