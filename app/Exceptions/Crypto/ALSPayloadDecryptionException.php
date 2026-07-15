<?php

namespace App\Exceptions\Crypto;

/**
 * Reference: api_spec.md §6.9 (ALS003), crypto_design.md §4.6
 * "Expired ALS Session" / decryption failure examples.
 *
 * Thrown when AES-256-GCM authenticated decryption fails — either the
 * ciphertext/tag was tampered with, or the wrong session key was
 * used. GCM's authentication tag makes this indistinguishable from
 * "modified ciphertext", which is the desired behavior (no oracle).
 */
class ALSPayloadDecryptionException extends CryptographicException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Tidak dapat mendekripsi payload ALS. Kunci sesi mungkin salah atau payload telah dimanipulasi.',
            errorCode: 'ALS003',
            statusCode: 422,
        );
    }
}
