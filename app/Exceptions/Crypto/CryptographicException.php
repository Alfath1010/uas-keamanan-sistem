<?php

namespace App\Exceptions\Crypto;

use Exception;

/**
 * Reference: architecture.md §3.10 (Error Handling Architecture)
 *
 * Every cryptographic error SHALL carry a human-readable message, a
 * machine-readable error code (api_spec.md §6.9), and an appropriate
 * HTTP status code — WITHOUT disclosing sensitive internal details
 * (SEC-008). Subclasses map 1:1 to entries in the Error Code
 * Reference table.
 */
abstract class CryptographicException extends Exception
{
    public function __construct(
        string $message,
        protected string $errorCode,
        protected int $statusCode = 422,
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

    /**
     * Standard API error envelope, per api_spec.md §6.2.
     */
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
