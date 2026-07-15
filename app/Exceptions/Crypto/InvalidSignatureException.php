<?php

namespace App\Exceptions\Crypto;

/**
 * Reference: api_spec.md §6.9 (SCH001), crypto_design.md §4.6
 * "Modified Ciphertext" example, DEM-004.
 *
 * Thrown when signature verification fails because the message
 * content no longer matches what was signed (e.g. tampered
 * ciphertext/plaintext) — an integrity failure specifically, as
 * opposed to a key-configuration problem (SCH002).
 */
class InvalidSignatureException extends CryptographicException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Verifikasi integritas gagal. Pesan tidak cocok dengan tanda tangannya.',
            errorCode: 'SCH001',
            statusCode: 422,
        );
    }
}
