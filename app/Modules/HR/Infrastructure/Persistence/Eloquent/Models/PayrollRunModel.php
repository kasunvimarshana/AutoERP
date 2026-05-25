<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PayrollRunModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'payroll_runs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'payment_date' => 'date',
            'total_gross' => 'decimal:4',
            'total_deductions' => 'decimal:4',
            'total_net' => 'decimal:4',
            'total_employer_contributions' => 'decimal:4',
            'processed_at' => 'datetime',
            'approved_at' => 'datetime',
            'approved_by' => 'integer',
            'created_by' => 'integer',
        ]);
    }
}