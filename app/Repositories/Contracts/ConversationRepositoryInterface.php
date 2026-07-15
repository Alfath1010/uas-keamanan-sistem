<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ConversationRepositoryInterface
{
    public function findByUuid(string $uuid): ?Conversation;

    /**
     * All conversations the given user participates in.
     */
    public function forUser(User $user): Collection;

    public function create(User $initiator, User $recipient): Conversation;

    /**
     * Whether the given user is a participant of the given conversation.
     * Used by the Business Layer for authorization (FT-CONV-003, MSG002).
     */
    public function hasMember(Conversation $conversation, User $user): bool;

    /**
     * Existing one-to-one conversation between two users, if any.
     */
    public function findBetween(User $a, User $b): ?Conversation;
}
