<?php
declare(strict_types=1);
namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\PayrollServiceInterface;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Domain\Events\PayrollProcessed;
use Modules\HR\Domain\Exceptions\PayrollRecordNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class PayrollService implements PayrollServiceInterface
{
    public function __construct(
        private readonly PayrollRepositoryInterface $repository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
    ) {}

    public function findById(int $id): PayrollRecord
    {
        $record = $this->repository->findById($id);
        if ($record === null) {
            throw new PayrollRecordNotFoundException($id);
        }
        return $record;
    }

    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByEmployee($employeeId, $perPage, $page);
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByTenant($tenantId, $perPage, $page);
    }

    public function findByTenantAndPeriod(int $tenantId, int $year, int $month): array
    {
        return $this->repository->findByTenantAndPeriod($tenantId, $year, $month);
    }

    public function processPayroll(int $employeeId, int $year, int $month, int $processedById, array $overrides = []): PayrollRecord
    {
        $employee    = $this->employeeRepository->findById($employeeId);
        $basicSalary = $overrides['basic_salary'] ?? ($employee?->getBaseSalary() ?? 0.0);
        $allowances  = $overrides['allowances'] ?? 0.0;
        $deductions  = $overrides['deductions'] ?? 0.0;
        $taxRate     = $overrides['tax_rate'] ?? 0.1; // 10% default tax
        $grossSalary = $basicSalary + $allowances;
        $taxAmount   = round($grossSalary * $taxRate, 2);
        $netSalary   = round($grossSalary - $deductions - $taxAmount, 2);

        $record = $this->repository->create([
            'tenant_id'        => $employee?->getTenantId() ?? 0,
            'employee_id'      => $employeeId,
            'period_year'      => $year,
            'period_month'     => $month,
            'basic_salary'     => $basicSalary,
            'allowances'       => $allowances,
            'deductions'       => $deductions,
            'tax_amount'       => $taxAmount,
            'net_salary'       => $netSalary,
            'status'           => PayrollRecord::STATUS_DRAFT,
            'processed_by_id'  => $processedById,
            'processed_at'     => date('Y-m-d H:i:s'),
            'breakdown'        => array_merge([
                'basic_salary' => $basicSalary,
                'allowances'   => $allowances,
                'gross_salary' => $grossSalary,
                'tax_rate'     => $taxRate,
                'tax_amount'   => $taxAmount,
                'deductions'   => $deductions,
                'net_salary'   => $netSalary,
            ], $overrides['breakdown'] ?? []),
        ]);

        // Transition to processed status
        $record = $this->repository->update($record->getId(), ['status' => PayrollRecord::STATUS_PROCESSED]) ?? $record;

        event(new PayrollProcessed($record->getId(), $employeeId, $year, $month));

        return $record;
    }

    public function approve(int $id): PayrollRecord
    {
        $record = $this->findById($id);
        $record->approve();
        $updated = $this->repository->update($id, ['status' => PayrollRecord::STATUS_APPROVED]);
        return $updated ?? $record;
    }

    public function markAsPaid(int $id, string $paymentDate, string $reference): PayrollRecord
    {
        $record = $this->findById($id);
        $record->markAsPaid(new \DateTime($paymentDate), $reference);
        $updated = $this->repository->update($id, [
            'status'             => PayrollRecord::STATUS_PAID,
            'payment_date'       => $paymentDate,
            'payment_reference'  => $reference,
        ]);
        return $updated ?? $record;
    }

    public function cancel(int $id): PayrollRecord
    {
        $record = $this->findById($id);
        $record->cancel();
        $updated = $this->repository->update($id, ['status' => PayrollRecord::STATUS_CANCELLED]);
        return $updated ?? $record;
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
