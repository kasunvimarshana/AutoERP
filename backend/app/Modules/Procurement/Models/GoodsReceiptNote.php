<?php

namespace App\Modules\Procurement\Models;

use App\Models\Tenant;
use App\Modules\Procurement\Enums\GoodsReceiptStatus;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Goods Receipt Note Model
 *
 * Represents a physical receipt of goods from a supplier against a purchase order.
 * Tracks what was actually received, enabling 3-way matching (PO-GRN-Invoice).
 */
class GoodsReceiptNote extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'grn_number',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'received_date',
        'received_by',
        'status',
        'notes',
        'total_quantity',
        'discrepancy_notes',
    ];

    protected $casts = [
        'received_date' => 'date',
        'status' => GoodsReceiptStatus::class,
    ];

    /**
     * Get the tenant that owns the goods receipt note
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the purchase order associated with this GRN
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the supplier associated with this GRN
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the items associated with this GRN
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptNoteItem::class);
    }

    /**
     * Check if the GRN has any discrepancies
     */
    public function hasDiscrepancies(): bool
    {
        return $this->items()->whereColumn('quantity_received', '!=', 'quantity_ordered')->exists();
    }

    /**
     * Calculate total received quantity
     */
    public function calculateTotalQuantity(): int
    {
        return $this->items()->sum('quantity_received');
    }
}
