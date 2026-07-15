<?php

namespace Tests\Feature\Messaging;

use App\Models\User;
use App\Services\Security\ECC\ECCService;
use App\Services\Security\Schnorr\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ActsAsUsers;
use Tests\Support\FakeSchnorrParameterProvider;
use Tests\Support\InteractsWithAls;
use Tests\Support\WithFakeSchnorrParameters;
use Tests\TestCase;

/**
 * Reference: testing_spec.md §7.5 (Messaging, FT-MSG-001..004),
 * FR-005, FR-006
 *
 * These tests act as the "client": they perform a real ALS handshake
 * against the live endpoint, then encrypt/decrypt every request and
 * response for the ALS-protected message endpoints, exactly as a real
 * client would (see InteractsWithAls).
 */
class MessageTest extends TestCase
{
    use RefreshDatabase;
    use ActsAsUsers;
    use InteractsWithAls;
    use WithFakeSchnorrParameters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeSchnorrParameters();
    }

    /**
     * Uploads a valid ECC + Schnorr public key pair for $user so
     * they're a legitimate messaging recipient (FR-008).
     */
    private function givePublicKeys(User $user): void
    {
        $ecc = (new ECCService())->generateKeyPair();
        $schnorr = (new SignatureService(new FakeSchnorrParameterProvider()))->generateKeyPair();

        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($user))
            ->postJson('/api/v1/users/keys', [
                'ecc_public_key' => $ecc['public_key'],
                'schnorr_public_key' => $schnorr['public_key'],
            ])->assertOk();
    }

    private function createConversation(User $initiator, User $recipient): string
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($initiator))
            ->postJson('/api/v1/conversations', ['recipient_email' => $recipient->email])
            ->json('data.uuid');
    }

    #[Test]
    public function ft_msg_001_a_user_can_send_an_encrypted_message(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->givePublicKeys($bob);

        $conversationUuid = $this->createConversation($alice, $bob);
        $token = $this->tokenFor($alice);
        $session = $this->establishAlsSession($alice, $token);

        $response = $this->alsPostJson('/api/v1/messages', [
            'conversation_uuid' => $conversationUuid,
            'recipient_uuid' => $bob->uuid,
            'ciphertext_for_recipient' => 'opaque-ciphertext-for-bob',
            'ciphertext_for_sender' => 'opaque-ciphertext-for-alice',
            'signature' => null,
            'signed' => false,
        ], $token, $session);

        $response->assertCreated();
        $decoded = json_decode($response->getContent(), true);
        $this->assertTrue($decoded['success']);
        $this->assertArrayHasKey('message_uuid', $decoded['data']);

        $this->assertDatabaseHas('messages', ['conversation_id' => \App\Models\Conversation::where('uuid', $conversationUuid)->first()->id]);
    }

    #[Test]
    public function ft_msg_002_a_user_can_retrieve_encrypted_messages(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->givePublicKeys($bob);

        $conversationUuid = $this->createConversation($alice, $bob);
        $aliceToken = $this->tokenFor($alice);
        $session = $this->establishAlsSession($alice, $aliceToken);

        $this->alsPostJson('/api/v1/messages', [
            'conversation_uuid' => $conversationUuid,
            'recipient_uuid' => $bob->uuid,
            'ciphertext_for_recipient' => 'opaque-ciphertext-for-bob',
            'ciphertext_for_sender' => 'opaque-ciphertext-for-alice',
            'signature' => null,
            'signed' => false,
        ], $aliceToken, $session)->assertCreated();

        // Alice (the sender) should see HER OWN sealed copy when listing —
        // this is the whole point of storing two ciphertexts: sealed-box
        // encryption is one-directional, so without a sender-sealed copy
        // Alice could never read a message she herself sent.
        $aliceListSession = $this->establishAlsSession($alice, $aliceToken);
        $aliceResponse = $this->alsGetJson("/api/v1/messages/{$conversationUuid}", $aliceToken, $aliceListSession);

        $aliceResponse->assertOk();
        $aliceDecoded = json_decode($aliceResponse->getContent(), true);
        $this->assertCount(1, $aliceDecoded['data']['messages']);
        $this->assertSame('opaque-ciphertext-for-alice', $aliceDecoded['data']['messages'][0]['ciphertext']);

        // Bob (the recipient) should see the OTHER sealed copy.
        $bobToken = $this->tokenFor($bob);
        $bobListSession = $this->establishAlsSession($bob, $bobToken);
        $bobResponse = $this->alsGetJson("/api/v1/messages/{$conversationUuid}", $bobToken, $bobListSession);

        $bobResponse->assertOk();
        $bobDecoded = json_decode($bobResponse->getContent(), true);
        $this->assertSame('opaque-ciphertext-for-bob', $bobDecoded['data']['messages'][0]['ciphertext']);
    }

    #[Test]
    public function ft_msg_003_sending_to_a_nonexistent_conversation_is_rejected(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->givePublicKeys($bob);

        $token = $this->tokenFor($alice);
        $session = $this->establishAlsSession($alice, $token);

        $response = $this->alsPostJson('/api/v1/messages', [
            'conversation_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'recipient_uuid' => $bob->uuid,
            'ciphertext_for_recipient' => 'opaque-ciphertext',
            'ciphertext_for_sender' => 'opaque-ciphertext-self',
            'signature' => null,
            'signed' => false,
        ], $token, $session);

        $response->assertStatus(404);
        $decoded = json_decode($response->getContent(), true);
        $this->assertSame('MSG001', $decoded['error_code']);
    }

    #[Test]
    public function ft_msg_004_sending_to_a_recipient_with_no_public_key_is_rejected(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        // Deliberately NOT calling givePublicKeys($bob).

        $conversationUuid = $this->createConversation($alice, $bob);
        $token = $this->tokenFor($alice);
        $session = $this->establishAlsSession($alice, $token);

        $response = $this->alsPostJson('/api/v1/messages', [
            'conversation_uuid' => $conversationUuid,
            'recipient_uuid' => $bob->uuid,
            'ciphertext_for_recipient' => 'opaque-ciphertext',
            'ciphertext_for_sender' => 'opaque-ciphertext-self',
            'signature' => null,
            'signed' => false,
        ], $token, $session);

        $response->assertStatus(422);
        $decoded = json_decode($response->getContent(), true);
        $this->assertSame('ECC002', $decoded['error_code']);
    }
}
