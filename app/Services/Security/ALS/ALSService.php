<?php

namespace App\Services\Security\ALS;

use App\Exceptions\Crypto\AlsSessionExpiredException;
use App\Exceptions\Crypto\InvalidAlsSessionException;
use App\Models\ALSSession;
use App\Models\User;
use App\Repositories\Contracts\ALSSessionRepositoryInterface;
use App\Services\Security\BlockCipher\BlockCipherServiceInterface;
use App\Services\Security\ECC\ECCServiceInterface;
use Illuminate\Support\Carbon;

/**
 * Reference: crypto_design.md §4.2
 *
 * Session key derivation: raw X25519 shared secret -> HKDF-SHA256 ->
 * 32-byte AES-256-GCM key (crypto_design.md §4.2.4). The HKDF `info`
 * parameter binds the derived key to both parties' ephemeral public
 * keys, which provides domain separation and ties the key to this
 * specific handshake transcript.
 */
class ALSService implements ALSServiceInterface
{
    private const HKDF_HASH_ALGO = 'sha256';

    private const SESSION_KEY_LENGTH = 32; // bytes, for AES-256

    public function __construct(
        private readonly ECCServiceInterface $ecc,
        private readonly BlockCipherServiceInterface $blockCipher,
        private readonly ALSSessionRepositoryInterface $sessions,
    ) {}

    public function handshake(User $user, string $clientPublicKeyB64): array
    {
        $serverKeyPair = $this->ecc->generateKeyPair();

        $sharedSecret = $this->ecc->sharedSecret(
            $serverKeyPair['private_key'],
            $clientPublicKeyB64,
        );

        $sessionKey = $this->deriveSessionKey(
            $sharedSecret,
            $clientPublicKeyB64,
            $serverKeyPair['public_key'],
        );

        $expiresAt = Carbon::now()->addMinutes(
            (int) config('als.session_lifetime_minutes', 15)
        );

        $session = $this->sessions->create(
            $user,
            base64_encode($sessionKey),
            $expiresAt,
        );

        // The server's ephemeral private key is used once, above, and
        // never persisted or returned — only the derived session key
        // survives the handshake (forward secrecy for the transport
        // layer: compromising stored data later does not reveal it).

        return [
            'session' => $session,
            'server_public_key' => $serverKeyPair['public_key'],
        ];
    }

    public function renew(User $user, string $clientPublicKeyB64): array
    {
        // Renewal is a fresh handshake (ALS-002): a new session row
        // is created rather than mutating the expired one, per the
        // ALSSession model's immutability note.
        return $this->handshake($user, $clientPublicKeyB64);
    }

    public function resolveActiveSession(User $user, string $sessionUuid): ALSSession
    {
        $session = $this->sessions->findByUuid($sessionUuid);

        if ($session === null) {
            throw InvalidAlsSessionException::notFound($sessionUuid);
        }

        if ($session->user_id !== $user->id) {
            throw InvalidAlsSessionException::ownedByAnotherUser($sessionUuid, $session->user_id, $user->id);
        }

        if ($session->isExpired()) {
            throw new AlsSessionExpiredException();
        }

        return $session;
    }

    public function decryptPayload(ALSSession $session, array $payload): string
    {
        $key = base64_decode($session->session_key, true);

        return $this->blockCipher->decrypt(
            $payload['iv'],
            $payload['ciphertext'],
            $payload['tag'],
            $key,
        );
    }

    public function encryptPayload(ALSSession $session, string $plaintext): array
    {
        $key = base64_decode($session->session_key, true);

        return $this->blockCipher->encrypt($plaintext, $key);
    }

    private function deriveSessionKey(string $sharedSecret, string $clientPublicKeyB64, string $serverPublicKeyB64): string
    {
        $info = 'secure-messaging-app-als-session-key|'.$clientPublicKeyB64.'|'.$serverPublicKeyB64;

        return hash_hkdf(
            algo: self::HKDF_HASH_ALGO,
            key: $sharedSecret,
            length: self::SESSION_KEY_LENGTH,
            info: $info,
        );
    }
}
