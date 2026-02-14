<?php

namespace App\Modules\Branch\Models;

use App\Core\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Branch Model
 *
 * Represents organizational branches/locations within a tenant
 * Supports hierarchical structure with parent-child relationships
 */
class Branch extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'manager_id',
        'name',
        'code',
        'type',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the tenant that owns the branch
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Get the parent branch
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'parent_id');
    }

    /**
     * Get the child branches
     */
    public function children(): HasMany
    {
        return $this->hasMany(Branch::class, 'parent_id');
    }

    /**
     * Get the branch manager
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Check if branch is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
