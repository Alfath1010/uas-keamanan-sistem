<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Reference: api_spec.md §6.2 (Standard API Response), ADR-004
 *
 * Every controller SHALL return responses through this trait so the
 * {success, message, data} / {success, message, error_code, errors}
 * envelope stays consistent across the entire API.
 */
trait ApiResponse
{
    protected function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
