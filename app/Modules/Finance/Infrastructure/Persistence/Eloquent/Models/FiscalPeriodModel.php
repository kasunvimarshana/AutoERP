<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class FiscalPeriodModel extends BaseModel
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'fiscal_periods';

    protected $fillable = [
        'tenant_id',
        'fiscal_year_id',
        'period_number',
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'period_number' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
