<?php

namespace App\Modules\Core\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingPeriod extends BaseModel
{
    protected $table = 'accounting_periods';

    protected $fillable = [
        'tenant_id',
        'fiscal_year_id',
        'name',
        'period_number',
        'start_date',
        'end_date',
        'status',
        'closed_at'
    ];

    protected $casts = [
        'period_number' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\FiscalYear::class, 'fiscal_year_id');
    }
}
