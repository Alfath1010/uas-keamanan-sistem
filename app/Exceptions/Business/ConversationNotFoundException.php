<?php

namespace App\Exceptions\Business;

/**
 * Reference: api_spec.md §6.9 (MSG001), §6.5 (GET conversation error
 * codes), §6.6 (POST /messages error codes)
 */
class ConversationNotFoundException extends BusinessException
{
    /**
     * NOTE: api_spec.md is internally inconsistent about the HTTP
     * status for MSG001 — §6.5 (GET conversation) implies 404, while
     * §6.6 (POST /messages) implies 400. Defaults to 404 (more
     * RESTful for "resource not found"); callers on the POST /messages
     * path may pass 400 explicitly to match §6.6 literally.
     */
    public function __construct(int $statusCode = 404)
    {
        parent::__construct(
            message: 'Percakapan tidak ditemukan.',
            errorCode: 'MSG001',
            statusCode: $statusCode,
        );
    }
}
