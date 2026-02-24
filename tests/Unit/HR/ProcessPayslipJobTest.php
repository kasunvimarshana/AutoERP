<?php

namespace Tests\Unit\HR;

use Illuminate\Support\Facades\DB;
use Mockery;
use Modules\HR\Application\UseCases\ComputePayslipComponentsUseCase;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Contracts\PayslipRepositoryInterface;
use Modules\HR\Infrastructure\Jobs\ProcessPayslipJob;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProcessPayslipJob.
 *
 * Verifies salary-structure integration, employee-salary fallback, inactive
 * employee guard, and BCMath precision of payslip fields.
 */
class ProcessPayslipJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeJob(string $runId = 'run-1', string $empId = 'emp-1', string $tenantId = 'tenant-1'): ProcessPayslipJob
    {
        return new ProcessPayslipJob($runId, $empId, $tenantId);
    }

    public function test_skips_when_employee_not_found(): void
    {
        $employeeRepo  = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('emp-1')->andReturnNull();

        $computeMock   = Mockery::mock(ComputePayslipComponentsUseCase::class);
        $payslipRepo   = Mockery::mock(PayslipRepositoryInterface::class);
        $payslipRepo->shouldReceive('create')->never();
        $computeMock->shouldReceive('execute')->never();

        $this->makeJob()->handle($computeMock, $employeeRepo, $payslipRepo);
        // No assertions needed — reaching here without error confirms the guard.
        $this->addToAssertionCount(1);
    }

    public function test_skips_when_employee_inactive(): void
    {
        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->andReturn((object) ['id' => 'emp-1', 'status' => 'terminated']);

        $computeMock = Mockery::mock(ComputePayslipComponentsUseCase::class);
        $payslipRepo = Mockery::mock(PayslipRepositoryInterface::class);
        $payslipRepo->shouldReceive('create')->never();
        $computeMock->shouldReceive('execute')->never();

        $this->makeJob()->handle($computeMock, $employeeRepo, $payslipRepo);
        $this->addToAssertionCount(1);
    }

    public function test_uses_employee_salary_fallback_when_no_structure_assigned(): void
    {
        $employee = (object) ['id' => 'emp-1', 'status' => 'active', 'salary' => '3000.00000000'];

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->andReturn($employee);

        // Structure returns zeros → fallback to employee.salary
        $computeMock = Mockery::mock(ComputePayslipComponentsUseCase::class);
        $computeMock->shouldReceive('execute')->with('tenant-1', 'emp-1')->andReturn([
            'gross'      => '0.00000000',
            'deductions' => '0.00000000',
            'net'        => '0.00000000',
            'components' => [],
        ]);

        $payslipRepo = Mockery::mock(PayslipRepositoryInterface::class);
        $payslipRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['gross_salary'] === '3000.00000000'
                    && $data['deductions']   === '0.00000000'
                    && $data['net_salary']   === '3000.00000000'
                    && $data['status']       === 'pending';
            })
            ->andReturn((object) []);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $this->makeJob()->handle($computeMock, $employeeRepo, $payslipRepo);
        $this->addToAssertionCount(1);
    }

    public function test_uses_salary_structure_when_assigned_and_verifies_bcmath_precision(): void
    {
        $employee = (object) ['id' => 'emp-1', 'status' => 'active', 'salary' => '5000.00000000'];

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->andReturn($employee);

        // Structure provides non-zero gross → structure values used instead of employee.salary
        $computeMock = Mockery::mock(ComputePayslipComponentsUseCase::class);
        $computeMock->shouldReceive('execute')->with('tenant-1', 'emp-1')->andReturn([
            'gross'      => '4500.00000000',
            'deductions' => '500.00000000',
            'net'        => '4000.00000000',
            'components' => [],
        ]);

        $payslipRepo = Mockery::mock(PayslipRepositoryInterface::class);
        $payslipRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['gross_salary'] === '4500.00000000'
                    && $data['deductions']   === '500.00000000'
                    && $data['net_salary']   === '4000.00000000';
            })
            ->andReturn((object) []);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $this->makeJob()->handle($computeMock, $employeeRepo, $payslipRepo);
        $this->addToAssertionCount(1);
    }
}
