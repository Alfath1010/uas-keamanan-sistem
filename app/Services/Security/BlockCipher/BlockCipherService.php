<?php

namespace App\Services\Security\BlockCipher;

use App\Exceptions\Crypto\ALSPayloadDecryptionException;

/**
 * AES-256-GCM implementation of BlockCipherServiceInterface, built on
 * PHP's OpenSSL extension (openssl_encrypt/openssl_decrypt) — no
 * hand-rolled block cipher or mode logic, per project constraint that
 * nothing cryptographic is implemented from scratch.
 *
 * DEVIATION FROM crypto_design.md §4.2.5: the spec's example payload
 * shape uses `"mode": "CBC"`. We use GCM instead, an authenticated
 * (AEAD) mode: it provides integrity/tamper-detection intrinsically,
 * removing the need for a separate HMAC and avoiding CBC padding-
 * oracle risk. The payload shape gains a `tag` field alongside
 * `iv`/`ciphertext` (see api_spec.md addendum). This was agreed with
 * the project owner as a "sane default" substitution.
 *
 * Reference: crypto_design.md §4.2.5 (Payload Encryption)
 */
class BlockCipherService implements BlockCipherServiceInterface
{
    private const CIPHER = 'aes-256-gcm';

    /** Byte length of a GCM nonce/IV. 12 bytes is the standard, most efficient size. */
    private const IV_LENGTH = 12;

    /** Byte length of the GCM authentication tag. */
    private const TAG_LENGTH = 16;

    public function encrypt(string $plaintext, string $key): array
    {
        $this->assertKeyLength($key);

        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            data: $plaintext,
            cipher_algo: self::CIPHER,
            passphrase: $key,
            options: OPENSSL_RAW_DATA,
            iv: $iv,
            tag: $tag,
            tag_length: self::TAG_LENGTH,
        );

        if ($ciphertext === false) {
            // Encryption failure here indicates a programming/config
            // error (e.g. bad key length), not an adversarial input,
            // so we fail loudly rather than returning a crypto error
            // code intended for client-facing failures.
            throw new \RuntimeException('AES-256-GCM encryption failed.');
        }

        return [
            'iv' => base64_encode($iv),
            'ciphertext' => base64_encode($ciphertext),
            'tag' => base64_encode($tag),
        ];
    }

    public function decrypt(string $ivB64, string $ciphertextB64, string $tagB64, string $key): string
    {
        $this->assertKeyLength($key);

        $iv = base64_decode($ivB64, true);
        $ciphertext = base64_decode($ciphertextB64, true);
        $tag = base64_decode($tagB64, true);

        if ($iv === false || $ciphertext === false || $tag === false) {
            throw new ALSPayloadDecryptionException();
        }

        $plaintext = openssl_decrypt(
            data: $ciphertext,
            cipher_algo: self::CIPHER,
            passphrase: $key,
            options: OPENSSL_RAW_DATA,
            iv: $iv,
            tag: $tag,
        );

        // openssl_decrypt returns false both on tampered ciphertext
        // and on a wrong key/tag — GCM authentication SHALL fail
        // closed in either case (BC-003, BC-004, DEM-009).
        if ($plaintext === false) {
            throw new ALSPayloadDecryptionException();
        }

        return $plaintext;
    }

    private function assertKeyLength(string $key): void
    {
        if (strlen($key) !== 32) {
            throw new \InvalidArgumentException('BlockCipherService requires a 32-byte (256-bit) key.');
        }
    }
}
