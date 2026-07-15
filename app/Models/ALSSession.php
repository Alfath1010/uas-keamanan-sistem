<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reference: db_design.md §5.4 (als_sessions)
 *
 * Represents one ALS session established via the X25519 ECDH
 * handshake (crypto_design.md §4.2). `session_key` holds the
 * HKDF-derived AES-256-GCM key.
 *
 * The `encrypted` cast wraps the value with Laravel's encryption
 * facilities (AES-256-CBC under APP_KEY) transparently on
 * read/write, satisfying the "Implementation Note" in db_design.md
 * and SEC-007 (no sensitive material in plaintext at rest). This is
 * a second, independent layer of protection on top of the session
 * key's own secrecy — a DB compromise alone does not yield usable
 * session keys without APP_KEY.
 *
 * No `updated_at`: sessions are immutable once created; renewal
 * (ALS-002 / POST /als/renew) creates a new session row rather than
 * mutating an existing one, so only `created_at` is meaningful.
 */
class ALSSession extends Model
{
    use HasFactory;
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table = 'als_sessions';

    protected $fillable = [
        'user_id',
        'session_key',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'session_key' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
