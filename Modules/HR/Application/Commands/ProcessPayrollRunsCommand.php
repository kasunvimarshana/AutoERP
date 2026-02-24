<?php

namespace Modules\HR\Application\Commands;

use Illuminate\Console\Command;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Contracts\PayrollRunRepositoryInterface;
use Modules\HR\Infrastructure\Jobs\ProcessPayslipJob;

/**
 * Dispatches individual payslip-processing jobs for all draft payroll runs,
 * iterating active employees in chunks to prevent execution timeout.
 *
 * Pattern: identical to ProcessSubscriptionRenewalsCommand and
 * ProcessReorderRulesCommand — the command is non-blocking, doing only
 * in-memory chunking and job dispatch; each ProcessPayslipJob handles one
 * employee within a dedicated queue worker.
 *
 * Usage:
 *   php artisan hr:process-payroll-runs
 *   php artisan hr:process-payroll-runs --tenant=<uuid>
 *   php artisan hr:process-payroll-runs --chunk=50
 */
class ProcessPayrollRunsCommand extends Command
{
    protected $signature = 'hr:process-payroll-runs
                            {--tenant= : Limit processing to a specific tenant ID}
                            {--chunk=100 : Number of employees to process per chunk}';

    protected $description = 'Dispatch payslip jobs for all draft payroll runs in chunks to prevent execution timeout';

    public function __construct(
        private PayrollRunRepositoryInterface $payrollRunRepo,
        private EmployeeRepositoryInterface   $employeeRepo,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId  = $this->option('tenant') ?: null;
        $chunkSize = (int) $this->option('chunk');

        $dispatched = 0;

        $this->payrollRunRepo->chunkDraftRuns($chunkSize, function ($runs) use ($tenantId, $chunkSize, &$dispatched) {
            foreach ($runs as $run) {
                // Mark run as processing so subsequent command invocations skip it.
                $this->payrollRunRepo->update($run->id, ['status' => 'processing']);

                $runTenantId = $run->tenant_id;

                // Dispatch one job per active employee for this run.
                $this->employeeRepo->chunkActive($runTenantId, $chunkSize, function ($employees) use ($run, $runTenantId, &$dispatched) {
                    foreach ($employees as $employee) {
                        ProcessPayslipJob::dispatch($run->id, $employee->id, $runTenantId);
                        $dispatched++;
                    }
                });
            }
        }, $tenantId);

        $this->info("Dispatched {$dispatched} payslip job(s) to the queue.");

        return self::SUCCESS;
    }
}
