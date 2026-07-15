<?php

namespace App\Services\Security\KeyManagement;

use App\Models\PublicKey;
use App\Models\User;

/**
 * Reference: architecture.md §3.3 (Security Layer components),
 * testing_spec.md §7.4 (KeyManagerService: Key import, Key export,
 * Validation, Storage)
 *
 * IMPORTANT: this service manages PUBLIC key material only.
 * Private keys never exist server-side (FR-009, ADR-007, SEC-005) —
 * "import/export" in the testing spec refers to client-side key
 * file handling (crypto_design.md §4.5, .ecpub/.ecprv/.scpub/.scprv),
 * which is a Presentation Layer concern outside this backend. This
 * service's job is validating the FORMAT of uploaded public keys
 * before they are persisted, so malformed keys fail fast with a
 * descriptive error rather than silently corrupting later
 * encryption/verification attempts.
 */
interface KeyManagerServiceInterface
{
    /**
     * Validate and store (create or replace, FT-KEY-002) a user's
     * ECC and Schnorr public keys.
     *
     * @throws \App\Exceptions\Crypto\InvalidPublicKeyException
     */
    public function uploadPublicKeys(User $user, string $eccPublicKeyB64, string $schnorrPublicKeyDecimal): PublicKey;

    public function getPublicKeys(User $user): ?PublicKey;
}
