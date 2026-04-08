<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class OrderLineModel extends BaseModel
{
    protected $table = 'order_lines';

    protected $fillable = [
        'uuid',
        'order_id',
        'product_id',
        'variant_id',
        'line_number',
        'description',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'total',
        'quantity_received',
        'quantity_delivered',
        'batch_lot_id',
        'serial_number_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'quantity'           => 'decimal:6',
        'unit_price'         => 'decimal:6',
        'discount_percent'   => 'decimal:2',
        'discount_amount'    => 'decimal:6',
        'tax_rate'           => 'decimal:2',
        'tax_amount'         => 'decimal:6',
        'subtotal'           => 'decimal:6',
        'total'              => 'decimal:6',
        'quantity_received'  => 'decimal:6',
        'quantity_delivered' => 'decimal:6',
        'metadata'           => 'array',
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
