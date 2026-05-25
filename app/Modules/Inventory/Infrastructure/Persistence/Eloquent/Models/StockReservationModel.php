<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class StockReservationModel extends CoreModel
{


    protected $table = 'stock_reservations';

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
            'quantity' => 'decimal:4',
            'expires_at' => 'datetime',
            'unit_cost' => 'decimal:4',
            'reserved_for_id' => 'integer',
        ]);
    }
}