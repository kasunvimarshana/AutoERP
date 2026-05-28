<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PricingTierModel extends CoreModel
{
    protected $table = 'pricing_tiers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'price_list_item_id' => 'integer',
            'pricing_rule_id' => 'integer',
            'discount_id' => 'integer',
            'sequence' => 'integer',
            'min_quantity' => 'decimal:4',
            'max_quantity' => 'decimal:4',
            'uom_id' => 'integer',
            'currency_id' => 'integer',
            'price' => 'decimal:4',
            'adjustment_value' => 'decimal:4',
            'priority' => 'integer',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
        ]);
    }
}
