<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class DiscountModel extends CoreModel
{
    protected $table = 'discounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'item_id' => 'integer',
            'customer_id' => 'integer',
            'supplier_id' => 'integer',
            'currency_id' => 'integer',
            'uom_id' => 'integer',
            'discount_value' => 'decimal:4',
            'min_quantity' => 'decimal:4',
            'max_quantity' => 'decimal:4',
            'priority' => 'integer',
            'is_stackable' => 'boolean',
            'is_exclusive' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
        ]);
    }
}
