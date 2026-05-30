<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierItemModel extends CoreModel
{
    protected $table = 'supplier_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'item_id' => 'integer',
            'variant_id' => 'integer',
            'lead_time_days' => 'integer',
            'min_order_qty' => 'decimal:4',
            'is_preferred' => 'boolean',
            'last_observed_unit_cost' => 'decimal:4',
            'metadata' => 'array',
            'row_version' => 'integer',
        ]);
    }
}
