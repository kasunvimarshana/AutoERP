<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollRunModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipLineModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryStructureModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class PayslipModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope, SoftDeletes;

    protected $table = 'payslips';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:4',
            'leave_days_paid' => 'decimal:4',
            'leave_days_unpaid' => 'decimal:4',
            'metadata' => 'array',
            'net_salary' => 'decimal:4',
            'overtime_hours' => 'decimal:4',
            'period_end' => 'date',
            'period_start' => 'date',
            'row_version' => 'integer',
            'total_deductions' => 'decimal:4',
            'total_earnings' => 'decimal:4',
            'worked_days' => 'decimal:4',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRunModel::class, 'payroll_run_id');
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructureModel::class, 'salary_structure_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function payslipLines(): HasMany
    {
        return $this->hasMany(PayslipLineModel::class, 'payslip_id');
    }

}
