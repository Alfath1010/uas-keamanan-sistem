<?php

namespace App\Services\Messaging;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reference: FR-005, FR-006, FT-MSG-001..004
 *
 * IMPORTANT: this service never sees plaintext. $ciphertext and
 * $signature are opaque, already-produced-client-side values
 * (see CryptoManagerInterface's scope note) — this service's job is
 * authorization (is the sender a participant?), a recipient-key
 * courtesy check, and storage, nothing more.
 */
interface MessageServiceInterface
{
    /**
     * @param  string  $ciphertextForRecipient  Sealed to the recipient's ECC public key.
     * @param  string  $ciphertextForSender  Sealed to the sender's OWN ECC public key, so
     *   the sender can later read their own sent messages — sealed-box encryption is
     *   one-directional, so the recipient-sealed copy alone is unreadable by the sender.
     *
     * @throws \App\Exceptions\Business\ConversationNotFoundException
     * @throws \App\Exceptions\Business\NotConversationParticipantException
     * @throws \App\Exceptions\Crypto\RecipientPublicKeyUnavailableException
     */
    public function send(
        User $sender,
        string $conversationUuid,
        string $ciphertextForRecipient,
        string $ciphertextForSender,
        ?string $signature,
        bool $signed,
    ): Message;

    /**
     * @throws \App\Exceptions\Business\ConversationNotFoundException
     * @throws \App\Exceptions\Business\NotConversationParticipantException
     */
    public function listForConversation(User $user, string $conversationUuid): Collection;
}
