<?php

namespace App\Services\Security\Schnorr;

/**
 * Generates random arbitrary-precision integers using PHP's CSPRNG
 * (random_bytes), converted to GMP via gmp_import.
 *
 * IMPORTANT: GMP's own gmp_random_bits()/gmp_random_range() are
 * explicitly documented as NOT cryptographically secure ("must not
 * be used for cryptographic purposes, or purposes that require
 * returned values to be unguessable" — php.net). They are fine for
 * public, non-secret values, but private keys (x) and per-signature
 * nonces (k) must be unpredictable, so this class routes all
 * signature/key-relevant randomness through random_bytes() instead.
 */
final class SecureGmpRandom
{
    /**
     * A uniformly random integer in the range [1, $max - 1] (i.e.
     * strictly between 0 and $max), using rejection sampling to
     * avoid modulo bias.
     */
    public static function belowExclusive(\GMP $max): \GMP
    {
        $bits = self::bitLength($max);
        $byteLength = (int) ceil($bits / 8);

        do {
            $bytes = random_bytes($byteLength);
            $candidate = gmp_import($bytes, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);
        } while (gmp_cmp($candidate, $max) >= 0 || gmp_cmp($candidate, 1) < 0);

        return $candidate;
    }

    /**
     * A uniformly random integer with exactly $bits bits (top bit
     * forced to 1), suitable for prime-candidate search.
     */
    public static function exactBitLength(int $bits): \GMP
    {
        $byteLength = (int) ceil($bits / 8);
        $bytes = random_bytes($byteLength);

        $value = gmp_import($bytes, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);

        // Trim down to exactly $bits bits, then force the top bit so
        // the result has precisely the requested bit length.
        // NOTE: gmp_setbit() mutates its argument BY REFERENCE and
        // returns null — it must NOT be reassigned to $value.
        $value = gmp_and($value, gmp_sub(gmp_pow(2, $bits), 1));
        gmp_setbit($value, $bits - 1);

        return $value;
    }

    /**
     * Bit length of a positive GMP integer. NOTE: PHP's GMP
     * extension has no gmp_sizeinbase() function (that's the
     * underlying C library's mpz_sizeinbase, with no PHP binding) —
     * bit length is computed via the base-2 string representation
     * instead.
     */
    private static function bitLength(\GMP $n): int
    {
        return strlen(gmp_strval($n, 2));
    }
}
