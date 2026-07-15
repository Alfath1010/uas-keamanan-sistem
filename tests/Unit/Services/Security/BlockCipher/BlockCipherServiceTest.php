<?php

namespace Tests\Unit\Services\Security\BlockCipher;

use App\Exceptions\Crypto\ALSPayloadDecryptionException;
use App\Services\Security\BlockCipher\BlockCipherService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reference: testing_spec.md §7.3 (Block Cipher unit tests, BC-001..006)
 *
 * NOTE: these tests exercise AES-256-GCM, not the CBC mode shown in
 * crypto_design.md's example JSON — see BlockCipherService's docblock
 * for the agreed rationale. Test IDs are preserved from the spec
 * since the underlying requirement (encrypt/decrypt/tamper-detection)
 * is identical regardless of mode.
 */
class BlockCipherServiceTest extends TestCase
{
    private BlockCipherService $cipher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cipher = new BlockCipherService();
    }

    private function key(): string
    {
        return random_bytes(32);
    }

    #[Test]
    public function bc_001_encrypt_plaintext_produces_ciphertext(): void
    {
        $key = $this->key();
        $result = $this->cipher->encrypt('hello world', $key);

        $this->assertArrayHasKey('iv', $result);
        $this->assertArrayHasKey('ciphertext', $result);
        $this->assertArrayHasKey('tag', $result);
        $this->assertNotSame('hello world', $result['ciphertext']);
    }

    #[Test]
    public function bc_002_decrypt_recovers_original_plaintext(): void
    {
        $key = $this->key();
        $encrypted = $this->cipher->encrypt('the quick brown fox', $key);

        $plaintext = $this->cipher->decrypt(
            $encrypted['iv'],
            $encrypted['ciphertext'],
            $encrypted['tag'],
            $key,
        );

        $this->assertSame('the quick brown fox', $plaintext);
    }

    #[Test]
    public function bc_003_wrong_key_fails_decryption(): void
    {
        $encrypted = $this->cipher->encrypt('secret payload', $this->key());

        $this->expectException(ALSPayloadDecryptionException::class);

        $this->cipher->decrypt(
            $encrypted['iv'],
            $encrypted['ciphertext'],
            $encrypted['tag'],
            $this->key(), // different key
        );
    }

    #[Test]
    public function bc_004_wrong_iv_fails_decryption(): void
    {
        $key = $this->key();
        $encrypted = $this->cipher->encrypt('secret payload', $key);

        $this->expectException(ALSPayloadDecryptionException::class);

        $this->cipher->decrypt(
            base64_encode(random_bytes(12)), // different IV
            $encrypted['ciphertext'],
            $encrypted['tag'],
            $key,
        );
    }

    #[Test]
    public function bc_005_empty_plaintext_produces_valid_encrypted_output(): void
    {
        $key = $this->key();
        $encrypted = $this->cipher->encrypt('', $key);

        $plaintext = $this->cipher->decrypt(
            $encrypted['iv'],
            $encrypted['ciphertext'],
            $encrypted['tag'],
            $key,
        );

        $this->assertSame('', $plaintext);
    }

    #[Test]
    public function bc_006_large_plaintext_is_successfully_encrypted(): void
    {
        $key = $this->key();
        $large = str_repeat('A', 5 * 1024 * 1024); // 5 MB

        $encrypted = $this->cipher->encrypt($large, $key);
        $plaintext = $this->cipher->decrypt(
            $encrypted['iv'],
            $encrypted['ciphertext'],
            $encrypted['tag'],
            $key,
        );

        $this->assertSame($large, $plaintext);
    }

    #[Test]
    public function tampered_ciphertext_is_rejected(): void
    {
        $key = $this->key();
        $encrypted = $this->cipher->encrypt('do not tamper with me', $key);

        $tamperedBytes = base64_decode($encrypted['ciphertext']);
        $tamperedBytes[0] = chr(ord($tamperedBytes[0]) ^ 0xFF);

        $this->expectException(ALSPayloadDecryptionException::class);

        $this->cipher->decrypt(
            $encrypted['iv'],
            base64_encode($tamperedBytes),
            $encrypted['tag'],
            $key,
        );
    }
}
