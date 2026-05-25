<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PickingTaskModel extends CoreModel
{


    protected $table = 'picking_tasks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'receipt_inspection_id' => 'integer',
            'stock_movement_id' => 'integer',
            'source_warehouse_id' => 'integer',
            'source_location_id' => 'integer',
            'quantity' => 'decimal:4',
            'assigned_user_id' => 'integer',
            'completed_at' => 'datetime',
        ]);
    }
}