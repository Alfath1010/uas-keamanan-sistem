<?php

namespace App\Services\Security\Schnorr;

/**
 * Reference: crypto_design.md §4.4 (Schnorr Digital Signatures)
 *
 * Classical discrete-log Schnorr signatures over the multiplicative
 * group (Z/pZ)*, using the server-wide (p, q, alpha) domain
 * parameters from SchnorrParameterProviderInterface.
 *
 * Key pair relationship: public key y = alpha^x mod p, where x is
 * the private key, 1 <= x <= q-1.
 *
 * Signature scheme (matches crypto_design.md §4.4.4/§4.4.5 exactly):
 *   Sign(M, x):
 *     k <-$ [1, q-1]
 *     r = alpha^k mod p
 *     e = H(M || r) mod q
 *     s = (k - x*e) mod q
 *     signature = (e, s)
 *   Verify(M, (e, s), y):
 *     r_v = alpha^s * y^e mod p
 *     e_v = H(M || r_v) mod q
 *     valid iff e_v == e
 */
interface SignatureServiceInterface
{
    /**
     * Generate a new Schnorr key pair relative to the current domain
     * parameters. As with ECCServiceInterface::generateKeyPair(), the
     * private key half is intended for client-side use only
     * (FR-009) — the server SHALL NOT persist it.
     *
     * @return array{public_key: string, private_key: string} Decimal strings.
     */
    public function generateKeyPair(): array;

    /**
     * @return array{e: string, s: string} Decimal strings.
     */
    public function sign(string $message, string $privateKeyDecimal): array;

    public function verify(string $message, array $signature, string $publicKeyDecimal): bool;
}
