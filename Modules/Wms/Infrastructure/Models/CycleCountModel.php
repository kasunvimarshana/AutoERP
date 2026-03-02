<?php

declare(strict_types=1);

namespace Modules\Wms\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class CycleCountModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'wms_cycle_counts';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'status',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
