<?php

namespace App\Modules\Organization\Models;

use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Branch Model
 * 
 * Represents branches/locations of an organization.
 */
class Branch extends Model
{
    use TenantAware, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'organization_id',
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
        'latitude',
        'longitude',
        'timezone',
        'currency',
        'is_active',
        'is_primary',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'settings' => 'array',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isPrimary(): bool
    {
        return $this->is_primary;
    }
}
