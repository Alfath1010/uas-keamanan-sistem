<?php

namespace App\Providers;

use App\Services\CryptoManager;
use App\Services\CryptoManagerInterface;
use App\Services\Security\ALS\ALSService;
use App\Services\Security\ALS\ALSServiceInterface;
use App\Services\Security\BlockCipher\BlockCipherService;
use App\Services\Security\BlockCipher\BlockCipherServiceInterface;
use App\Services\Security\ECC\ECCService;
use App\Services\Security\ECC\ECCServiceInterface;
use App\Services\Security\KeyManagement\KeyManagerService;
use App\Services\Security\KeyManagement\KeyManagerServiceInterface;
use App\Services\Security\Schnorr\FileSchnorrParameterProvider;
use App\Services\Security\Schnorr\SchnorrParameterProviderInterface;
use App\Services\Security\Schnorr\SignatureService;
use App\Services\Security\Schnorr\SignatureServiceInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Binds Security Layer contracts to their concrete implementations
 * (architecture.md §3.3 — Security Layer), plus the CryptoManager
 * façade (§3.7) that Business Layer services depend on directly.
 */
class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlockCipherServiceInterface::class, BlockCipherService::class);
        $this->app->bind(ECCServiceInterface::class, ECCService::class);

        $this->app->singleton(SchnorrParameterProviderInterface::class, FileSchnorrParameterProvider::class);
        $this->app->bind(SignatureServiceInterface::class, SignatureService::class);

        $this->app->bind(ALSServiceInterface::class, ALSService::class);
        $this->app->bind(KeyManagerServiceInterface::class, KeyManagerService::class);

        $this->app->bind(CryptoManagerInterface::class, CryptoManager::class);
    }
}
