<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Activity Model
 *
 * Stores activity logs for all models using the LogsActivity trait.
 * Provides a centralized audit trail for compliance and debugging.
 *
 * This model works with the native LogsActivity trait in Modules/Core/Traits.
 * No external packages required - pure Laravel Eloquent.
 */
class Activity extends Model
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeInLog(Builder $query, string $logName): Builder
    {
        return $query->where('log_name', $logName);
    }

    public function scopeForSubject(Builder $query, string $subjectType): Builder
    {
        return $query->where('subject_type', $subjectType);
    }

    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query->where('causer_type', get_class($causer))
            ->where('causer_id', $causer->getKey());
    }

    public function getOldAttributes(): ?array
    {
        return $this->properties['old'] ?? null;
    }

    public function getNewAttributes(): ?array
    {
        return $this->properties['attributes'] ?? null;
    }

    public function getChanges(): ?array
    {
        $old = $this->getOldAttributes();
        $new = $this->getNewAttributes();

        if ($old === null || $new === null) {
            return null;
        }

        $changes = [];
        foreach ($new as $key => $value) {
            if (! isset($old[$key]) || $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }
}
