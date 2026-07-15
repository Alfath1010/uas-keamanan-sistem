<?php

namespace App\Exceptions\Crypto;

/**
 * Reference: api_spec.md §6.9 (ECC002), api_spec.md §6.6
 * (POST /messages error codes), FT-MSG-004.
 */
class RecipientPublicKeyUnavailableException extends CryptographicException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Penerima belum mengunggah kunci publik ECC. Pesan tidak dapat dienkripsi.',
            errorCode: 'ECC002',
            statusCode: 422,
        );
    }
}
