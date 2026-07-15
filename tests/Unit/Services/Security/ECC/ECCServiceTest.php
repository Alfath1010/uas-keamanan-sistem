<?php

namespace Tests\Unit\Services\Security\ECC;

use App\Exceptions\Crypto\EccDecryptionFailedException;
use App\Exceptions\Crypto\InvalidEccPrivateKeyException;
use App\Services\Security\ECC\ECCService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reference: testing_spec.md §7.3 (ECC unit tests, ECC-001..005)
 */
class ECCServiceTest extends TestCase
{
    private ECCService $ecc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ecc = new ECCService();
    }

    #[Test]
    public function ecc_001_key_generation_produces_a_valid_key_pair(): void
    {
        $pair = $this->ecc->generateKeyPair();

        $this->assertArrayHasKey('public_key', $pair);
        $this->assertArrayHasKey('private_key', $pair);
        $this->assertSame(32, strlen(base64_decode($pair['public_key'], true)));
        $this->assertSame(32, strlen(base64_decode($pair['private_key'], true)));
    }

    #[Test]
    public function ecc_002_encrypt_message_produces_ciphertext(): void
    {
        $pair = $this->ecc->generateKeyPair();

        $ciphertext = $this->ecc->encrypt('hello, recipient', $pair['public_key']);

        $this->assertNotSame('hello, recipient', $ciphertext);
        $this->assertNotEmpty($ciphertext);
    }

    #[Test]
    public function ecc_003_correct_private_key_recovers_plaintext(): void
    {
        $pair = $this->ecc->generateKeyPair();
        $ciphertext = $this->ecc->encrypt('a message only you can read', $pair['public_key']);

        $plaintext = $this->ecc->decrypt($ciphertext, $pair['public_key'], $pair['private_key']);

        $this->assertSame('a message only you can read', $plaintext);
    }

    #[Test]
    public function ecc_004_wrong_private_key_throws_explicit_error(): void
    {
        $recipient = $this->ecc->generateKeyPair();
        $attacker = $this->ecc->generateKeyPair();

        $ciphertext = $this->ecc->encrypt('for the real recipient only', $recipient['public_key']);

        $this->expectException(InvalidEccPrivateKeyException::class);

        // Wrong key pair entirely — public/private mismatch.
        $this->ecc->decrypt($ciphertext, $attacker['public_key'], $attacker['private_key']);
    }

    #[Test]
    public function ecc_005_invalid_public_key_is_rejected(): void
    {
        $this->expectException(InvalidEccPrivateKeyException::class);

        // Not valid base64 X25519 key material (wrong length).
        $this->ecc->encrypt('should not encrypt', base64_encode('too-short'));
    }

    #[Test]
    public function malformed_ciphertext_raises_a_distinct_error_from_wrong_key(): void
    {
        $pair = $this->ecc->generateKeyPair();

        $this->expectException(EccDecryptionFailedException::class);

        $this->ecc->decrypt('not-valid-base64-sealed-box!!', $pair['public_key'], $pair['private_key']);
    }
}
