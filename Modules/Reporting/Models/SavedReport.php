<?php

declare(strict_types=1);

namespace Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Tenant\Traits\TenantScoped;

/**
 * SavedReport Model
 *
 * Represents user-saved reports with custom filters and preferences
 */
class SavedReport extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'user_id',
        'report_id',
        'name',
        'description',
        'filters',
        'parameters',
        'is_favorite',
        'last_accessed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'parameters' => 'array',
        'is_favorite' => 'boolean',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the user who saved this report
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the base report definition
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Mark as favorite
     */
    public function markAsFavorite(): void
    {
        $this->update(['is_favorite' => true]);
    }

    /**
     * Remove from favorites
     */
    public function removeFromFavorites(): void
    {
        $this->update(['is_favorite' => false]);
    }

    /**
     * Update last accessed timestamp
     */
    public function updateLastAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }
}
