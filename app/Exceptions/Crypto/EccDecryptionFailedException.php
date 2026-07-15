<?php

namespace App\Exceptions\Crypto;

/**
 * Reference: api_spec.md §6.9 (ECC003)
 *
 * Distinct from InvalidEccPrivateKeyException (ECC001): this covers
 * the general "sealed box could not be opened" case (e.g. malformed
 * or corrupted ciphertext, DEM-009), whereas ECC001 is specifically
 * surfaced by the KeyManager/UI flow when a user selects a private
 * key that produces a decryption failure.
 */
class EccDecryptionFailedException extends CryptographicException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Pesan tidak dapat didekripsi. Ciphertext mungkin rusak.',
            errorCode: 'ECC003',
            statusCode: 422,
        );
    }
}
