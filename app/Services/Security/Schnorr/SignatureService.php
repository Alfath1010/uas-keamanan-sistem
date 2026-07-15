<?php

namespace App\Services\Security\Schnorr;

/**
 * Implements SignatureServiceInterface using GMP for all modular
 * arithmetic (gmp_powm for exponentiation, gmp_mod for reduction) and
 * PHP's native hash() for the Fiat-Shamir challenge. The signing/
 * verification protocol logic (combining r, e, s per
 * crypto_design.md §4.4.4/§4.4.5) is the only "custom" code here —
 * all actual big-integer math and hashing is delegated to vetted
 * primitives.
 *
 * Reference: crypto_design.md §4.4
 */
class SignatureService implements SignatureServiceInterface
{
    public function __construct(
        private readonly SchnorrParameterProviderInterface $parameters,
    ) {}

    public function generateKeyPair(): array
    {
        $params = $this->parameters->current();
        $p = gmp_init($params->p, 10);
        $q = gmp_init($params->q, 10);
        $alpha = gmp_init($params->alpha, 10);

        // Private key x in [1, q-1].
        $x = SecureGmpRandom::belowExclusive($q);

        // Public key y = alpha^x mod p.
        $y = gmp_powm($alpha, $x, $p);

        return [
            'public_key' => gmp_strval($y),
            'private_key' => gmp_strval($x),
        ];
    }

    public function sign(string $message, string $privateKeyDecimal): array
    {
        $params = $this->parameters->current();
        $p = gmp_init($params->p, 10);
        $q = gmp_init($params->q, 10);
        $alpha = gmp_init($params->alpha, 10);
        $x = gmp_init($privateKeyDecimal, 10);

        // Nonce k in [1, q-1]. MUST be fresh and unpredictable per
        // signature: reuse or predictability of k directly leaks the
        // private key x (classic Schnorr/DSA nonce-reuse attack),
        // which is why this goes through SecureGmpRandom (CSPRNG)
        // rather than GMP's non-cryptographic RNG.
        $k = SecureGmpRandom::belowExclusive($q);

        $r = gmp_powm($alpha, $k, $p);
        $e = $this->hashToZq($message, $r, $q);

        // s = (k - x*e) mod q
        $s = gmp_mod(gmp_sub($k, gmp_mul($x, $e)), $q);

        return [
            'e' => gmp_strval($e),
            's' => gmp_strval($s),
        ];
    }

    public function verify(string $message, array $signature, string $publicKeyDecimal): bool
    {
        $params = $this->parameters->current();
        $p = gmp_init($params->p, 10);
        $q = gmp_init($params->q, 10);
        $alpha = gmp_init($params->alpha, 10);
        $y = gmp_init($publicKeyDecimal, 10);

        if (! isset($signature['e'], $signature['s'])) {
            return false;
        }

        $e = gmp_init($signature['e'], 10);
        $s = gmp_init($signature['s'], 10);

        // r_v = alpha^s * y^e mod p
        $rv = gmp_mod(
            gmp_mul(gmp_powm($alpha, $s, $p), gmp_powm($y, $e, $p)),
            $p
        );

        $ev = $this->hashToZq($message, $rv, $q);

        return gmp_cmp($ev, $e) === 0;
    }

    /**
     * H(M || r) mod q, per crypto_design.md §4.4.4
     * ("e = H(M||r), where H is a cryptographic hash function
     * H : {0,1}* -> Z_q"). Uses SHA-256, reduced mod q.
     */
    private function hashToZq(string $message, \GMP $r, \GMP $q): \GMP
    {
        $rBytes = gmp_export($r, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);
        $digest = hash('sha256', $message.$rBytes, binary: true);
        $asInt = gmp_import($digest, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);

        return gmp_mod($asInt, $q);
    }
}
