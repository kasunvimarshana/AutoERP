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
use Modules\Core\Models\TenantOwnedModel;
use Modules\Payroll\Enums\PayrollCycleType;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollPayPeriod extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_pay_periods';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'cycle_type' => PayrollCycleType::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'payout_date' => 'date',
            'total_working_days' => 'integer',
            'is_closed' => 'boolean',
        ]);
    }

    public function payRuns(): HasMany
    {
        return $this->hasMany(PayrollPayRun::class, 'pay_period_id');
    }
}
