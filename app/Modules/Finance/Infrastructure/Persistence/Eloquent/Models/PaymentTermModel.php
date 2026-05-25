<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

final class PaymentTermModel extends FinanceModel
{
    use SoftDeletes;
    protected $table = 'payment_terms';

    protected $casts = [
        'metadata' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
