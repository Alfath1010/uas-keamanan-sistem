<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Reference: db_design.md §5.4 (users), §5.10 (Implementation Mapping)
 *
 * Bearer token authentication is provided by Laravel Sanctum, per
 * api_spec.md §6.1 ("Authentication: Laravel Bearer Token").
 *
 * This model encapsulates relationships only. Cryptographic
 * operations SHALL NOT be implemented here (db_design.md §5.10,
 * architecture.md §3.5 — Repositories/Models SHALL NOT perform
 * encryption).
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuid;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Conversations this user participates in.
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'conversation_members'
        );
    }

    /**
     * Membership rows for this user (used by ConversationRepository
     * to check participation without loading full conversations).
     */
    public function conversationMemberships(): HasMany
    {
        return $this->hasMany(ConversationMember::class);
    }

    /**
     * Messages sent by this user.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * The user's uploaded public key material (ECC + Schnorr).
     * Private keys never exist server-side (FR-009, ADR-007).
     */
    public function publicKey(): HasOne
    {
        return $this->hasOne(PublicKey::class);
    }

    /**
     * Active/expired ALS sessions belonging to this user.
     */
    public function alsSessions(): HasMany
    {
        return $this->hasMany(ALSSession::class);
    }
}
