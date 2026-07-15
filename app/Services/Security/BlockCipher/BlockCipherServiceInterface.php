<?php

namespace App\Services\Security\BlockCipher;

/**
 * Reference: crypto_design.md §4.2 (Application Layer Security)
 *
 * Symmetric authenticated encryption used to protect ALS request/
 * response payloads once a session key has been derived from the
 * ECDH handshake (crypto_design.md §4.2.4). Deliberately algorithm-
 * agnostic at the interface level so BlockCipherService could be
 * swapped without touching ALSService or CryptoManager (NFR-003).
 */
interface BlockCipherServiceInterface
{
    /**
     * Encrypt $plaintext under $key.
     *
     * @param  string  $key  Raw binary key (32 bytes for AES-256).
     * @return array{iv: string, ciphertext: string, tag: string} Base64-encoded fields.
     */
    public function encrypt(string $plaintext, string $key): array;

    /**
     * Decrypt a payload previously produced by encrypt().
     *
     * @param  string  $ivB64  Base64-encoded IV/nonce.
     * @param  string  $ciphertextB64  Base64-encoded ciphertext.
     * @param  string  $tagB64  Base64-encoded authentication tag.
     * @param  string  $key  Raw binary key (32 bytes for AES-256).
     *
     * @throws \App\Exceptions\Crypto\ALSPayloadDecryptionException
     */
    public function decrypt(string $ivB64, string $ciphertextB64, string $tagB64, string $key): string;
}
