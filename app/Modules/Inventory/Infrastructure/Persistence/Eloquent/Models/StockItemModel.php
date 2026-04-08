<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class StockItemModel extends BaseModel
{
    use HasTenant, SoftDeletes;

    protected $table = 'stock_items';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_id',
        'variant_id',
        'location_id',
        'batch_lot_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'unit_cost',
        'status',
        'metadata',
    ];

    protected $casts = [
        'quantity_on_hand'   => 'decimal:6',
        'quantity_reserved'  => 'decimal:6',
        'quantity_available' => 'decimal:6',
        'unit_cost'          => 'decimal:6',
        'metadata'           => 'array',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function batchLot(): BelongsTo
    {
        return $this->belongsTo(BatchLotModel::class, 'batch_lot_id');
    }
}
