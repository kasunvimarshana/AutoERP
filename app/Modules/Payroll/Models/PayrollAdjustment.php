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
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\Payroll\Enums\AdjustmentStatus;
use Modules\Payroll\Enums\AdjustmentType;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;
use Modules\User\Models\UserModel;

final class PayrollAdjustment extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_adjustments';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        parent::booted();

        self::updating(function (self $adjustment): void {
            $originalStatus = $adjustment->getOriginal('status');
            $isApplied = ($originalStatus instanceof AdjustmentStatus && $originalStatus === AdjustmentStatus::Applied)
                || $originalStatus === AdjustmentStatus::Applied->value
                || $adjustment->getRawOriginal('status') === AdjustmentStatus::Applied->value;

            if ($isApplied && ! $adjustment->isDirty('status')) {
                throw new LogicException('Applied adjustments cannot be modified directly.');
            }
        });

        self::deleting(function (self $adjustment): void {
            $status = $adjustment->status;
            $isApplied = ($status instanceof AdjustmentStatus && $status === AdjustmentStatus::Applied)
                || $status === AdjustmentStatus::Applied->value;

            if ($isApplied) {
                throw new LogicException('Applied adjustments cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'employee_id' => 'integer',
            'salary_component_id' => 'integer',
            'effective_pay_period_id' => 'integer',
            'applied_payslip_id' => 'integer',
            'adjustment_type' => AdjustmentType::class,
            'status' => AdjustmentStatus::class,
            'amount' => 'decimal:4',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollSalaryComponent::class, 'salary_component_id');
    }

    public function payPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPayPeriod::class, 'effective_pay_period_id');
    }

    public function appliedPayslip(): BelongsTo
    {
        return $this->belongsTo(PayrollPayslip::class, 'applied_payslip_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'approved_by');
    }
}
