<?php

namespace App\Exceptions\Crypto;

/**
 * Reference: api_spec.md §6.9 (ECC001), crypto_design.md §4.6
 * "Invalid ECC Private Key" demonstration example (DEM-002).
 */
class InvalidEccPrivateKeyException extends CryptographicException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Tidak dapat mendekripsi pesan. Kunci privat ECC yang diberikan tidak cocok dengan kunci enkripsi.',
            errorCode: 'ECC001',
            statusCode: 422,
        );
    }
}
