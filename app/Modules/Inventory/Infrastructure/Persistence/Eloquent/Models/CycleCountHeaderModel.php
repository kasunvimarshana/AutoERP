<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CycleCountHeaderModel extends CoreModel
{


    protected $table = 'cycle_count_headers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'warehouse_id' => 'integer',
            'location_id' => 'integer',
            'counted_by_user_id' => 'integer',
            'counted_at' => 'datetime',
            'approved_by_user_id' => 'integer',
            'approved_at' => 'datetime',
        ]);
    }
}