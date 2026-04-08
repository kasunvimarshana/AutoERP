<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class WarehouseLocationModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'warehouse_locations';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'parent_id',
        'name',
        'code',
        'path',
        'level',
        'type',
        'is_active',
    ];

    protected $casts = [
        'level'     => 'integer',
        'is_active' => 'boolean',
    ];
}
