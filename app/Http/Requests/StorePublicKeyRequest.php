<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reference: api_spec.md §6.7 (POST /users/keys), FR-008
 */
class StorePublicKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ecc_public_key' => ['required', 'string'],
            'schnorr_public_key' => ['required', 'string'],
        ];
    }
}
