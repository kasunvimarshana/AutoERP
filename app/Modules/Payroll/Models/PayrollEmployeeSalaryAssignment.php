<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\Payroll\Enums\PayrollPaymentMethod;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollEmployeeSalaryAssignment extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_employee_salary_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'employee_id' => 'integer',
            'salary_structure_id' => 'integer',
            'base_salary' => 'decimal:6',
            'hourly_rate' => 'decimal:6',
            'payment_method' => PayrollPaymentMethod::class,
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(PayrollSalaryStructure::class, 'salary_structure_id');
    }
}
