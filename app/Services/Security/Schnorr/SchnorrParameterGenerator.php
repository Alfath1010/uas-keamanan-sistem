<?php

namespace App\Services\Security\Schnorr;

/**
 * Generates a fresh Schnorr group (p, q, alpha) using DSA-style
 * domain parameter construction:
 *
 *   1. q: a random prime of $qBits bits (the subgroup order).
 *   2. p: a prime of $pBits bits such that q | (p - 1), found by
 *      searching p = q*j + 1 for random even j until p is prime.
 *   3. alpha: a generator of the unique order-q subgroup of
 *      (Z/pZ)*, found as alpha = h^((p-1)/q) mod p for random h,
 *      retried until alpha != 1.
 *
 * All modular exponentiation and primality testing is delegated to
 * the GMP extension (gmp_powm, gmp_prob_prime) — this class contains
 * protocol/search logic only, no hand-rolled big-integer arithmetic,
 * per the project's "no crypto primitives from scratch" constraint.
 * Randomness for candidate search is drawn via SecureGmpRandom
 * (backed by random_bytes/CSPRNG) rather than GMP's own
 * gmp_random_bits()/gmp_random_range(), which PHP's manual
 * explicitly documents as NOT cryptographically secure.
 *
 * Reference: crypto_design.md §4.4.2 (Global Parameters)
 *
 * This is a one-time, deployment-level setup step (see
 * schnorr:generate-parameters console command), not something run
 * per-request — generation of a 2048-bit p can take anywhere from a
 * few seconds to over a minute depending on hardware, since it
 * involves repeated primality search.
 */
class SchnorrParameterGenerator
{
    /** Miller-Rabin rounds for gmp_prob_prime; 25 rounds is a common
     *  high-confidence choice (error probability roughly 4^-25). */
    private const PRIMALITY_CERTAINTY = 25;

    /** Safety cap so a pathological run cannot loop forever. */
    private const MAX_ATTEMPTS = 100_000;

    public function generate(int $pBits = 2048, int $qBits = 256): SchnorrParameters
    {
        if ($qBits >= $pBits) {
            throw new \InvalidArgumentException('qBits must be smaller than pBits.');
        }

        $q = $this->generatePrime($qBits);
        $p = $this->findPWithSubgroup($q, $pBits, $qBits);
        $alpha = $this->findGenerator($p, $q);

        return new SchnorrParameters(
            p: gmp_strval($p),
            q: gmp_strval($q),
            alpha: gmp_strval($alpha),
        );
    }

    /**
     * A random probable prime of exactly $bits bits (top bit set).
     */
    private function generatePrime(int $bits): \GMP
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $candidate = SecureGmpRandom::exactBitLength($bits);
            $candidate = gmp_or($candidate, gmp_init(1)); // force odd

            if (gmp_prob_prime($candidate, self::PRIMALITY_CERTAINTY) > 0) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Failed to generate a probable prime within the attempt budget.');
    }

    /**
     * Search for a prime p = q*j + 1 with exactly $pBits bits.
     */
    private function findPWithSubgroup(\GMP $q, int $pBits, int $qBits): \GMP
    {
        $jBits = $pBits - $qBits;

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $j = SecureGmpRandom::exactBitLength($jBits);
            // p = q*j + 1 must be odd => q*j must be even; q is odd,
            // so j must be even.
            if (gmp_testbit($j, 0)) {
                $j = gmp_add($j, 1);
            }

            $p = gmp_add(gmp_mul($q, $j), 1);

            if (strlen(gmp_strval($p, 2)) !== $pBits) {
                continue; // carry pushed us out of the target bit length
            }

            if (gmp_prob_prime($p, self::PRIMALITY_CERTAINTY) > 0) {
                return $p;
            }
        }

        throw new \RuntimeException('Failed to find a suitable prime p within the attempt budget.');
    }

    /**
     * Find alpha, a generator of the order-q subgroup of (Z/pZ)*.
     */
    private function findGenerator(\GMP $p, \GMP $q): \GMP
    {
        $exponent = gmp_div_q(gmp_sub($p, 1), $q);

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            // h in [2, p-2]: draw uniformly from [1, p-2) via
            // belowExclusive($p - 2) then shift up by 1.
            $h = gmp_add(SecureGmpRandom::belowExclusive(gmp_sub($p, 2)), 1);
            $alpha = gmp_powm($h, $exponent, $p);

            if (gmp_cmp($alpha, 1) !== 0) {
                return $alpha;
            }
        }

        throw new \RuntimeException('Failed to find a subgroup generator within the attempt budget.');
    }
}
