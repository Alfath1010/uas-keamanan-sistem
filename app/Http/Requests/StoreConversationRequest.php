<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reference: api_spec.md §6.5 (POST /conversations)
 *
 * DEVIATION: api_spec.md's example payload uses `recipient_uuid`. Changed
 * to `recipient_email` on request, so end users never need to see or
 * exchange raw UUIDs — email is the human-facing identifier; UUID remains
 * the internal public identifier (ADR-001) and is still what's actually
 * stored/returned once the conversation exists.
 */
class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_email' => ['required', 'string', 'email'],
        ];
    }
}
