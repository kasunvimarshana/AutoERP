<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class TaxRuleModel extends FinanceModel
{
    protected $table = 'tax_rules';

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
