<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class WarehouseModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'description',
        'address',
        'capacity',
        'location_id',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'tenant_id'   => 'integer',
        'location_id' => 'integer',
        'capacity'    => 'float',
        'is_active'   => 'boolean',
    ];
}
