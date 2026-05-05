<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleJobCardModel;

class VehicleServicePartUsageModel extends Model
{
    protected $table = 'vehicle_service_part_usages';

    protected $fillable = [
        'tenant_id',
        'job_card_id',
        'service_task_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_cost',
        'line_total',
        'stock_movement_id',
        'description',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'job_card_id' => 'integer',
        'service_task_id' => 'integer',
        'product_id' => 'integer',
        'uom_id' => 'integer',
        'quantity' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'line_total' => 'decimal:6',
        'stock_movement_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(VehicleJobCardModel::class, 'job_card_id');
    }
}
