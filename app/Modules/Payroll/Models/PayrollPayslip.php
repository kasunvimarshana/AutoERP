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
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\Payroll\Enums\PayrollPaymentMethod;
use Modules\Payroll\Enums\PayslipStatus;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollPayslip extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_payslips';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        parent::booted();

        self::updating(function (self $payslip): void {
            $originalStatus = $payslip->getOriginal('status');
            $isFinalized = ($originalStatus instanceof PayslipStatus && in_array($originalStatus, [PayslipStatus::Approved, PayslipStatus::Paid], true))
                || in_array($originalStatus, [PayslipStatus::Approved->value, PayslipStatus::Paid->value], true)
                || in_array($payslip->getRawOriginal('status'), [PayslipStatus::Approved->value, PayslipStatus::Paid->value], true);

            if ($isFinalized) {
                $allowedDirty = ['status', 'row_version', 'updated_at'];
                $disallowed = array_diff(array_keys($payslip->getDirty()), $allowedDirty);

                if ($disallowed !== []) {
                    throw new LogicException('Approved or Paid payslips cannot be updated directly. Adjustments must be recorded via supplementary pay runs or linked adjustment records.');
                }
            }

            if (! $payslip->isDirty('row_version')) {
                $payslip->row_version = ((int) $payslip->getOriginal('row_version', 1)) + 1;
            }
        });

        self::deleting(function (self $payslip): void {
            $status = $payslip->status;
            $isFinalized = ($status instanceof PayslipStatus && in_array($status, [PayslipStatus::Approved, PayslipStatus::Paid], true))
                || in_array($status, [PayslipStatus::Approved->value, PayslipStatus::Paid->value], true);

            if ($isFinalized) {
                throw new LogicException('Approved or Paid payslips cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'pay_run_id' => 'integer',
            'employee_id' => 'integer',
            'salary_structure_id' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => PayslipStatus::class,
            'eligible_days' => 'decimal:2',
            'standard_days' => 'decimal:2',
            'worked_days' => 'decimal:2',
            'unpaid_leave_days' => 'decimal:2',
            'absent_days' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'double_time_hours' => 'decimal:2',
            'base_salary' => 'decimal:6',
            'prorated_basic_salary' => 'decimal:6',
            'gross_pay' => 'decimal:6',
            'total_allowances' => 'decimal:6',
            'total_deductions' => 'decimal:6',
            'employee_statutory_deductions' => 'decimal:6',
            'employer_statutory_contributions' => 'decimal:6',
            'tax_deduction' => 'decimal:6',
            'net_pay' => 'decimal:6',
            'payment_method' => PayrollPaymentMethod::class,
        ]);
    }

    public function payRun(): BelongsTo
    {
        return $this->belongsTo(PayrollPayRun::class, 'pay_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(PayrollSalaryStructure::class, 'salary_structure_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollPayslipItem::class, 'payslip_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class, 'applied_payslip_id');
    }
}
