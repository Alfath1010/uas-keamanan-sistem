<?php

namespace App\Services;

use App\Models\ALSSession;
use App\Models\PublicKey;
use App\Models\User;

/**
 * Reference: architecture.md §3.7 (CryptoManager Abstraction)
 *
 * IMPORTANT — scope note (read before extending this class):
 *
 * architecture.md §3.7 describes CryptoManager generically as
 * coordinating "encrypting messages... signing messages... verifying
 * signatures" alongside ALS. In this application's actual design,
 * however, ECC encryption and Schnorr signing of MESSAGE CONTENT
 * happen entirely on the CLIENT:
 *
 *  - Private keys never exist server-side (FR-009, ADR-007, SEC-005),
 *    so the server is cryptographically incapable of signing on a
 *    user's behalf or decrypting a message meant for them.
 *  - api_spec.md §6.6's "Decrypted Payload" for POST /messages already
 *    contains a finished `ciphertext` and `signature` — the client
 *    computed both before the request was ever made.
 *  - Signature verification likewise requires the plaintext, which
 *    requires ECC decryption with the recipient's private key — also
 *    client-only.
 *
 * So CryptoManager's real job is narrower than §3.7's prose suggests:
 * it coordinates the ALS transport layer (the one thing that
 * genuinely happens server-side) and offers a small set of
 * courtesy/validation helpers that don't require touching plaintext
 * or private keys. Business services (e.g. MessageService) SHALL
 * still go through CryptoManager rather than ALSService/KeyManager
 * directly, preserving the abstraction boundary described in §3.7 —
 * the scope is just accurately reflected here rather than aspirational.
 */
interface CryptoManagerInterface
{
    /**
     * @return array{session: ALSSession, server_public_key: string}
     */
    public function negotiateSession(User $user, string $clientPublicKeyB64): array;

    /**
     * @return array{session: ALSSession, server_public_key: string}
     */
    public function renewSession(User $user, string $clientPublicKeyB64): array;

    /**
     * @throws \App\Exceptions\Crypto\InvalidAlsSessionException
     * @throws \App\Exceptions\Crypto\AlsSessionExpiredException
     */
    public function resolveActiveSession(User $user, string $sessionUuid): ALSSession;

    /**
     * @param  array{iv: string, ciphertext: string, tag: string}  $payload
     *
     * @throws \App\Exceptions\Crypto\ALSPayloadDecryptionException
     */
    public function decryptRequestPayload(ALSSession $session, array $payload): string;

    /**
     * @return array{iv: string, ciphertext: string, tag: string}
     */
    public function encryptResponsePayload(ALSSession $session, string $plaintext): array;

    /**
     * Courtesy pre-flight check used by MessageService before
     * accepting a new message: confirms the recipient has uploaded
     * ECC key material at all. This does NOT perform any encryption
     * and does NOT guarantee the client's own key-lookup will
     * succeed (keys could theoretically change between this check
     * and the client's own GET /users/{uuid}/keys call) — it exists
     * purely to fail fast with a descriptive error (ECC002) instead
     * of accepting a message that store-only logic has no way to
     * flag as undeliverable.
     *
     * @throws \App\Exceptions\Crypto\RecipientPublicKeyUnavailableException
     */
    public function ensureRecipientEncryptable(User $recipient): void;

    /**
     * @throws \App\Exceptions\Crypto\InvalidPublicKeyException
     */
    public function uploadPublicKeys(User $user, string $eccPublicKeyB64, string $schnorrPublicKeyDecimal): PublicKey;

    public function getPublicKeys(User $user): ?PublicKey;
}
