<?php

namespace App\Services\Security\ECC;

use App\Exceptions\Crypto\EccDecryptionFailedException;
use App\Exceptions\Crypto\InvalidEccPrivateKeyException;

/**
 * X25519 implementation of ECCServiceInterface, built entirely on
 * PHP's libsodium extension (sodium_crypto_box_*) — no hand-rolled
 * elliptic-curve arithmetic.
 *
 * Uses libsodium's "sealed box" construction
 * (sodium_crypto_box_seal / _seal_open), which is exactly "encrypt
 * anonymously to a public key": it internally generates an ephemeral
 * X25519 key pair per message, so no nonce management is required by
 * this service.
 *
 * Reference: crypto_design.md §4.3 (End-to-End Encryption)
 *
 * NOTE on error codes (ECC001 vs ECC003): a sealed box's
 * authenticated decryption cannot cryptographically distinguish
 * "wrong private key" from "corrupted/tampered ciphertext" — both
 * simply fail the Poly1305 MAC check inside the box. This is by
 * design (an oracle that told you *why* decryption failed would leak
 * information). Since crypto_design.md §4.6 and DEM-002 specifically
 * demonstrate the "wrong private key" scenario, decrypt() surfaces
 * InvalidEccPrivateKeyException (ECC001) for any seal_open failure;
 * EccDecryptionFailedException (ECC003) is reserved for detectably
 * malformed input (e.g. invalid base64, ciphertext too short to be a
 * valid sealed box) caught before decryption is even attempted.
 */
class ECCService implements ECCServiceInterface
{
    public function generateKeyPair(): array
    {
        $keyPair = sodium_crypto_box_keypair();

        return [
            'public_key' => base64_encode(sodium_crypto_box_publickey($keyPair)),
            'private_key' => base64_encode(sodium_crypto_box_secretkey($keyPair)),
        ];
    }

    public function encrypt(string $plaintext, string $recipientPublicKeyB64): string
    {
        $publicKey = $this->decodeKey($recipientPublicKeyB64, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES);

        $sealed = sodium_crypto_box_seal($plaintext, $publicKey);

        return base64_encode($sealed);
    }

    public function decrypt(string $ciphertextB64, string $recipientPublicKeyB64, string $recipientPrivateKeyB64): string
    {
        $ciphertext = base64_decode($ciphertextB64, true);

        if ($ciphertext === false || strlen($ciphertext) < SODIUM_CRYPTO_BOX_SEALBYTES) {
            throw new EccDecryptionFailedException();
        }

        $publicKey = $this->decodeKey($recipientPublicKeyB64, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES);
        $privateKey = $this->decodeKey($recipientPrivateKeyB64, SODIUM_CRYPTO_BOX_SECRETKEYBYTES);

        $keyPair = sodium_crypto_box_keypair_from_secretkey_and_publickey($privateKey, $publicKey);

        $plaintext = sodium_crypto_box_seal_open($ciphertext, $keyPair);

        if ($plaintext === false) {
            throw new InvalidEccPrivateKeyException();
        }

        return $plaintext;
    }

    public function sharedSecret(string $privateKeyB64, string $peerPublicKeyB64): string
    {
        $privateKey = $this->decodeKey($privateKeyB64, SODIUM_CRYPTO_BOX_SECRETKEYBYTES);
        $peerPublicKey = $this->decodeKey($peerPublicKeyB64, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES);

        // crypto_box keypairs are X25519 keys, directly usable with
        // scalarmult — sodium_crypto_box_seal()/_seal_open() and
        // sodium_crypto_scalarmult() operate on the same key format.
        return sodium_crypto_scalarmult($privateKey, $peerPublicKey);
    }

    private function decodeKey(string $keyB64, int $expectedLength): string
    {
        $decoded = base64_decode($keyB64, true);

        if ($decoded === false || strlen($decoded) !== $expectedLength) {
            throw new InvalidEccPrivateKeyException();
        }

        return $decoded;
    }
}
