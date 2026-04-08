<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ReturnLineModel extends BaseModel
{
    protected $table = 'return_lines';

    protected $fillable = [
        'uuid',
        'return_id',
        'order_line_id',
        'product_id',
        'variant_id',
        'batch_lot_id',
        'serial_number_id',
        'quantity_requested',
        'quantity_approved',
        'quantity_received',
        'unit_price',
        'subtotal',
        'quality_check_result',
        'quality_notes',
        'condition_notes',
        'restock_action',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:6',
        'quantity_approved'  => 'decimal:6',
        'quantity_received'  => 'decimal:6',
        'unit_price'         => 'decimal:6',
        'subtotal'           => 'decimal:6',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
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
}
