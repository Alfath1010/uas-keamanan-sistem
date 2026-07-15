<?php

/**
 * Indonesian (Bahasa Indonesia) translations for Laravel's default
 * validation messages. Only extended with entries actually exercised
 * by this project's FormRequests (app/Http/Requests/*), rather than
 * a full port of every possible Laravel validation rule.
 */
return [
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'min' => [
        'string' => ':attribute minimal harus :min karakter.',
    ],
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'array' => ':attribute harus berupa larik (array).',
    'uuid' => ':attribute harus berupa UUID yang valid.',
    'required_with' => ':attribute wajib diisi apabila :values ada.',

    /*
     * Human-readable field names substituted into ":attribute" above.
     */
    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'client_public_key' => 'kunci publik klien',
        'recipient_email' => 'email penerima',
        'recipient_uuid' => 'UUID penerima',
        'conversation_uuid' => 'UUID percakapan',
        'ciphertext_for_recipient' => 'ciphertext untuk penerima',
        'ciphertext_for_sender' => 'ciphertext untuk pengirim',
        'signature' => 'tanda tangan',
        'signature.e' => 'komponen e pada tanda tangan',
        'signature.s' => 'komponen s pada tanda tangan',
        'signed' => 'status tanda tangan',
        'ecc_public_key' => 'kunci publik ECC',
        'schnorr_public_key' => 'kunci publik Schnorr',
    ],
];
