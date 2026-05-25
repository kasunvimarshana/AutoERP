<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PriceListItemModel extends CoreModel
{
    protected $table = 'price_list_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'price_list_id' => 'integer',
            'item_id' => 'integer',
            'variant_id' => 'integer',
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'batch_id' => 'integer',
            'serial_id' => 'integer',
            'uom_id' => 'integer',
            'min_quantity' => 'decimal:4',
            'price' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'valid_from' => 'date',
            'valid_to' => 'date'
        ]);
    }
}