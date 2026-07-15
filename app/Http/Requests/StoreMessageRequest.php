<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reference: api_spec.md §6.6 (POST /messages, Decrypted Payload)
 *
 * NOTE: `recipient_uuid` is present in the decrypted payload shape
 * per the spec but is not independently trusted here — the actual
 * recipient is derived from conversation membership in
 * MessageService, which is the authoritative source. This field is
 * still validated as present/well-formed for API-contract
 * conformance.
 *
 * DEVIATION: api_spec.md's example payload has a single `ciphertext`
 * field. Since ECC sealed-box encryption is one-directional, the
 * client must encrypt the message twice — once to the recipient's
 * public key, once to its own — so the sender can also read their own
 * sent messages later (see the messages migration's docblock for the
 * full rationale). The response shape is unaffected: GET endpoints
 * still return a single `ciphertext` field, resolved server-side to
 * whichever copy matches the requesting user.
 */
class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conversation_uuid' => ['required', 'string', 'uuid'],
            'recipient_uuid' => ['required', 'string', 'uuid'],
            'ciphertext_for_recipient' => ['required', 'string'],
            'ciphertext_for_sender' => ['required', 'string'],
            'signature' => ['nullable', 'array'],
            'signature.e' => ['required_with:signature', 'string'],
            'signature.s' => ['required_with:signature', 'string'],
            'signed' => ['required', 'boolean'],
        ];
    }
}
