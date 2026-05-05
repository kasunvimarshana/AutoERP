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
            $totalGross = 0.0;
            $totalDeductions = 0.0;

            foreach ($employees as $employee) {
                $employeeId = (int) ($employee['employee_id'] ?? 0);
                $baseSalary = (string) ($employee['base_salary'] ?? '0');
                $workedDays = (float) ($employee['worked_days'] ?? 0);
                $baseAmount = (float) $baseSalary;

                $earnings = 0.0;
                $deductions = 0.0;

                foreach ($activeItems as $item) {
                    $itemValue = (float) $item->getValue();

                    if ($item->getCalculationType() === 'percentage') {
                        $itemValue = $baseAmount * ($itemValue / 100);
                    }

                    if ($item->getType() === 'earning') {
                        $earnings += $itemValue;
                    } elseif ($item->getType() === 'deduction') {
                        $deductions += $itemValue;
                    }
                }

                $gross = $baseAmount + $earnings;
                $net = $gross - $deductions;

                $payslip = new Payslip(
                    tenantId: $run->getTenantId(),
                    employeeId: $employeeId,
                    payrollRunId: $run->getId(),
                    periodStart: $run->getPeriodStart(),
                    periodEnd: $run->getPeriodEnd(),
                    grossSalary: number_format($gross, 6, '.', ''),
                    totalDeductions: number_format($deductions, 6, '.', ''),
                    netSalary: number_format($net, 6, '.', ''),
                    baseSalary: $baseSalary,
                    workedDays: $workedDays,
                    status: 'draft',
                    journalEntryId: null,
                    metadata: [],
                    createdAt: $now,
                    updatedAt: $now,
                );

                $savedPayslip = $this->payslipRepository->save($payslip);

                $totalGross += $gross;
                $totalDeductions += $deductions;

                $this->addEvent(new PayslipGenerated($savedPayslip, $run->getTenantId()));
            }

            $totalNet = $totalGross - $totalDeductions;
            $processedRun = new PayrollRun(
                tenantId: $run->getTenantId(),
                periodStart: $run->getPeriodStart(),
                periodEnd: $run->getPeriodEnd(),
                status: PayrollRunStatus::PROCESSING,
                processedAt: $now,
                approvedAt: $run->getApprovedAt(),
                approvedBy: $run->getApprovedBy(),
                totalGross: number_format($totalGross, 6, '.', ''),
                totalDeductions: number_format($totalDeductions, 6, '.', ''),
                totalNet: number_format($totalNet, 6, '.', ''),
                metadata: $run->getMetadata(),
                createdAt: $run->getCreatedAt(),
                updatedAt: $now,
                id: $run->getId(),
            );

            return $this->runRepository->save($processedRun);
        });
    }
}
