<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleJobCardModel;

class VehicleServiceTaskModel extends Model
{
    protected $table = 'vehicle_service_tasks';

    protected $fillable = [
        'tenant_id',
        'job_card_id',
        'task_name',
        'task_status',
        'estimated_hours',
        'actual_hours',
        'labor_rate',
        'labor_cost',
        'notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'job_card_id' => 'integer',
        'estimated_hours' => 'decimal:6',
        'actual_hours' => 'decimal:6',
        'labor_rate' => 'decimal:6',
        'labor_cost' => 'decimal:6',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(VehicleJobCardModel::class, 'job_card_id');
    }
}
