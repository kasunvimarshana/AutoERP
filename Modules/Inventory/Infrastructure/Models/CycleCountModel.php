<?php

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class CycleCountModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope;

    protected $table = 'inventory_cycle_counts';

    protected $fillable = [
        'id',
        'tenant_id',
        'warehouse_id',
        'location_id',
        'reference',
        'count_date',
        'status',
        'notes',
    ];
}
