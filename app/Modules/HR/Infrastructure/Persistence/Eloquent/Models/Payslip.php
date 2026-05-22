<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HR\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class Payslip extends Model
{
    use HasTenantAndOrganizationScopes;
    use SoftDeletes;

    protected $table = 'payslips';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
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
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\Employee', 'employee_id');
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\PayrollRun', 'payroll_run_id');
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\SalaryStructure', 'salary_structure_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo('Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\JournalEntry', 'journal_entry_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\PayslipLine', 'payslip_id');
    }
}
