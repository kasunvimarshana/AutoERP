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
use Modules\Payroll\Enums\CalculationType;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollSalaryStructureItem extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_salary_structure_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'salary_structure_id' => 'integer',
            'salary_component_id' => 'integer',
            'percentage_basis_component_id' => 'integer',
            'calculation_type' => CalculationType::class,
            'amount_or_rate' => 'decimal:6',
            'sort_order' => 'integer',
        ]);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(PayrollSalaryStructure::class, 'salary_structure_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollSalaryComponent::class, 'salary_component_id');
    }

    public function percentageBasisComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollSalaryComponent::class, 'percentage_basis_component_id');
    }
}
