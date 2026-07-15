<?php

namespace Tests\Support;

use App\Services\Security\Schnorr\SchnorrParameters;
use App\Services\Security\Schnorr\SchnorrParameterProviderInterface;

/**
 * Reference: testing_spec.md §7.3 (Schnorr unit tests)
 *
 * A real 2048-bit/256-bit parameter set (crypto_design.md §4.4.2)
 * takes far too long to generate on every test run. This test double
 * substitutes a small (128-bit p / 64-bit q) but GENUINELY VALID
 * parameter set — precomputed once offline and verified to satisfy
 * q | (p-1) and alpha^q ≡ 1 (mod p) — so signature math is exercised
 * for real, just at a size safe only for testing, never production.
 */
class FakeSchnorrParameterProvider implements SchnorrParameterProviderInterface
{
    // Precomputed and verified (see project notes): p = q*j + 1 is
    // prime, q is prime, alpha has order q mod p.
    private const P = '188976727030546019885223510357946405207';
    private const Q = '10509875596903072259';
    private const ALPHA = '188497437765792115011680910380976532946';

    private bool $stored = false;

    public function current(): SchnorrParameters
    {
        return new SchnorrParameters(self::P, self::Q, self::ALPHA);
    }

    public function exists(): bool
    {
        return true;
    }

    public function store(SchnorrParameters $parameters): void
    {
        $this->stored = true;
    }
}
