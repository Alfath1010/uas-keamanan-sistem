<?php

namespace App\Repositories;

use App\Models\PublicKey;
use App\Models\User;
use App\Repositories\Contracts\PublicKeyRepositoryInterface;

/**
 * Reference: architecture.md §3.5, db_design.md §5.4 (public_keys)
 *
 * Stores/retrieves PUBLIC key material only. This repository has no
 * knowledge of, and never receives, private key material (ADR-007).
 */
class PublicKeyRepository implements PublicKeyRepositoryInterface
{
    public function forUser(User $user): ?PublicKey
    {
        return $user->publicKey;
    }

    public function upsert(User $user, string $eccPublicKey, string $schnorrPublicKey): PublicKey
    {
        return PublicKey::updateOrCreate(
            ['user_id' => $user->id],
            [
                'ecc_public_key' => $eccPublicKey,
                'schnorr_public_key' => $schnorrPublicKey,
            ]
        );
    }
}
