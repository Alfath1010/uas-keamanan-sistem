<?php

namespace App\Exceptions\Business;

/**
 * ADDENDUM to api_spec.md §6.9: §6.3's register error table lists
 * "409 | Email already exists" without an explicit machine-readable
 * code from the canonical table. AUTH003 fills that gap.
 */
class EmailAlreadyExistsException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Akun dengan alamat email ini sudah ada.',
            errorCode: 'AUTH003',
            statusCode: 409,
        );
    }
}
