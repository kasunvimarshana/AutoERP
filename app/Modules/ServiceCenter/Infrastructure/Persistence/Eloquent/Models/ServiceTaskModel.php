<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTaskModel extends Model
{
    protected $table = 'service_tasks';

    protected $fillable = [
        'id',
        'service_order_id',
        'task_name',
        'description',
        'status',
        'labor_cost',
        'labor_minutes',
    ];

    protected $casts = [
        'labor_cost' => 'decimal:6',
        'labor_minutes' => 'integer',
    ];

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrderModel::class, 'service_order_id', 'id');
    }
}
