<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SupplierPriceListModel extends CoreModel
{
    protected $table = 'supplier_price_lists';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'supplier_id' => 'integer',
            'price_list_id' => 'integer',
            'priority' => 'integer'
        ]);
    }
}