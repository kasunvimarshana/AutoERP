<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Warehouse Model
 *
 * Manages warehouse locations within branches.
 */
class Warehouse extends Model
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'code',
        'address',
        'manager_name',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Tenant::class);
    }

    /**
     * Get the branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Branch::class);
    }

    /**
     * Get all stock locations.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(StockLocation::class);
    }

    /**
     * Get all stock ledger entries.
     */
    public function stockLedger(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    /**
     * Scope active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
