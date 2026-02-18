<?php

namespace App\Modules\Inventory\Models;

use App\Models\Tenant;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Warehouse Model
 *
 * Represents a physical warehouse/location for storing inventory.
 */
class Warehouse extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'type',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the tenant that owns the warehouse.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope for active warehouses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
