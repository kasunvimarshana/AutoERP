<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'type',
        'status',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'contact_person',
        'contact_email',
        'contact_phone',
        'total_capacity',
        'capacity_unit',
        'parent_id',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'total_capacity' => 'decimal:2',
        'settings' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the warehouse.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the parent warehouse.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'parent_id');
    }

    /**
     * Get the child warehouses.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'parent_id');
    }

    /**
     * Get the stock ledger entries for the warehouse.
     */
    public function stockLedgers(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    /**
     * Check if warehouse is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope to filter active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
