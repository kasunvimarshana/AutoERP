<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class CycleCountModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'cycle_counts';

    protected $fillable = [
        'tenant_id', 'warehouse_id', 'location_id', 'status',
        'scheduled_at', 'completed_at', 'notes',
    ];

    protected $casts = [
        'scheduled_at'  => 'datetime',
        'completed_at'  => 'datetime',
    ];
}
