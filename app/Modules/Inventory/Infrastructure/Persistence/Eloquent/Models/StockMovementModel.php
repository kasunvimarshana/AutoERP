<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class StockMovementModel extends BaseModel
{
    use HasTenant;

    protected $table = 'stock_movements';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_id',
        'variant_id',
        'location_id',
        'batch_lot_id',
        'serial_number_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'reference',
        'notes',
        'moved_by',
        'moved_at',
    ];

    protected $casts = [
        'quantity'   => 'decimal:6',
        'unit_cost'  => 'decimal:6',
        'moved_at'   => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->moved_at)) {
                $model->moved_at = now();
            }
        });
    }
}
