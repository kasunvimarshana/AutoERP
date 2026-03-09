<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Movement Model
 *
 * Immutable audit log of all stock changes.
 * Movement types: IN, OUT, RESERVE, RELEASE, ADJUSTMENT, RETURN, TRANSFER
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string $warehouse_id
 * @property string $movement_type
 * @property int $quantity
 * @property string|null $reference_id   Order ID, Saga ID, etc.
 * @property string|null $reference_type  'order', 'saga', 'adjustment'
 * @property string|null $notes
 */
class StockMovement extends Model
{
    use HasUuids;

    protected $table = 'stock_movements';

    // Immutable - no updates allowed
    public $timestamps = true;
    protected $guarded = [];

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'movement_type',
        'quantity',
        'reference_id',
        'reference_type',
        'quantity_before',
        'quantity_after',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
    ];

    /**
     * Valid movement types.
     */
    public const TYPES = [
        'IN', 'OUT', 'RESERVE', 'RELEASE', 'ADJUSTMENT', 'RETURN', 'TRANSFER'
    ];

    /**
     * Get the product for this movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
