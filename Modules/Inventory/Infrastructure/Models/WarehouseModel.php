<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class WarehouseModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'address',
        'status',
    ];
}
