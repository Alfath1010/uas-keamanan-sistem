<?php

namespace App\Services\Messaging;

use App\Models\Message;
use App\Models\User;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Services\CryptoManagerInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reference: FR-005, FR-006, architecture.md §3.4 (Services/Messaging)
 */
class MessageService implements MessageServiceInterface
{
    public function __construct(
        private readonly MessageRepositoryInterface $messages,
        private readonly ConversationServiceInterface $conversations,
        private readonly CryptoManagerInterface $crypto,
    ) {}

    public function send(
        User $sender,
        string $conversationUuid,
        string $ciphertextForRecipient,
        string $ciphertextForSender,
        ?string $signature,
        bool $signed,
    ): Message {
        // Authorization: throws ConversationNotFoundException /
        // NotConversationParticipantException as appropriate.
        $conversation = $this->conversations->getForUser($sender, $conversationUuid);

        $recipient = $conversation->members
            ->reject(fn (User $member) => $member->id === $sender->id)
            ->first();

        // Courtesy check only — does not perform any encryption
        // (see CryptoManagerInterface::ensureRecipientEncryptable doc).
        $this->crypto->ensureRecipientEncryptable($recipient);

        return $this->messages->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'ciphertext_for_recipient' => $ciphertextForRecipient,
            'ciphertext_for_sender' => $ciphertextForSender,
            'signature' => $signature,
            'signed' => $signed,
        ]);
    }

    public function listForConversation(User $user, string $conversationUuid): Collection
    {
        $conversation = $this->conversations->getForUser($user, $conversationUuid);

        return $this->messages->forConversation($conversation);
    }
}
