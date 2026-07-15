<?php

namespace App\Exceptions\Crypto;

/**
 * Reference: api_spec.md §6.9 (SCH002), crypto_design.md §4.6
 * "Invalid Schnorr Public Key" example.
 *
 * Thrown when verification cannot proceed or fails due to a
 * malformed/mismatched public key (e.g. wrong sender's key selected),
 * distinct from InvalidSignatureException's message-tamper case.
 */
class SignatureVerificationFailedException extends CryptographicException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Verifikasi tanda tangan gagal.',
            errorCode: 'SCH002',
            statusCode: 422,
        );
    }
}
