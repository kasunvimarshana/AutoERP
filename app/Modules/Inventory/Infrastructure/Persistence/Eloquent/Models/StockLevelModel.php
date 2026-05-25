<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class StockLevelModel extends CoreModel
{


    protected $table = 'stock_levels';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'item_id' => 'integer',
            'variant_id' => 'integer',
            'warehouse_id' => 'integer',
            'location_id' => 'integer',
            'batch_id' => 'integer',
            'serial_id' => 'integer',
            'uom_id' => 'integer',
            'quantity_on_hand' => 'decimal:4',
            'quantity_reserved' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'last_movement_at' => 'datetime',
        ]);
    }
}