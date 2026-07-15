<?php

namespace App\Services\Security\Schnorr;

use Illuminate\Support\Facades\File;

/**
 * Persists the global (p, q, alpha) group as JSON under
 * storage/app/private (outside the public disk). These values are
 * NOT secret — they are deliberately published via
 * GET /api/v1/schnorr/parameters (api_spec.md §6.8) — but they must
 * be stable across requests/deployments, since every user's Schnorr
 * key pair (crypto_design.md §4.4.3) is defined relative to them.
 * Regenerating them would invalidate every existing signature and
 * public key.
 */
class FileSchnorrParameterProvider implements SchnorrParameterProviderInterface
{
    private function path(): string
    {
        return storage_path('app/private/schnorr-parameters.json');
    }

    public function exists(): bool
    {
        return File::exists($this->path());
    }

    public function current(): SchnorrParameters
    {
        if (! $this->exists()) {
            throw new \RuntimeException(
                'Schnorr domain parameters have not been generated yet. '
                .'Run `php artisan schnorr:generate-parameters` once per deployment.'
            );
        }

        $data = json_decode(File::get($this->path()), true, flags: JSON_THROW_ON_ERROR);

        return SchnorrParameters::fromArray($data);
    }

    public function store(SchnorrParameters $parameters): void
    {
        File::ensureDirectoryExists(dirname($this->path()));

        File::put(
            $this->path(),
            json_encode($parameters->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }
}
