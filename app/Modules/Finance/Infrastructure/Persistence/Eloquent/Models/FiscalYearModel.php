<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYearModel extends Model
{
    protected $table = 'fiscal_years';

    protected $fillable = [
        'tenant_id',
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
