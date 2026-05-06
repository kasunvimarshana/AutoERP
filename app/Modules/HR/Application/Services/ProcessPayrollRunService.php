<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\ProcessPayrollRunServiceInterface;
use Modules\HR\Domain\Entities\PayrollRun;
use Modules\HR\Domain\Entities\Payslip;
use Modules\HR\Domain\Events\PayslipGenerated;
use Modules\HR\Domain\Exceptions\PayrollRunNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PayrollItemRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRunRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayslipRepositoryInterface;
use Modules\HR\Domain\ValueObjects\PayrollRunStatus;

class ProcessPayrollRunService extends BaseService implements ProcessPayrollRunServiceInterface
{
    public function __construct(
        private readonly PayrollRunRepositoryInterface $runRepository,
        private readonly PayrollItemRepositoryInterface $itemRepository,
        private readonly PayslipRepositoryInterface $payslipRepository,
    ) {
        parent::__construct($this->runRepository);
    }

    protected function handle(array $data): PayrollRun
    {
        $id = (int) ($data['id'] ?? 0);
        $run = $this->runRepository->find($id);

        if ($run === null) {
            throw new PayrollRunNotFoundException($id);
        }

        if ($run->getStatus() !== PayrollRunStatus::DRAFT) {
            throw new DomainException('Only draft payroll runs can be processed.');
        }

        /** @var array<int, array<string, mixed>> $employees */
        $employees = $data['employees'] ?? [];

        $activeItems = $this->itemRepository
            ->resetCriteria()
            ->where('tenant_id', $run->getTenantId())
            ->where('is_active', true)
            ->get();

        return DB::transaction(function () use ($run, $employees, $activeItems): PayrollRun {
            $now = new \DateTimeImmutable;
            $totalGross = '0.000000';
            $totalDeductions = '0.000000';

            foreach ($employees as $employee) {
                $employeeId = (int) ($employee['employee_id'] ?? 0);
                $baseSalary = number_format((float) ($employee['base_salary'] ?? 0), 6, '.', '');
                $workedDays = (float) ($employee['worked_days'] ?? 0);

                $earnings = '0.000000';
                $deductions = '0.000000';

                foreach ($activeItems as $item) {
                    $itemValue = number_format((float) $item->getValue(), 6, '.', '');

                    if ($item->getCalculationType() === 'percentage') {
                        $itemValue = bcmul($baseSalary, bcdiv($itemValue, '100.000000', 6), 6);
                    }

                    if ($item->getType() === 'earning') {
                        $earnings = bcadd($earnings, $itemValue, 6);
                    } elseif ($item->getType() === 'deduction') {
                        $deductions = bcadd($deductions, $itemValue, 6);
                    }
                }

                $gross = bcadd($baseSalary, $earnings, 6);
                $net = bcsub($gross, $deductions, 6);

                $payslip = new Payslip(
                    tenantId: $run->getTenantId(),
                    employeeId: $employeeId,
                    payrollRunId: $run->getId(),
                    periodStart: $run->getPeriodStart(),
                    periodEnd: $run->getPeriodEnd(),
                    grossSalary: $gross,
                    totalDeductions: $deductions,
                    netSalary: $net,
                    baseSalary: $baseSalary,
                    workedDays: $workedDays,
                    status: 'draft',
                    journalEntryId: null,
                    metadata: [],
                    createdAt: $now,
                    updatedAt: $now,
                );

                $savedPayslip = $this->payslipRepository->save($payslip);

                $totalGross = bcadd($totalGross, $gross, 6);
                $totalDeductions = bcadd($totalDeductions, $deductions, 6);

                $this->addEvent(new PayslipGenerated($savedPayslip, $run->getTenantId()));
            }

            $totalNet = bcsub($totalGross, $totalDeductions, 6);
            $processedRun = new PayrollRun(
                tenantId: $run->getTenantId(),
                periodStart: $run->getPeriodStart(),
                periodEnd: $run->getPeriodEnd(),
                status: PayrollRunStatus::PROCESSING,
                processedAt: $now,
                approvedAt: $run->getApprovedAt(),
                approvedBy: $run->getApprovedBy(),
                totalGross: $totalGross,
                totalDeductions: $totalDeductions,
                totalNet: $totalNet,
                metadata: $run->getMetadata(),
                createdAt: $run->getCreatedAt(),
                updatedAt: $now,
                id: $run->getId(),
            );

            return $this->runRepository->save($processedRun);
        });
    }
}
