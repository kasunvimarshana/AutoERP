<?php

namespace App\Modules\Reporting\Models;

use App\Models\Tenant;
use App\Models\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dashboard Model
 *
 * Represents a user dashboard with widget configurations.
 */
class Dashboard extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Reporting\DashboardFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'description',
        'layout_config',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'layout_config' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Get the tenant that owns the dashboard
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who owns the dashboard
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the widgets for the dashboard
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class);
    }

    /**
     * Check if dashboard is default
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }
}
