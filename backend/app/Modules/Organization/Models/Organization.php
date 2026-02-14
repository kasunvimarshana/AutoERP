<?php

namespace App\Modules\Organization\Models;

use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Organization Model
 * 
 * Represents organizations (vendors, customers, internal) within a tenant.
 */
class Organization extends Model
{
    use TenantAware, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'tax_id',
        'registration_number',
        'is_active',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(\App\Modules\Organization\Models\Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isVendor(): bool
    {
        return $this->type === 'vendor';
    }

    public function isCustomer(): bool
    {
        return $this->type === 'customer';
    }
}
