<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\TenantScoped;
use App\Modules\Branch\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Warehouse Model
 *
 * Represents storage locations for inventory
 */
class Warehouse extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'code',
        'location',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'capacity',
        'is_active',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the warehouse
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Get the branch associated with this warehouse
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get stock records for this warehouse
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get stock movements for this warehouse
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if warehouse is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
