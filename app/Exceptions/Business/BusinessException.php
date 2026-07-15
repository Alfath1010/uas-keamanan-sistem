<?php

namespace App\Exceptions\Business;

use Exception;

/**
 * Reference: architecture.md §3.10 (Error Handling Architecture),
 * api_spec.md §6.2 (Standard API Response)
 *
 * Non-cryptographic domain errors (missing/unauthorized resources,
 * bad credentials) follow the same envelope shape as
 * CryptographicException, kept as a separate hierarchy since these
 * originate in the Business Layer rather than the Security Layer
 * (architecture.md §3.3) and carry no cryptographic meaning.
 */
abstract class BusinessException extends Exception
{
    public function __construct(
        string $message,
        protected string $errorCode,
        protected int $statusCode,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function toResponseArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'errors' => [],
        ];
    }
}
