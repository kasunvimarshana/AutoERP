<?php

declare(strict_types=1);

namespace Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class WarehouseLocationModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'warehouse_locations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'warehouse_id' => 'integer',
            'parent_id' => 'integer',
            'depth' => 'integer',
            'is_active' => 'boolean',
            'is_pickable' => 'boolean',
            'is_receivable' => 'boolean',
            'capacity' => 'decimal:6',
        ]);
    }
}
