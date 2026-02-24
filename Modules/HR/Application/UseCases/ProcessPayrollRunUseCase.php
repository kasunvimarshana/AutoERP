<?php

namespace Modules\HR\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Contracts\PayrollRunRepositoryInterface;
use Modules\HR\Domain\Contracts\PayslipRepositoryInterface;
use Modules\HR\Domain\Events\PayrollRunCompleted;

/**
 * Processes a payroll run synchronously for small / mid-size tenants.
 *
 * For each active employee the use case first attempts salary-structure–based
 * computation via ComputePayslipComponentsUseCase.  When the employee has no
 * active structure assignment (gross = 0), it falls back to the employee's
 * fixed `salary` field, which preserves backward-compatibility.
 *
 * Large tenants should trigger processing via ProcessPayrollRunsCommand which
 * dispatches individual ProcessPayslipJob queue jobs to avoid execution timeouts.
 */
class ProcessPayrollRunUseCase
{
    public function __construct(
        private PayrollRunRepositoryInterface    $payrollRunRepo,
        private PayslipRepositoryInterface       $payslipRepo,
        private EmployeeRepositoryInterface      $employeeRepo,
        private ComputePayslipComponentsUseCase  $computePayslip,
    ) {}

    public function execute(string $payrollRunId): object
    {
        return DB::transaction(function () use ($payrollRunId) {
            $run = $this->payrollRunRepo->findById($payrollRunId);

            if (! $run) {
                throw new \DomainException("Payroll run [{$payrollRunId}] not found.");
            }

            if ($run->status === 'completed') {
                throw new \DomainException("Payroll run [{$payrollRunId}] is already completed.");
            }

            $this->payrollRunRepo->update($payrollRunId, ['status' => 'processing']);

            $tenantId   = $run->tenant_id;
            $totalGross = '0.00000000';
            $totalNet   = '0.00000000';

            $this->employeeRepo->chunkActive($tenantId, 100, function ($employees) use ($payrollRunId, $tenantId, &$totalGross, &$totalNet) {
                foreach ($employees as $employee) {
                    // Try salary-structure–based computation first.
                    $computed = $this->computePayslip->execute($tenantId, $employee->id);

                    // Fall back to fixed salary when no structure is assigned.
                    if (bccomp($computed['gross'], '0.00000000', 8) === 0) {
                        $gross      = bcadd((string) ($employee->salary ?? '0'), '0.00000000', 8);
                        $deductions = '0.00000000';
                        $net        = $gross;
                    } else {
                        $gross      = $computed['gross'];
                        $deductions = $computed['deductions'];
                        $net        = $computed['net'];
                    }

                    $this->payslipRepo->create([
                        'tenant_id'      => $tenantId,
                        'payroll_run_id' => $payrollRunId,
                        'employee_id'    => $employee->id,
                        'gross_salary'   => $gross,
                        'deductions'     => $deductions,
                        'net_salary'     => $net,
                        'status'         => 'pending',
                    ]);

                    $totalGross = bcadd($totalGross, $gross, 8);
                    $totalNet   = bcadd($totalNet, $net, 8);
                }
            });

            $run = $this->payrollRunRepo->update($payrollRunId, [
                'status'      => 'completed',
                'total_gross' => $totalGross,
                'total_net'   => $totalNet,
            ]);

            $totalDeductions = bcsub($totalGross, $totalNet, 8);

            Event::dispatch(new PayrollRunCompleted(
                payrollRunId:    $payrollRunId,
                tenantId:        $tenantId,
                totalGross:      $totalGross,
                totalDeductions: $totalDeductions,
                totalNet:        $totalNet,
                periodLabel:     (string) ($run->period ?? ''),
            ));

            return $run;
        });
    }
}
