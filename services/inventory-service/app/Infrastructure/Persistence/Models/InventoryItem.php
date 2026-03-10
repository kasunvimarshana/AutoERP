<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * InventoryItem Eloquent Model
 *
 * Tracks stock levels per product per warehouse per tenant.
 */
class InventoryItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'inventory_items';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'reorder_point',
        'reorder_quantity',
        'unit_cost',
        'currency',
        'location',
        'batch_number',
        'expiry_date',
        'metadata',
    ];

    protected $casts = [
        'quantity_on_hand'   => 'integer',
        'quantity_reserved'  => 'integer',
        'quantity_available' => 'integer',
        'reorder_point'      => 'integer',
        'reorder_quantity'   => 'integer',
        'unit_cost'          => 'float',
        'metadata'           => 'array',
        'expiry_date'        => 'date',
    ];

    public function scopeForTenant(\Illuminate\Database\Eloquent\Builder $query, string $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Computed: available quantity = on_hand - reserved.
     * Stored for query performance; updated on every stock movement.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity_available <= $this->reorder_point;
    }
}
