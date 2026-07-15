<?php

namespace Tests\Support;

use App\Services\Security\Schnorr\SchnorrParameterProviderInterface;

/**
 * Swaps the real FileSchnorrParameterProvider (which requires
 * `php artisan schnorr:generate-parameters` to have been run, and is
 * slow even when it has) for the small precomputed test parameters,
 * for any feature test that exercises public-key upload/validation
 * (KeyManagerService checks Schnorr public keys against whatever
 * provider is bound).
 */
trait WithFakeSchnorrParameters
{
    protected function setUpFakeSchnorrParameters(): void
    {
        $this->app->bind(SchnorrParameterProviderInterface::class, FakeSchnorrParameterProvider::class);
    }
}
