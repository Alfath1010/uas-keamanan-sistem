<?php

namespace App\Exceptions\Business;

/**
 * ADDENDUM to api_spec.md §6.9: needed when a recipient_uuid
 * (POST /conversations, §6.5) or user_uuid (GET /users/{uuid}/keys,
 * §6.7) doesn't resolve to an existing user.
 */
class UserNotFoundException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Pengguna tidak ditemukan.',
            errorCode: 'USR001',
            statusCode: 404,
        );
    }
}
