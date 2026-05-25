<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

final class TaxGroupModel extends FinanceModel
{
    use SoftDeletes;
    protected $table = 'tax_groups';

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
