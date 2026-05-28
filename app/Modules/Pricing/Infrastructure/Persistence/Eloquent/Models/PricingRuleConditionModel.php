<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PricingRuleConditionModel extends CoreModel
{
    protected $table = 'pricing_rule_conditions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'pricing_rule_id' => 'integer',
            'sequence' => 'integer',
            'value_number' => 'decimal:4',
            'value_boolean' => 'boolean',
            'value_date' => 'date',
            'is_active' => 'boolean',
        ]);
    }
}
