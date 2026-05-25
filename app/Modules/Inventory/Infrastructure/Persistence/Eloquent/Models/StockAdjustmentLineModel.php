<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class StockAdjustmentLineModel extends CoreModel
{


    protected $table = 'stock_adjustment_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'stock_adjustment_id' => 'integer',
            'item_id' => 'integer',
            'variant_id' => 'integer',
            'batch_id' => 'integer',
            'serial_id' => 'integer',
            'location_id' => 'integer',
            'warehouse_id' => 'integer',
            'system_qty' => 'decimal:4',
            'counted_qty' => 'decimal:4',
            'variance_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'variance_value' => 'decimal:4',
            'adjustment_movement_id' => 'integer',
        ]);
    }
}