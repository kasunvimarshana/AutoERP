<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SerialModel extends CoreModel
{


    protected $table = 'serials';

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
            'current_location_id' => 'integer',
            'warranty_expiry' => 'date',
            'manufacture_date' => 'date',
            'unit_cost' => 'decimal:4',
            'current_owner_id' => 'integer',
        ]);
    }
}