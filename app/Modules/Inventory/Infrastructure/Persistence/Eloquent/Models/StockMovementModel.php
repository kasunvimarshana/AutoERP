<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class StockMovementModel extends CoreModel
{


    protected $table = 'stock_movements';

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
            'location_id' => 'integer',
            'warehouse_id' => 'integer',
            'uom_id' => 'integer',
            'quantity' => 'decimal:4',
            'quantity_in' => 'decimal:4',
            'quantity_out' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'balance_quantity' => 'decimal:4',
            'balance_value' => 'decimal:4',
            'performed_by' => 'integer',
            'performed_at' => 'datetime',
            'reference_id' => 'integer',
        ]);
    }
}