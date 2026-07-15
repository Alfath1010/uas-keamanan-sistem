<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reference: db_design.md §5.4 (public_keys)
 *
 * Stores ONLY public cryptographic material:
 *  - ecc_public_key: X25519 public key, base64-encoded (crypto_design.md §4.3.2)
 *  - schnorr_public_key: classical discrete-log Schnorr public key
 *    v = alpha^-x mod p, stored as a decimal string (crypto_design.md §4.4.3)
 *
 * Private keys are never accepted, stored, or referenced by this model
 * (FR-009, ADR-007, SEC-005). No UUID column: public keys are always
 * accessed via the owning user's UUID (GET /users/{user_uuid}/keys).
 */
class PublicKey extends Model
{
    protected $fillable = [
        'user_id',
        'ecc_public_key',
        'schnorr_public_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
