<?php

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CycleCountLineModel extends Model
{
    use HasUuids;

    protected $table = 'inventory_cycle_count_lines';

    protected $fillable = [
        'id',
        'cycle_count_id',
        'tenant_id',
        'product_id',
        'expected_qty',
        'counted_qty',
        'notes',
    ];

    protected $casts = [
        'expected_qty' => 'string',
        'counted_qty'  => 'string',
    ];
}
