<?php

namespace App\Services\Messaging;

use App\Exceptions\Business\CannotConverseWithSelfException;
use App\Exceptions\Business\ConversationNotFoundException;
use App\Exceptions\Business\NotConversationParticipantException;
use App\Exceptions\Business\UserNotFoundException;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reference: FR-003, FT-CONV-001/002/003
 */
class ConversationService implements ConversationServiceInterface
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function listForUser(User $user): Collection
    {
        return $this->conversations->forUser($user);
    }

    public function create(User $initiator, string $recipientEmail): Conversation
    {
        $recipient = $this->users->findByEmail($recipientEmail);

        if ($recipient === null) {
            throw new UserNotFoundException();
        }

        if ($recipient->id === $initiator->id) {
            throw new CannotConverseWithSelfException();
        }

        // Reuse an existing one-to-one conversation rather than
        // creating duplicates for the same pair of users.
        $existing = $this->conversations->findBetween($initiator, $recipient);

        if ($existing !== null) {
            return $existing;
        }

        return $this->conversations->create($initiator, $recipient);
    }

    public function getForUser(User $user, string $conversationUuid): Conversation
    {
        $conversation = $this->conversations->findByUuid($conversationUuid);

        if ($conversation === null) {
            throw new ConversationNotFoundException();
        }

        if (! $this->conversations->hasMember($conversation, $user)) {
            throw new NotConversationParticipantException();
        }

        return $conversation;
    }
}
