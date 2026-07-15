<?php

namespace App\Services\Security\ALS;

use App\Models\ALSSession;
use App\Models\User;

/**
 * Reference: crypto_design.md §4.2 (Application Layer Security)
 *
 * Coordinates the ECDH handshake (via ECCServiceInterface) and
 * payload encryption/decryption (via BlockCipherServiceInterface) for
 * transport-layer protection of HTTP requests/responses, independent
 * of E2EE message content (crypto_design.md §4.1 — each layer SHALL
 * operate independently).
 */
interface ALSServiceInterface
{
    /**
     * Perform steps 3-5 of the handshake described in
     * crypto_design.md §4.2.3: generate a server ephemeral key pair,
     * compute the shared secret with the client's public key, derive
     * a session key, and persist a new session.
     *
     * @return array{session: ALSSession, server_public_key: string}
     */
    public function handshake(User $user, string $clientPublicKeyB64): array;

    /**
     * Renew an expired/expiring session by performing a fresh
     * handshake (ALS-002). Semantically identical to handshake(),
     * exposed separately to mirror POST /als/renew in api_spec.md §6.4.
     *
     * @return array{session: ALSSession, server_public_key: string}
     */
    public function renew(User $user, string $clientPublicKeyB64): array;

    /**
     * Resolve and validate the session referenced by $sessionUuid for
     * $user, throwing if it doesn't exist or has expired.
     *
     * @throws \App\Exceptions\Crypto\InvalidAlsSessionException
     * @throws \App\Exceptions\Crypto\AlsSessionExpiredException
     */
    public function resolveActiveSession(User $user, string $sessionUuid): ALSSession;

    /**
     * Decrypt an incoming ALS-protected request body.
     *
     * @param  array{iv: string, ciphertext: string, tag: string}  $payload
     *
     * @throws \App\Exceptions\Crypto\ALSPayloadDecryptionException
     */
    public function decryptPayload(ALSSession $session, array $payload): string;

    /**
     * Encrypt an outgoing ALS-protected response body.
     *
     * @return array{iv: string, ciphertext: string, tag: string}
     */
    public function encryptPayload(ALSSession $session, string $plaintext): array;
}
