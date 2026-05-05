<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PayslipModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_payslips';

    protected $fillable = ['tenant_id', 'employee_id', 'payroll_run_id', 'period_start', 'period_end', 'gross_salary', 'total_deductions', 'net_salary', 'base_salary', 'worked_days', 'status', 'journal_entry_id', 'metadata'];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'worked_days' => 'float', 'metadata' => 'array'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRunModel::class, 'payroll_run_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLineModel::class, 'payslip_id');
    }
}
