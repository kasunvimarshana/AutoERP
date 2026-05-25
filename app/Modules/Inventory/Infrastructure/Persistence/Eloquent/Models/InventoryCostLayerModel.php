<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class InventoryCostLayerModel extends CoreModel
{


    protected $table = 'inventory_cost_layers';

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
            'batch_id' => 'integer',
            'serial_id' => 'integer',
            'warehouse_id' => 'integer',
            'location_id' => 'integer',
            'layer_date' => 'date',
            'quantity_in' => 'decimal:4',
            'quantity_remaining' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'is_closed' => 'boolean',
            'reference_id' => 'integer',
        ]);
    }
}