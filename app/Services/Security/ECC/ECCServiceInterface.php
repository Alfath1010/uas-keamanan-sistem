<?php

namespace App\Services\Security\ECC;

use App\Exceptions\Crypto\EccDecryptionFailedException;
use App\Exceptions\Crypto\InvalidEccPrivateKeyException;

/**
 * Reference: crypto_design.md §4.3 (End-to-End Encryption)
 *
 * Provides X25519-based public-key encryption ("ECC" in the project
 * vocabulary — X25519 is an elliptic-curve Diffie-Hellman function).
 * Used both for E2EE message encryption and, independently, as the
 * key-agreement primitive underlying the ALS handshake
 * (crypto_design.md §4.2.3).
 */
interface ECCServiceInterface
{
    /**
     * Generate a new X25519 key pair.
     *
     * NOTE: per FR-009/ADR-007, private keys generated here are
     * intended for local, client-side use only (e.g. a convenience
     * "generate keys for me" flow in a demo client). The server
     * SHALL NOT persist the private key half of this result.
     *
     * @return array{public_key: string, private_key: string} Base64-encoded.
     */
    public function generateKeyPair(): array;

    /**
     * Encrypt $plaintext so that only the holder of the matching
     * private key for $recipientPublicKeyB64 can decrypt it
     * (anonymous sealed box — no sender authentication is implied by
     * this primitive; that is what Schnorr signatures are for).
     */
    public function encrypt(string $plaintext, string $recipientPublicKeyB64): string;

    /**
     * @throws InvalidEccPrivateKeyException
     * @throws EccDecryptionFailedException
     */
    public function decrypt(string $ciphertextB64, string $recipientPublicKeyB64, string $recipientPrivateKeyB64): string;

    /**
     * Raw X25519 Diffie-Hellman shared secret: scalarmult(privateKey,
     * peerPublicKey). Distinct from encrypt()/decrypt() (sealed
     * boxes, used for E2EE message content) — this is the primitive
     * the ALS handshake uses to derive a transport session key
     * (crypto_design.md §4.2.3/§4.2.4). The returned value is a raw
     * shared point, NOT yet suitable as a cipher key; it must be
     * passed through a KDF (see ALSService) before use.
     */
    public function sharedSecret(string $privateKeyB64, string $peerPublicKeyB64): string;
}
