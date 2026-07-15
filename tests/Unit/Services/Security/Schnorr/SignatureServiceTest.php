<?php

namespace Tests\Unit\Services\Security\Schnorr;

use App\Services\Security\Schnorr\SignatureService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeSchnorrParameterProvider;
use Tests\TestCase;

/**
 * Reference: testing_spec.md §7.3 (Schnorr unit tests, SCH-001..004)
 *
 * Uses FakeSchnorrParameterProvider (small but genuinely valid domain
 * parameters) rather than real 2048-bit parameters, purely for test
 * speed — see that class's docblock.
 */
class SignatureServiceTest extends TestCase
{
    private SignatureService $signer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signer = new SignatureService(new FakeSchnorrParameterProvider());
    }

    #[Test]
    public function sch_001_sign_message_produces_a_signature(): void
    {
        $pair = $this->signer->generateKeyPair();

        $signature = $this->signer->sign('a message worth signing', $pair['private_key']);

        $this->assertArrayHasKey('e', $signature);
        $this->assertArrayHasKey('s', $signature);
        $this->assertNotSame('', $signature['e']);
        $this->assertNotSame('', $signature['s']);
    }

    #[Test]
    public function sch_002_verify_signature_succeeds(): void
    {
        $pair = $this->signer->generateKeyPair();
        $message = 'a message worth signing';
        $signature = $this->signer->sign($message, $pair['private_key']);

        $valid = $this->signer->verify($message, $signature, $pair['public_key']);

        $this->assertTrue($valid);
    }

    #[Test]
    public function sch_003_modified_message_fails_verification(): void
    {
        $pair = $this->signer->generateKeyPair();
        $signature = $this->signer->sign('original message', $pair['private_key']);

        $valid = $this->signer->verify('tampered message', $signature, $pair['public_key']);

        $this->assertFalse($valid);
    }

    #[Test]
    public function sch_004_wrong_public_key_fails_verification(): void
    {
        $signer = $this->signer->generateKeyPair();
        $imposter = $this->signer->generateKeyPair();
        $message = 'a message worth signing';
        $signature = $this->signer->sign($message, $signer['private_key']);

        $valid = $this->signer->verify($message, $signature, $imposter['public_key']);

        $this->assertFalse($valid);
    }

    #[Test]
    public function two_signatures_of_the_same_message_use_different_nonces(): void
    {
        // Guards against nonce reuse (a real Schnorr/DSA
        // vulnerability) regressing silently: if k were fixed or
        // predictable, signing the same message twice would produce
        // an identical (e, s) pair every time.
        $pair = $this->signer->generateKeyPair();
        $message = 'sign me twice';

        $first = $this->signer->sign($message, $pair['private_key']);
        $second = $this->signer->sign($message, $pair['private_key']);

        $this->assertNotSame($first['s'], $second['s']);
    }

    #[Test]
    public function malformed_signature_array_fails_verification_gracefully(): void
    {
        $pair = $this->signer->generateKeyPair();

        $valid = $this->signer->verify('a message', ['e' => 'not-enough-fields'], $pair['public_key']);

        $this->assertFalse($valid);
    }
}
