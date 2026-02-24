<?php

namespace Modules\HR\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\HR\Application\UseCases\ComputePayslipComponentsUseCase;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Contracts\PayslipRepositoryInterface;


class ProcessPayslipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;
    public int $timeout = 120;

    public function __construct(
        public readonly string $payrollRunId,
        public readonly string $employeeId,
        public readonly string $tenantId,
    ) {}

    public function handle(
        ComputePayslipComponentsUseCase $computePayslip,
        EmployeeRepositoryInterface     $employeeRepo,
        PayslipRepositoryInterface      $payslipRepo,
    ): void {
        $employee = $employeeRepo->findById($this->employeeId);

        if (! $employee || ($employee->status ?? '') !== 'active') {
            return;
        }

        // Try salary-structure–based computation first.
        $computed = $computePayslip->execute($this->tenantId, $this->employeeId);

        // Fall back to the employee's fixed salary when no structure is assigned.
        if (bccomp($computed['gross'], '0.00000000', 8) === 0) {
            $gross      = bcadd((string) ($employee->salary ?? '0'), '0.00000000', 8);
            $deductions = '0.00000000';
            $net        = $gross;
        } else {
            $gross      = $computed['gross'];
            $deductions = $computed['deductions'];
            $net        = $computed['net'];
        }

        DB::transaction(function () use ($gross, $deductions, $net, $payslipRepo) {
            $payslipRepo->create([
                'tenant_id'      => $this->tenantId,
                'payroll_run_id' => $this->payrollRunId,
                'employee_id'    => $this->employeeId,
                'gross_salary'   => $gross,
                'deductions'     => $deductions,
                'net_salary'     => $net,
                'status'         => 'pending',
            ]);
        });
    }
}
