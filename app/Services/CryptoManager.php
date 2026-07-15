<?php

namespace App\Services;

use App\Exceptions\Crypto\RecipientPublicKeyUnavailableException;
use App\Models\ALSSession;
use App\Models\PublicKey;
use App\Models\User;
use App\Services\Security\ALS\ALSServiceInterface;
use App\Services\Security\KeyManagement\KeyManagerServiceInterface;

/**
 * See CryptoManagerInterface for the scope note explaining why this
 * class does not perform ECC encryption/Schnorr signing of message
 * content (that happens client-side by design).
 *
 * Reference: architecture.md §3.7
 */
class CryptoManager implements CryptoManagerInterface
{
    public function __construct(
        private readonly ALSServiceInterface $als,
        private readonly KeyManagerServiceInterface $keyManager,
    ) {}

    public function negotiateSession(User $user, string $clientPublicKeyB64): array
    {
        return $this->als->handshake($user, $clientPublicKeyB64);
    }

    public function renewSession(User $user, string $clientPublicKeyB64): array
    {
        return $this->als->renew($user, $clientPublicKeyB64);
    }

    public function resolveActiveSession(User $user, string $sessionUuid): ALSSession
    {
        return $this->als->resolveActiveSession($user, $sessionUuid);
    }

    public function decryptRequestPayload(ALSSession $session, array $payload): string
    {
        return $this->als->decryptPayload($session, $payload);
    }

    public function encryptResponsePayload(ALSSession $session, string $plaintext): array
    {
        return $this->als->encryptPayload($session, $plaintext);
    }

    public function ensureRecipientEncryptable(User $recipient): void
    {
        $keys = $this->keyManager->getPublicKeys($recipient);

        if ($keys === null) {
            throw new RecipientPublicKeyUnavailableException();
        }
    }

    public function uploadPublicKeys(User $user, string $eccPublicKeyB64, string $schnorrPublicKeyDecimal): PublicKey
    {
        return $this->keyManager->uploadPublicKeys($user, $eccPublicKeyB64, $schnorrPublicKeyDecimal);
    }

    public function getPublicKeys(User $user): ?PublicKey
    {
        return $this->keyManager->getPublicKeys($user);
    }
}
