<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\Hr\Models\HrEmployee;
use Modules\Payroll\Enums\AdjustmentStatus;
use Modules\Payroll\Enums\AdjustmentType;
use Modules\Payroll\Enums\PayslipStatus;
use Modules\Payroll\Models\PayrollAdjustment;
use Modules\Payroll\Models\PayrollEmployeeSalaryAssignment;
use Modules\Payroll\Models\PayrollPayPeriod;
use Modules\Payroll\Models\PayrollPayslip;

final class PayrollRetroactivePayService
{
    /**
     * Calculate and generate retroactive salary increment adjustment (arrears)
     * across historical closed/paid pay periods without mutating historical records.
     */
    public function calculateSalaryArrears(
        int $tenantId,
        int $employeeId,
        string $effectiveFromDate,
        int $targetPayPeriodId,
        int $userId,
    ): ?PayrollAdjustment {
        $employee = HrEmployee::query()->where('tenant_id', $tenantId)->findOrFail($employeeId);
        $targetPeriod = PayrollPayPeriod::query()->where('tenant_id', $tenantId)->findOrFail($targetPayPeriodId);

        $effectiveDate = CarbonImmutable::parse($effectiveFromDate);
        if ($effectiveDate->isAfter($targetPeriod->period_start)) {
            throw new InvalidArgumentException('Effective date must be prior to the start of the target pay period to calculate arrears.');
        }

        // Active current salary assignment
        $currentAssignment = PayrollEmployeeSalaryAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->first();

        if ($currentAssignment === null) {
            throw new InvalidArgumentException('Employee has no active salary assignment.');
        }

        $newBaseSalary = (string) $currentAssignment->base_salary;

        // Query historical paid payslips within the backdated date range
        $historicalPayslips = PayrollPayslip::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('status', PayslipStatus::Paid)
            ->whereDate('period_end', '>=', $effectiveFromDate)
            ->whereDate('period_end', '<', $targetPeriod->period_start)
            ->orderBy('period_start')
            ->get();

        if ($historicalPayslips->isEmpty()) {
            return null;
        }

        $totalArrears = '0.0000';
        $periodSummaries = [];

        foreach ($historicalPayslips as $payslip) {
            $oldBaseSalary = (string) $payslip->base_salary;

            // Only compute arrears if new salary exceeds historical base salary
            if (bccomp($newBaseSalary, $oldBaseSalary, 4) <= 0) {
                continue;
            }

            $salaryDelta = bcsub($newBaseSalary, $oldBaseSalary, 4);

            $periodStart = CarbonImmutable::parse($payslip->period_start);
            $periodEnd = CarbonImmutable::parse($payslip->period_end);

            // Calculate ratio if effective date falls within this pay period
            $periodTotalDays = $periodStart->diffInDays($periodEnd) + 1;
            $activeStart = $effectiveDate->isAfter($periodStart) ? $effectiveDate : $periodStart;
            $activeDays = $activeStart->diffInDays($periodEnd) + 1;

            $fraction = $periodTotalDays > 0 ? bcdiv((string) $activeDays, (string) $periodTotalDays, 6) : '1.000000';
            $periodArrears = bcmul($salaryDelta, $fraction, 4);

            if (bccomp($periodArrears, '0.0000', 4) > 0) {
                $totalArrears = bcadd($totalArrears, $periodArrears, 4);
                $periodSummaries[] = sprintf('%s to %s (+%s)', $periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d'), $periodArrears);
            }
        }

        if (bccomp($totalArrears, '0.0000', 4) <= 0) {
            return null;
        }

        $reason = sprintf(
            'Retroactive salary adjustment effective %s: %s',
            $effectiveFromDate,
            implode('; ', $periodSummaries),
        );

        return PayrollAdjustment::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $employee->organization_unit_id,
            'employee_id' => $employeeId,
            'adjustment_type' => AdjustmentType::RetroPay,
            'amount' => $totalArrears,
            'effective_pay_period_id' => $targetPayPeriodId,
            'status' => AdjustmentStatus::Pending,
            'reason' => $reason,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }
}
