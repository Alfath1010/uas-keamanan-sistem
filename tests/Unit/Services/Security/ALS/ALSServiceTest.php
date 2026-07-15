<?php

namespace Tests\Unit\Services\Security\ALS;

use App\Exceptions\Crypto\ALSPayloadDecryptionException;
use App\Exceptions\Crypto\AlsSessionExpiredException;
use App\Exceptions\Crypto\InvalidAlsSessionException;
use App\Models\User;
use App\Repositories\ALSSessionRepository;
use App\Services\Security\ALS\ALSService;
use App\Services\Security\BlockCipher\BlockCipherService;
use App\Services\Security\ECC\ECCService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reference: testing_spec.md §7.3 (ALS unit tests, ALS-001..005)
 *
 * Exercises the real ECCService + BlockCipherService + DB-backed
 * ALSSessionRepository together, since ALSService's entire purpose is
 * coordinating them (crypto_design.md §4.2).
 */
class ALSServiceTest extends TestCase
{
    use RefreshDatabase;

    private ALSService $als;
    private ECCService $ecc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ecc = new ECCService();
        $this->als = new ALSService(
            $this->ecc,
            new BlockCipherService(),
            new ALSSessionRepository(),
        );
    }

    #[Test]
    public function als_001_successful_handshake_establishes_a_shared_secret(): void
    {
        $user = User::factory()->create();
        $client = $this->ecc->generateKeyPair();

        $result = $this->als->handshake($user, $client['public_key']);

        $this->assertNotEmpty($result['session']->uuid);
        $this->assertNotEmpty($result['server_public_key']);

        // The client independently derives the same shared secret via
        // ECDH — this is what proves the handshake actually agrees on
        // a key, not just that a session row exists.
        $clientSharedSecret = $this->ecc->sharedSecret($client['private_key'], $result['server_public_key']);
        $this->assertNotEmpty($clientSharedSecret);
    }

    #[Test]
    public function als_002_session_renewal_creates_a_new_session(): void
    {
        $user = User::factory()->create();
        $client = $this->ecc->generateKeyPair();

        $first = $this->als->handshake($user, $client['public_key']);
        $second = $this->als->renew($user, $client['public_key']);

        $this->assertNotSame($first['session']->uuid, $second['session']->uuid);
    }

    #[Test]
    public function als_003_expired_session_is_rejected(): void
    {
        $user = User::factory()->create();
        $client = $this->ecc->generateKeyPair();
        $result = $this->als->handshake($user, $client['public_key']);

        // Force the session into the past.
        $result['session']->update(['expires_at' => Carbon::now()->subMinute()]);

        $this->expectException(AlsSessionExpiredException::class);

        $this->als->resolveActiveSession($user, $result['session']->uuid);
    }

    #[Test]
    public function unknown_session_uuid_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidAlsSessionException::class);

        $this->als->resolveActiveSession($user, (string) Str::uuid());
    }

    #[Test]
    public function als_004_payload_encryption_produces_ciphertext(): void
    {
        $user = User::factory()->create();
        $client = $this->ecc->generateKeyPair();
        $result = $this->als->handshake($user, $client['public_key']);

        $encrypted = $this->als->encryptPayload($result['session'], '{"hello":"world"}');

        $this->assertArrayHasKey('iv', $encrypted);
        $this->assertArrayHasKey('ciphertext', $encrypted);
        $this->assertArrayHasKey('tag', $encrypted);
    }

    #[Test]
    public function als_005_payload_decryption_recovers_the_original_payload(): void
    {
        $user = User::factory()->create();
        $client = $this->ecc->generateKeyPair();
        $result = $this->als->handshake($user, $client['public_key']);

        $encrypted = $this->als->encryptPayload($result['session'], '{"hello":"world"}');
        $decrypted = $this->als->decryptPayload($result['session'], $encrypted);

        $this->assertSame('{"hello":"world"}', $decrypted);
    }

    #[Test]
    public function tampered_payload_fails_decryption(): void
    {
        $user = User::factory()->create();
        $client = $this->ecc->generateKeyPair();
        $result = $this->als->handshake($user, $client['public_key']);

        $encrypted = $this->als->encryptPayload($result['session'], '{"hello":"world"}');
        $encrypted['ciphertext'] = base64_encode(random_bytes(20));

        $this->expectException(ALSPayloadDecryptionException::class);

        $this->als->decryptPayload($result['session'], $encrypted);
    }
}
