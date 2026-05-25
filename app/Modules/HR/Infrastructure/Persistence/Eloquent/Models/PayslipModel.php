<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PayslipModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'payslips';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'employee_id' => 'integer',
            'payroll_run_id' => 'integer',
            'salary_structure_id' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'base_salary' => 'decimal:4',
            'total_earnings' => 'decimal:4',
            'total_deductions' => 'decimal:4',
            'net_salary' => 'decimal:4',
            'worked_days' => 'decimal:4',
            'leave_days_paid' => 'decimal:4',
            'leave_days_unpaid' => 'decimal:4',
            'overtime_hours' => 'decimal:4',
            'journal_entry_id' => 'integer',
        ]);
    }
}