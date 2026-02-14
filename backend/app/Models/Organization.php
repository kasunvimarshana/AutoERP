<?php

namespace App\Models;

use App\Base\BaseModel;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Organization/Branch Model
 * 
 * Represents organizational units or branches within a tenant
 */
class Organization extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'description',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'tax_id',
        'is_active',
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Tenant relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Customers relationship
     */
    public function customers(): HasMany
    {
        return $this->hasMany(\App\Modules\Customer\Models\Customer::class, 'branch_id');
    }

    /**
     * Vehicles relationship
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(\App\Modules\Vehicle\Models\Vehicle::class, 'branch_id');
    }

    /**
     * Check if organization is active
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Scope: Active organizations
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
