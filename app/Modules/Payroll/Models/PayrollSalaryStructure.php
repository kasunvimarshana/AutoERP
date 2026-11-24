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

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Payroll\Enums\PayrollCycleType;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollSalaryStructure extends TenantOwnedModel
{
    use ScopesPayrollTenant;
    use SoftDeletes;

    protected $table = 'payroll_salary_structures';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'currency_id' => 'integer',
            'cycle_type' => PayrollCycleType::class,
            'is_active' => 'boolean',
        ]);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollSalaryStructureItem::class, 'salary_structure_id')->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PayrollEmployeeSalaryAssignment::class, 'salary_structure_id');
    }
}
