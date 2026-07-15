<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reference: architecture.md §3.5, db_design.md §5.4 (messages)
 *
 * SECURITY: this repository SHALL NEVER receive or persist plaintext.
 * Callers (MessageService via CryptoManager) are responsible for
 * ensuring `ciphertext` is already ECC-sealed before create() is
 * invoked (db_design.md §5.9, FR-006).
 */
class MessageRepository implements MessageRepositoryInterface
{
    public function findByUuid(string $uuid): ?Message
    {
        return Message::where('uuid', $uuid)->first();
    }

    public function forConversation(Conversation $conversation): Collection
    {
        return $conversation->messages()
            ->orderBy('created_at')
            ->get();
    }

    public function create(array $attributes): Message
    {
        return Message::create($attributes);
    }
}
