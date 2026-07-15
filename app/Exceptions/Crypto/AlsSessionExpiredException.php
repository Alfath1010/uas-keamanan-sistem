<?php

namespace App\Exceptions\Crypto;

/**
 * Reference: api_spec.md §6.9 (ALS002), crypto_design.md §4.6
 * "Expired ALS Session" example, DEM-005.
 */
class AlsSessionExpiredException extends CryptographicException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Sesi Application Layer Security telah kedaluwarsa. Diperlukan jabat tangan (handshake) baru.',
            errorCode: 'ALS002',
            statusCode: 422,
        );
    }
}
