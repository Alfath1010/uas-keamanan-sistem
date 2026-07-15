<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Automatically populates the `uuid` column on model creation.
 *
 * Reference: db_design.md §5.1 — "all externally exposed resources
 * SHALL additionally possess a UUID that is used by the REST API."
 * and §5.4 notes — "UUIDs SHALL be generated automatically during
 * creation."
 *
 * Route-model binding also resolves on `uuid` rather than the internal
 * auto-incrementing `id`, per ADR-001 (public resources identified by
 * UUID, not sequential DB identifiers).
 */
trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Resolve route bindings using the public UUID instead of the
     * internal primary key.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
