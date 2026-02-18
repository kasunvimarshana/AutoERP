<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Support\Str;

/**
 * HasUuid Trait
 *
 * Automatically generates UUIDs for model primary keys.
 * This follows multi-tenant best practices where UUIDs prevent
 * ID enumeration attacks and allow distributed systems.
 *
 * Usage:
 * 1. Add trait to your model: use HasUuid;
 * 2. Ensure your model's primary key is a string type
 * 3. Set $incrementing = false in your model
 * 4. Set $keyType = 'string' in your model
 *
 * Migration example:
 * $table->uuid('id')->primary();
 *
 * Model example:
 * protected $keyType = 'string';
 * public $incrementing = false;
 */
trait HasUuid
{
    /**
     * Boot the HasUuid trait for a model.
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
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
