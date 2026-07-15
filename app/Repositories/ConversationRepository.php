<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reference: architecture.md §3.5, db_design.md §5.4
 * (conversations, conversation_members)
 */
class ConversationRepository implements ConversationRepositoryInterface
{
    public function findByUuid(string $uuid): ?Conversation
    {
        return Conversation::where('uuid', $uuid)->first();
    }

    public function forUser(User $user): Collection
    {
        return $user->conversations()->with('members')->get();
    }

    public function create(User $initiator, User $recipient): Conversation
    {
        return DB::transaction(function () use ($initiator, $recipient) {
            $conversation = Conversation::create();
            $conversation->members()->attach([$initiator->id, $recipient->id]);

            return $conversation;
        });
    }

    public function hasMember(Conversation $conversation, User $user): bool
    {
        return $conversation->conversationMembers()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function findBetween(User $a, User $b): ?Conversation
    {
        return Conversation::whereHas('members', fn ($q) => $q->where('users.id', $a->id))
            ->whereHas('members', fn ($q) => $q->where('users.id', $b->id))
            ->first();
    }
}
