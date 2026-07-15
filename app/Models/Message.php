<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reference: db_design.md §5.4 (messages)
 *
 * SECURITY: this model SHALL NEVER be given a `plaintext` attribute
 * or accessor that persists plaintext. `ciphertext_for_recipient` and
 * `ciphertext_for_sender` are both ECC-sealed (X25519 +
 * sodium_crypto_box_seal) copies of the same message body — sealed to
 * the recipient's and sender's own public keys respectively, since
 * sealed-box encryption is one-directional (see the messages
 * migration's docblock for the full rationale). `signature` is an
 * optional classical Schnorr (e, s) pair. All three are produced
 * entirely by the client before this model is ever touched
 * (architecture.md §3.5 — Repositories SHALL NOT perform encryption).
 */
class Message extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'ciphertext_for_recipient',
        'ciphertext_for_sender',
        'signature',
        'signed',
    ];

    protected function casts(): array
    {
        return [
            'signed' => 'boolean',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
