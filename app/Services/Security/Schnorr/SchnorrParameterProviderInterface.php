<?php

namespace App\Services\Security\Schnorr;

/**
 * Reference: crypto_design.md §4.4.2, api_spec.md §6.8
 * (GET /schnorr/parameters)
 *
 * The (p, q, alpha) group is generated ONCE per deployment (it is
 * expensive to compute and, unlike per-user keys, is meant to be
 * shared by every user), then persisted and served to clients.
 *
 * This is intentionally NOT modeled as a database table: it is
 * system-wide configuration analogous to APP_KEY, not per-record
 * application data, so db_design.md's schema does not include it.
 * The default implementation persists it as a single JSON file under
 * storage/app (see FileSchnorrParameterProvider).
 */
interface SchnorrParameterProviderInterface
{
    /**
     * Load the currently active parameters.
     *
     * @throws \RuntimeException if parameters have not been generated yet.
     */
    public function current(): SchnorrParameters;

    public function exists(): bool;

    public function store(SchnorrParameters $parameters): void;
}
