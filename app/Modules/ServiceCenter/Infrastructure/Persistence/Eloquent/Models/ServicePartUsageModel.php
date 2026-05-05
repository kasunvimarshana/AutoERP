<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePartUsageModel extends Model
{
    protected $table = 'service_part_usages';

    protected $fillable = [
        'id',
        'service_order_id',
        'inventory_item_id',
        'part_name',
        'part_number',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:6',
        'total_cost' => 'decimal:6',
    ];

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrderModel::class, 'service_order_id', 'id');
    }
}
