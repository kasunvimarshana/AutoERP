<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class StockTransferModel extends CoreModel
{


    protected $table = 'stock_transfers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'from_warehouse_id' => 'integer',
            'to_warehouse_id' => 'integer',
            'from_location_id' => 'integer',
            'to_location_id' => 'integer',
            'requested_by' => 'integer',
            'approved_by' => 'integer',
            'transferred_at' => 'datetime',
        ]);
    }
}