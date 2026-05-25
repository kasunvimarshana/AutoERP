<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class BatcheModel extends CoreModel
{


    protected $table = 'batches';

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
            'manufacture_date' => 'date',
            'expiry_date' => 'date',
            'received_date' => 'date',
            'supplier_id' => 'integer',
            'unit_cost' => 'decimal:4',
        ]);
    }
}