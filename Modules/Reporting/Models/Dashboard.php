<?php

declare(strict_types=1);

namespace Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Tenant\Traits\TenantScoped;

/**
 * Dashboard Model
 *
 * Represents a dashboard container with multiple widgets
 */
class Dashboard extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'user_id',
        'name',
        'description',
        'layout',
        'is_default',
        'is_shared',
        'metadata',
    ];

    protected $casts = [
        'layout' => 'array',
        'metadata' => 'array',
        'is_default' => 'boolean',
        'is_shared' => 'boolean',
    ];

    /**
     * Get the user who owns this dashboard
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get widgets on this dashboard
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class)->orderBy('order');
    }

    /**
     * Set as default dashboard
     */
    public function setAsDefault(): void
    {
        // Remove default from other dashboards
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
