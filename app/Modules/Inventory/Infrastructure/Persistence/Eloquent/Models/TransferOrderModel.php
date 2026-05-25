<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class TransferOrderModel extends CoreModel
{


    protected $table = 'transfer_orders';

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
            'request_date' => 'date',
            'expected_date' => 'date',
            'shipped_date' => 'date',
            'received_date' => 'date',
        ]);
    }
}