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
use Modules\Payroll\Enums\CalculationType;
use Modules\Payroll\Enums\SalaryComponentType;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollSalaryComponent extends TenantOwnedModel
{
    use ScopesPayrollTenant;
    use SoftDeletes;

    protected $table = 'payroll_salary_components';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'type' => SalaryComponentType::class,
            'calculation_type' => CalculationType::class,
            'is_taxable' => 'boolean',
            'is_statutory_base' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }

    public function structureItems(): HasMany
    {
        return $this->hasMany(PayrollSalaryStructureItem::class, 'salary_component_id');
    }
}
