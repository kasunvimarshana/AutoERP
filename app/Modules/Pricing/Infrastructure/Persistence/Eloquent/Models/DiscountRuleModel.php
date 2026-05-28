<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class DiscountRuleModel extends CoreModel
{
    protected $table = 'discount_rules';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'discount_id' => 'integer',
            'sequence' => 'integer',
            'value_number' => 'decimal:4',
            'value_boolean' => 'boolean',
            'value_date' => 'date',
            'is_active' => 'boolean',
        ]);
    }
}
