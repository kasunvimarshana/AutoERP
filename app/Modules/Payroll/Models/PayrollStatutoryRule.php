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

use Modules\Core\Models\TenantOwnedModel;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollStatutoryRule extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_statutory_rules';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'employee_rate_percentage' => 'decimal:2',
            'employer_rate_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ]);
    }
}
