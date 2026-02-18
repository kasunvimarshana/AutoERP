<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    use HasFactory;

    // This is an append-only table
    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'batch_number',
        'lot_number',
        'serial_number',
        'manufacturing_date',
        'expiry_date',
        'reference_type',
        'reference_id',
        'reference_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'created_by',
        'notes',
        'balance_after',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'balance_after' => 'decimal:3',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the stock ledger entry.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the product for this stock ledger entry.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse for this stock ledger entry.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who created this entry.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if transaction increases stock.
     */
    public function isStockIn(): bool
    {
        return in_array($this->transaction_type, ['in', 'transfer_in', 'return']);
    }

    /**
     * Check if transaction decreases stock.
     */
    public function isStockOut(): bool
    {
        return in_array($this->transaction_type, ['out', 'transfer_out', 'damaged', 'expired']);
    }

    /**
     * Scope to filter by warehouse.
     */
    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope to filter by product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
