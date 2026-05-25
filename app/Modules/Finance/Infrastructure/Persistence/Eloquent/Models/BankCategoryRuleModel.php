<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

final class BankCategoryRuleModel extends FinanceModel
{
    use SoftDeletes;
    protected $table = 'bank_category_rules';

    protected $casts = [
        'metadata' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];
}
