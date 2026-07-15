<?php

namespace App\Exceptions\Crypto;

/**
 * Reference: api_spec.md §6.9 (ALS001)
 *
 * Thrown when a request references an ALS session that doesn't
 * exist OR doesn't belong to the requesting user — distinct from
 * ALS002 (session existed, belonged to this user, but has expired).
 *
 * BUG HISTORY: during development, this exposed a detailed internal
 * debug message (session UUIDs, user IDs, in English) directly in the
 * client-facing response — useful for diagnosing a real bug at the
 * time, but not appropriate for an actual demo/production response.
 * Now that debugging is done, the client-facing message is a stable,
 * translated, generic string (api_spec.md defines only one ALS001
 * message); the previously-useful diagnostic detail is preserved in
 * $debugDetail for logging, but never exposed via toResponseArray().
 */
class InvalidAlsSessionException extends CryptographicException
{
    private function __construct(private readonly string $debugDetail)
    {
        parent::__construct(
            message: 'Sesi ALS tidak ditemukan atau tidak valid.',
            errorCode: 'ALS001',
            statusCode: 422,
        );
    }

    /** Diagnostic detail for logs — never included in the API response. */
    public function debugDetail(): string
    {
        return $this->debugDetail;
    }

    public static function notFound(string $sessionUuid): self
    {
        return new self("No ALS session row exists with uuid={$sessionUuid}.");
    }

    public static function missingHeader(): self
    {
        return new self('Request is missing the required X-ALS-Session header.');
    }

    public static function ownedByAnotherUser(string $sessionUuid, int $sessionUserId, int $requestingUserId): self
    {
        return new self(
            "ALS session uuid={$sessionUuid} exists but belongs to user_id={$sessionUserId}, ".
            "not the requesting user_id={$requestingUserId}."
        );
    }
}
