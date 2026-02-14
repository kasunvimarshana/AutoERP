<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="StockLedger",
 *     type="object",
 *     title="StockLedger",
 *     description="Stock ledger model for append-only stock movements (immutable)",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid"),
 *     @OA\Property(property="branch_id", type="string", format="uuid"),
 *     @OA\Property(property="inventory_id", type="string", format="uuid"),
 *     @OA\Property(property="product_variant_id", type="string", format="uuid"),
 *     @OA\Property(property="transaction_type", type="string", enum={"in", "out", "adjustment", "transfer"}),
 *     @OA\Property(property="quantity", type="integer", example=50),
 *     @OA\Property(property="unit_cost", type="number", format="float", example=10.50),
 *     @OA\Property(property="reference_type", type="string", example="order"),
 *     @OA\Property(property="reference_id", type="string", format="uuid"),
 *     @OA\Property(property="batch_number", type="string", example="BATCH-001"),
 *     @OA\Property(property="lot_number", type="string", example="LOT-001"),
 *     @OA\Property(property="expiry_date", type="string", format="date"),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="created_by", type="string", format="uuid"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class StockLedger extends Model
{
    const UPDATED_AT = null;
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'stock_ledger';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'inventory_id',
        'product_variant_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'batch_number',
        'lot_number',
        'expiry_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });

        static::updating(function ($model) {
            throw new \Exception('Stock ledger entries are immutable and cannot be updated');
        });

        static::deleting(function ($model) {
            throw new \Exception('Stock ledger entries are immutable and cannot be deleted');
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'branch_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function isStockIn(): bool
    {
        return $this->transaction_type === 'in';
    }

    public function isStockOut(): bool
    {
        return $this->transaction_type === 'out';
    }

    public function isAdjustment(): bool
    {
        return $this->transaction_type === 'adjustment';
    }

    public function isTransfer(): bool
    {
        return $this->transaction_type === 'transfer';
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByVariant($query, string $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByBatch($query, string $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function scopeByDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
