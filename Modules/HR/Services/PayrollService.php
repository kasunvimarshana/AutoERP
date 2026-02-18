<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Services\BaseService;
use Modules\HR\Entities\Payroll;
use Modules\HR\Events\PayrollProcessed;
use Modules\HR\Repositories\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Repositories\Contracts\PayrollRepositoryInterface;

/**
 * Payroll Service
 *
 * Handles business logic for payroll calculation and payslip generation.
 */
class PayrollService extends BaseService
{
    /**
     * PayrollService constructor.
     */
    public function __construct(
        protected PayrollRepositoryInterface $payrollRepository,
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}

    /**
     * Create a payroll entry.
     */
    public function create(array $data): Payroll
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate payroll number if not provided
            if (empty($data['payroll_number'])) {
                $data['payroll_number'] = $this->generatePayrollNumber($data['month'], $data['year']);
            }

            // Calculate gross and net salary if not provided
            if (empty($data['gross_salary'])) {
                $data['gross_salary'] = $data['basic_salary'] + ($data['allowances'] ?? 0);
            }
            if (empty($data['net_salary'])) {
                $data['net_salary'] = $data['gross_salary'] - ($data['deductions'] ?? 0);
            }

            $payroll = $this->payrollRepository->create($data);

            $this->logInfo('Payroll created', [
                'payroll_id' => $payroll->id,
                'employee_id' => $payroll->employee_id,
                'payroll_number' => $payroll->payroll_number,
            ]);

            return $payroll;
        });
    }

    /**
     * Update a payroll entry.
     */
    public function update(int $id, array $data): Payroll
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $payroll = $this->payrollRepository->update($id, $data);

            $this->logInfo('Payroll updated', ['payroll_id' => $payroll->id]);

            return $payroll;
        });
    }

    /**
     * Calculate payroll for an employee.
     */
    public function calculatePayroll(int $employeeId, int $month, int $year, array $additionalData = []): Payroll
    {
        return $this->executeInTransaction(function () use ($employeeId, $month, $year, $additionalData) {
            // Check if payroll already exists
            $existingPayroll = $this->payrollRepository->getByEmployeeMonthYear($employeeId, $month, $year);
            if ($existingPayroll) {
                throw new \RuntimeException('Payroll already exists for this employee and period.');
            }

            $employee = $this->employeeRepository->find($employeeId);
            if (! $employee) {
                throw new \RuntimeException('Employee not found.');
            }

            // Calculate payroll components
            $basicSalary = $employee->salary ?? 0;
            $allowances = $additionalData['allowances'] ?? 0;
            $deductions = $additionalData['deductions'] ?? 0;
            $grossSalary = $basicSalary + $allowances;
            $netSalary = $grossSalary - $deductions;

            $payrollData = [
                'employee_id' => $employeeId,
                'payroll_number' => $this->generatePayrollNumber($month, $year),
                'month' => $month,
                'year' => $year,
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'deductions' => $deductions,
                'gross_salary' => $grossSalary,
                'net_salary' => $netSalary,
                'allowance_details' => $additionalData['allowance_details'] ?? null,
                'deduction_details' => $additionalData['deduction_details'] ?? null,
                'status' => 'processed',
            ];

            $payroll = $this->payrollRepository->create($payrollData);

            event(new PayrollProcessed($payroll));

            $this->logInfo('Payroll calculated', [
                'payroll_id' => $payroll->id,
                'employee_id' => $employeeId,
                'month' => $month,
                'year' => $year,
                'net_salary' => $netSalary,
            ]);

            return $payroll;
        });
    }

    /**
     * Process payment for a payroll entry.
     */
    public function processPayment(int $id, string $paymentMethod = 'bank_transfer'): Payroll
    {
        return $this->executeInTransaction(function () use ($id, $paymentMethod) {
            $payroll = $this->payrollRepository->update($id, [
                'status' => 'paid',
                'paid_at' => Carbon::now(),
                'payment_method' => $paymentMethod,
            ]);

            $this->logInfo('Payroll payment processed', [
                'payroll_id' => $payroll->id,
                'payment_method' => $paymentMethod,
            ]);

            return $payroll;
        });
    }

    /**
     * Generate payslip (return payroll details).
     */
    public function generatePayslip(int $id): Payroll
    {
        $payroll = $this->payrollRepository->find($id);

        if (! $payroll) {
            throw new \RuntimeException('Payroll not found.');
        }

        return $payroll->load('employee.department', 'employee.position');
    }

    /**
     * Get payroll by employee.
     */
    public function getByEmployee(int $employeeId): Collection
    {
        return $this->payrollRepository->getByEmployee($employeeId);
    }

    /**
     * Get payroll by month and year.
     */
    public function getByMonthYear(int $month, int $year): Collection
    {
        return $this->payrollRepository->getByMonthYear($month, $year);
    }

    /**
     * Generate unique payroll number.
     */
    protected function generatePayrollNumber(int $month, int $year): string
    {
        $prefix = 'PAY';
        $monthStr = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        $timestamp = Carbon::now()->format('His');

        return sprintf('%s-%s%s-%s', $prefix, $year, $monthStr, $timestamp);
    }

    /**
     * Delete payroll record.
     */
    public function delete(int $id): bool
    {
        return $this->payrollRepository->delete($id);
    }
}
