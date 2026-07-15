<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;

interface MessageRepositoryInterface
{
    public function findByUuid(string $uuid): ?Message;

    /**
     * Encrypted messages belonging to a conversation, oldest first.
     */
    public function forConversation(Conversation $conversation): Collection;

    /**
     * Persist an already-encrypted (and optionally signed) message.
     * Ciphertext/signature SHALL already be produced by CryptoManager
     * before this is called (architecture.md §3.5).
     */
    public function create(array $attributes): Message;
}
