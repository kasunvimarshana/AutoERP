<?php

namespace App\Core\Traits;

use Illuminate\Support\Str;

/**
 * UUID Trait
 * 
 * Automatically generates UUIDs for models.
 */
trait HasUuid
{
    /**
     * Boot the UUID trait
     *
     * @return void
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the key type
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Get incrementing state
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }
}
