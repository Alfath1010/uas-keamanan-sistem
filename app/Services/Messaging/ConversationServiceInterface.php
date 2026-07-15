<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reference: FR-003 (Conversation Management)
 */
interface ConversationServiceInterface
{
    public function listForUser(User $user): Collection;

    /**
     * @throws \App\Exceptions\Business\UserNotFoundException
     * @throws \App\Exceptions\Business\CannotConverseWithSelfException
     */
    public function create(User $initiator, string $recipientEmail): Conversation;

    /**
     * @throws \App\Exceptions\Business\ConversationNotFoundException
     * @throws \App\Exceptions\Business\NotConversationParticipantException
     */
    public function getForUser(User $user, string $conversationUuid): Conversation;
}
