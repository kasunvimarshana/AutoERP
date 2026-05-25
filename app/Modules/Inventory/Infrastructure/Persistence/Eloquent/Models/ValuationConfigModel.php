<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class ValuationConfigModel extends CoreModel
{


    protected $table = 'valuation_configs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'warehouse_id' => 'integer',
            'item_id' => 'integer',
            'variant_id' => 'integer',
            'location_id' => 'integer',
            'batch_id' => 'integer',
            'serial_id' => 'integer',
            'is_active' => 'boolean',
        ]);
    }
}