<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

final class CostCenterModel extends FinanceModel
{
    use SoftDeletes;
    protected $table = 'cost_centers';

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
