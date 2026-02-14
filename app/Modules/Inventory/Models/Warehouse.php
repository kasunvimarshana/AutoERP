<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\TenantScoped;
use App\Modules\Tenancy\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Warehouse Model
 * 
 * Represents a warehouse for stock storage
 */
class Warehouse extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the tenant that owns this warehouse (via TenantScoped)
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Get the branch for this warehouse
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get locations in this warehouse
     */
    public function locations(): HasMany
    {
        return $this->hasMany(StockLocation::class);
    }

    /**
     * Get stock ledger entries for this warehouse
     */
    public function stockLedger(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }
}
