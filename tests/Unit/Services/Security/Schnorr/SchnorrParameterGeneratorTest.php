<?php

namespace Tests\Unit\Services\Security\Schnorr;

use App\Services\Security\Schnorr\SchnorrParameterGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reference: crypto_design.md §4.4.2
 *
 * Exercises the REAL generator (not the fake fixed parameters used
 * elsewhere), but at small bit sizes — a real 2048/256-bit run is a
 * deployment-time operation, not something to run on every test
 * invocation. This test instead verifies the *math is correct* at a
 * size that completes in well under a second.
 */
class SchnorrParameterGeneratorTest extends TestCase
{
    #[Test]
    public function generated_parameters_satisfy_the_schnorr_group_invariants(): void
    {
        $generator = new SchnorrParameterGenerator();

        $params = $generator->generate(pBits: 64, qBits: 24);

        $p = gmp_init($params->p, 10);
        $q = gmp_init($params->q, 10);
        $alpha = gmp_init($params->alpha, 10);

        $this->assertTrue(gmp_prob_prime($p) > 0, 'p must be prime');
        $this->assertTrue(gmp_prob_prime($q) > 0, 'q must be prime');

        // q must divide (p - 1)
        $this->assertSame(
            '0',
            gmp_strval(gmp_mod(gmp_sub($p, 1), $q)),
            'q must divide (p - 1)',
        );

        // alpha must have order q: alpha^q mod p == 1, alpha != 1
        $this->assertSame('1', gmp_strval(gmp_powm($alpha, $q, $p)));
        $this->assertNotSame('1', gmp_strval($alpha));
    }

    #[Test]
    public function rejects_qbits_greater_than_or_equal_to_pbits(): void
    {
        $generator = new SchnorrParameterGenerator();

        $this->expectException(\InvalidArgumentException::class);

        $generator->generate(pBits: 64, qBits: 64);
    }
}
