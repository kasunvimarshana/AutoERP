<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory, HasUuids;

    public const TYPE_RECEIPT      = 'receipt';
    public const TYPE_ISSUE        = 'issue';
    public const TYPE_TRANSFER_IN  = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_ADJUSTMENT   = 'adjustment';
    public const TYPE_RESERVATION  = 'reservation';
    public const TYPE_RELEASE      = 'release';
    public const TYPE_COMMIT       = 'commit';

    public const UPDATED_AT = null; // Movements are immutable

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'type',
        'quantity',
        'before_quantity',
        'after_quantity',
        'reference_id',
        'reference_type',
        'notes',
        'metadata',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'quantity'        => 'decimal:4',
        'before_quantity' => 'decimal:4',
        'after_quantity'  => 'decimal:4',
        'metadata'        => 'array',
        'performed_at'    => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->performed_at)) {
                $model->performed_at = now();
            }
        });
    }
}
