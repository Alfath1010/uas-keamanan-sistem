<?php

namespace App\Exceptions\Business;

/**
 * Reference: api_spec.md §6.9 (AUTH002 - "Invalid credentials"),
 * api_spec.md §6.3 (POST /login error codes list "AUTH001" for 401,
 * which is inconsistent with §6.9's own table; we follow §6.9's
 * definition since it is the canonical Error Code Reference and use
 * AUTH002 for invalid login credentials specifically).
 */
class InvalidCredentialsException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Email atau kata sandi yang Anda masukkan salah.',
            errorCode: 'AUTH002',
            statusCode: 401,
        );
    }
}
