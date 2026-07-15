<?php

namespace App\Exceptions\Crypto;

/**
 * ADDENDUM to api_spec.md §6.9: the Error Code Reference table does
 * not enumerate a code for malformed public key uploads (as opposed
 * to failures during use of a key, which ECC001-003/SCH001-002
 * already cover). KEY001 fills that gap for
 * POST /api/v1/users/keys (§6.7) validation failures. This should be
 * folded into the canonical error table the next time api_spec.md is
 * revised.
 */
class InvalidPublicKeyException extends CryptographicException
{
    public function __construct(string $detail)
    {
        parent::__construct(
            message: "Kunci publik tidak valid: {$detail}",
            errorCode: 'KEY001',
            statusCode: 422,
        );
    }
}
