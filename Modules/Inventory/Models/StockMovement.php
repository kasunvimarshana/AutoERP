<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Inventory\Enums\StockMovementType;
use Modules\Product\Models\Product;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * StockMovement Model
 *
 * Represents all inventory transactions including receipts, issues, transfers, and adjustments.
 * Provides a complete audit trail of all stock movements with polymorphic references.
 *
 * @property string $id
 * @property string $tenant_id
 * @property StockMovementType $type
 * @property string $product_id
 * @property string|null $from_warehouse_id
 * @property string|null $to_warehouse_id
 * @property string|null $from_location_id
 * @property string|null $to_location_id
 * @property string $quantity
 * @property string|null $cost
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string|null $batch_lot_id
 * @property string|null $serial_number_id
 * @property string|null $movement_date
 * @property string|null $document_number
 * @property string|null $notes
 * @property string|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class StockMovement extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'type',
        'product_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'cost',
        'reference_type',
        'reference_id',
        'batch_lot_id',
        'serial_number_id',
        'movement_date',
        'document_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'type' => StockMovementType::class,
        'quantity' => 'decimal:6',
        'cost' => 'decimal:6',
        'movement_date' => 'datetime',
    ];

    /**
     * Get the product for this movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the source warehouse.
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the destination warehouse.
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Get the source location.
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'from_location_id');
    }

    /**
     * Get the destination location.
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'to_location_id');
    }

    /**
     * Get the batch lot for this movement.
     */
    public function batchLot(): BelongsTo
    {
        return $this->belongsTo(BatchLot::class);
    }

    /**
     * Get the serial number for this movement.
     */
    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class);
    }

    /**
     * Get the reference model (polymorphic relationship).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the warehouse affected by this movement.
     */
    public function getAffectedWarehouseId(): ?string
    {
        return match ($this->type) {
            StockMovementType::RECEIPT, StockMovementType::RETURN, StockMovementType::RELEASED => $this->to_warehouse_id,
            StockMovementType::ISSUE, StockMovementType::SCRAP, StockMovementType::RESERVED => $this->from_warehouse_id,
            StockMovementType::TRANSFER => $this->from_warehouse_id, // Primary warehouse
            StockMovementType::ADJUSTMENT, StockMovementType::COUNT => $this->to_warehouse_id ?? $this->from_warehouse_id,
            default => null,
        };
    }

    /**
     * Check if movement increases stock.
     */
    public function increasesStock(): bool
    {
        return $this->type->increasesStock();
    }

    /**
     * Check if movement decreases stock.
     */
    public function decreasesStock(): bool
    {
        return $this->type->decreasesStock();
    }

    /**
     * Get the total value of this movement.
     */
    public function getTotalValue(): ?string
    {
        if ($this->cost === null) {
            return null;
        }

        return bcmul((string) $this->quantity, (string) $this->cost, 6);
    }
}
