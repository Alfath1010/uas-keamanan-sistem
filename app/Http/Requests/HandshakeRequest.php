<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reference: api_spec.md §6.4 (POST /als/handshake, POST /als/renew)
 */
class HandshakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_public_key' => ['required', 'string'],
        ];
    }
}
