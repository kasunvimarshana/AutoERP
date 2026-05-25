<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class TaxRateModel extends FinanceModel
{
    protected $table = 'tax_rates';

    protected $casts = [
        'metadata' => 'array',
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];
}
