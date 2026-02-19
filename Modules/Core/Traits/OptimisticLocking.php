<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * OptimisticLocking Trait
 *
 * Implements optimistic locking via version field
 */
trait OptimisticLocking
{
    /**
     * Boot the optimistic locking trait
     */
    protected static function bootOptimisticLocking(): void
    {
        static::creating(function (Model $model) {
            if (! $model->getAttribute('version')) {
                $model->setAttribute('version', 1);
            }
        });

        static::updating(function (Model $model) {
            $originalVersion = $model->getOriginal('version');
            $model->setAttribute('version', $originalVersion + 1);

            // Add version check to the update query
            static::addVersionWhereClause($model, $originalVersion);
        });

        static::updated(function (Model $model) {
            // Verify the update actually happened
            if ($model->wasChanged() && ! static::versionWasUpdated($model)) {
                throw new \RuntimeException(
                    'Optimistic lock exception: Record was modified by another transaction'
                );
            }
        });
    }

    /**
     * Add version check to query
     */
    protected static function addVersionWhereClause(Model $model, int $originalVersion): void
    {
        $model->newQuery()
            ->where($model->getKeyName(), $model->getKey())
            ->where('version', $originalVersion)
            ->limit(1);
    }

    /**
     * Check if version was updated
     */
    protected static function versionWasUpdated(Model $model): bool
    {
        $currentVersion = $model->getAttribute('version');
        $originalVersion = $model->getOriginal('version');

        return $currentVersion > $originalVersion;
    }

    /**
     * Get the version column name
     */
    public function getVersionColumn(): string
    {
        return 'version';
    }
}
