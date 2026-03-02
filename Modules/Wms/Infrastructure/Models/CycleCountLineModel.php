<?php

declare(strict_types=1);

namespace Modules\Wms\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class CycleCountLineModel extends Model
{
    use BelongsToTenant;

    protected $table = 'wms_cycle_count_lines';

    protected $fillable = [
        'cycle_count_id',
        'tenant_id',
        'product_id',
        'bin_id',
        'system_qty',
        'counted_qty',
        'variance',
        'notes',
    ];

    protected $casts = [
        'system_qty' => 'string',
        'counted_qty' => 'string',
        'variance' => 'string',
    ];
}
