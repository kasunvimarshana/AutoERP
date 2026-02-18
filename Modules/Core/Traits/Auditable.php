<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Auditable Trait
 *
 * Automatically tracks who created and updated records.
 * Provides audit trail functionality for all models.
 *
 * This trait integrates with spatie/laravel-activitylog for comprehensive
 * audit trails. For simple created_by/updated_by tracking, use this trait.
 * For full activity logging, use spatie/laravel-activitylog directly.
 *
 * Usage:
 * 1. Add trait to your model: use Auditable;
 * 2. Ensure your model's table has created_by and updated_by columns
 * 3. The trait will automatically populate these fields
 *
 * Migration example:
 * $table->foreignId('created_by')->nullable()->constrained('users');
 * $table->foreignId('updated_by')->nullable()->constrained('users');
 */
trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     */
    protected static function bootAuditable(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && ! $model->created_by) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    /**
     * Get the user who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Get the user who last updated the record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }
}
