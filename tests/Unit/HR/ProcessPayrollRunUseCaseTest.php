<?php

namespace Tests\Unit\HR;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\HR\Application\UseCases\ComputePayslipComponentsUseCase;
use Modules\HR\Application\UseCases\ProcessPayrollRunUseCase;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Contracts\PayrollRunRepositoryInterface;
use Modules\HR\Domain\Contracts\PayslipRepositoryInterface;
use Modules\HR\Domain\Events\PayrollRunCompleted;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProcessPayrollRunUseCase.
 *
 * Verifies BCMath payroll calculations, salary-structure integration,
 * employee-salary fallback, duplicate-processing guard, and that
 * PayrollRunCompleted is dispatched on success.
 */
class ProcessPayrollRunUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeRun(string $status = 'draft', string $id = 'run-uuid-1'): object
    {
        return (object) [
            'id'        => $id,
            'tenant_id' => 'tenant-uuid-1',
            'status'    => $status,
        ];
    }

    /** Returns a mock ComputePayslipComponentsUseCase that always yields zeros (no structure assigned). */
    private function noStructureMock(): ComputePayslipComponentsUseCase
    {
        $mock = Mockery::mock(ComputePayslipComponentsUseCase::class);
        $mock->shouldReceive('execute')->andReturn([
            'gross'      => '0.00000000',
            'deductions' => '0.00000000',
            'net'        => '0.00000000',
            'components' => [],
        ]);
        return $mock;
    }

    public function test_throws_when_run_not_found(): void
    {
        $runRepo = Mockery::mock(PayrollRunRepositoryInterface::class);
        $runRepo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        $payslipRepo   = Mockery::mock(PayslipRepositoryInterface::class);
        $employeeRepo  = Mockery::mock(EmployeeRepositoryInterface::class);
        $computeMock   = Mockery::mock(ComputePayslipComponentsUseCase::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ProcessPayrollRunUseCase($runRepo, $payslipRepo, $employeeRepo, $computeMock);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_throws_when_run_already_completed(): void
    {
        $run = $this->makeRun('completed');

        $runRepo = Mockery::mock(PayrollRunRepositoryInterface::class);
        $runRepo->shouldReceive('findById')->andReturn($run);

        $payslipRepo   = Mockery::mock(PayslipRepositoryInterface::class);
        $employeeRepo  = Mockery::mock(EmployeeRepositoryInterface::class);
        $computeMock   = Mockery::mock(ComputePayslipComponentsUseCase::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ProcessPayrollRunUseCase($runRepo, $payslipRepo, $employeeRepo, $computeMock);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already completed/i');

        $useCase->execute('run-uuid-1');
    }

    public function test_completes_run_with_zero_employees_and_dispatches_event(): void
    {
        $run     = $this->makeRun('draft');
        $updated = (object) array_merge((array) $run, ['status' => 'completed']);

        $runRepo = Mockery::mock(PayrollRunRepositoryInterface::class);
        $runRepo->shouldReceive('findById')->with('run-uuid-1')->andReturn($run);
        $runRepo->shouldReceive('update')
            ->with('run-uuid-1', ['status' => 'processing'])
            ->once()
            ->andReturn((object) array_merge((array) $run, ['status' => 'processing']));
        $runRepo->shouldReceive('update')
            ->with('run-uuid-1', Mockery::on(fn ($d) => $d['status'] === 'completed'
                && $d['total_gross'] === '0.00000000'
                && $d['total_net']   === '0.00000000'))
            ->once()
            ->andReturn($updated);

        $payslipRepo = Mockery::mock(PayslipRepositoryInterface::class);

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('chunkActive')
            ->once()
            ->withArgs(fn ($tid, $size, $cb) => $tid === 'tenant-uuid-1' && $size === 100)
            ->andReturnUsing(function ($tid, $size, $cb) {
                // Zero employees — callback invoked with empty collection
                $cb(new \Illuminate\Support\Collection([]));
            });

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PayrollRunCompleted);

        $useCase = new ProcessPayrollRunUseCase($runRepo, $payslipRepo, $employeeRepo, $this->noStructureMock());
        $result  = $useCase->execute('run-uuid-1');

        $this->assertSame('completed', $result->status);
    }

    public function test_creates_payslips_and_accumulates_totals_using_employee_salary_fallback(): void
    {
        $run = $this->makeRun('draft');

        $runRepo = Mockery::mock(PayrollRunRepositoryInterface::class);
        $runRepo->shouldReceive('findById')->andReturn($run);
        $runRepo->shouldReceive('update')->with('run-uuid-1', ['status' => 'processing'])->andReturn($run);
        $runRepo->shouldReceive('update')
            ->with('run-uuid-1', Mockery::on(function ($d) {
                // Two employees at 1000 each → total_gross = 2000, total_net = 2000 (no deductions)
                return $d['status'] === 'completed'
                    && $d['total_gross'] === '2000.00000000'
                    && $d['total_net']   === '2000.00000000';
            }))
            ->once()
            ->andReturn((object) array_merge((array) $run, ['status' => 'completed']));

        $payslipRepo = Mockery::mock(PayslipRepositoryInterface::class);
        $payslipRepo->shouldReceive('create')->twice()->andReturn((object) []);

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('chunkActive')
            ->once()
            ->andReturnUsing(function ($tid, $size, $cb) {
                $employees = new \Illuminate\Support\Collection([
                    (object) ['id' => 'emp-1', 'salary' => '1000.00000000'],
                    (object) ['id' => 'emp-2', 'salary' => '1000.00000000'],
                ]);
                $cb($employees);
            });

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        // computePayslip returns zeros → fallback to employee.salary
        $useCase = new ProcessPayrollRunUseCase($runRepo, $payslipRepo, $employeeRepo, $this->noStructureMock());
        $result  = $useCase->execute('run-uuid-1');

        $this->assertSame('completed', $result->status);
    }

    public function test_uses_salary_structure_when_assigned(): void
    {
        $run = $this->makeRun('draft');

        $runRepo = Mockery::mock(PayrollRunRepositoryInterface::class);
        $runRepo->shouldReceive('findById')->andReturn($run);
        $runRepo->shouldReceive('update')->with('run-uuid-1', ['status' => 'processing'])->andReturn($run);
        $runRepo->shouldReceive('update')
            ->with('run-uuid-1', Mockery::on(function ($d) {
                // gross = 4500 (3000 basic + 1500 HRA), deductions = 500, net = 4000
                return $d['status'] === 'completed'
                    && $d['total_gross'] === '4500.00000000'
                    && $d['total_net']   === '4000.00000000';
            }))
            ->once()
            ->andReturn((object) array_merge((array) $run, ['status' => 'completed']));

        $payslipRepo = Mockery::mock(PayslipRepositoryInterface::class);
        $payslipRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['gross_salary'] === '4500.00000000'
                    && $data['deductions']   === '500.00000000'
                    && $data['net_salary']   === '4000.00000000';
            })
            ->andReturn((object) []);

        $employeeRepo = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('chunkActive')
            ->once()
            ->andReturnUsing(function ($tid, $size, $cb) {
                $cb(new \Illuminate\Support\Collection([
                    (object) ['id' => 'emp-1', 'salary' => '5000.00000000'],
                ]));
            });

        // computePayslip returns structure-based values (non-zero gross) → structure used.
        $computeMock = Mockery::mock(ComputePayslipComponentsUseCase::class);
        $computeMock->shouldReceive('execute')
            ->with('tenant-uuid-1', 'emp-1')
            ->once()
            ->andReturn([
                'gross'      => '4500.00000000',
                'deductions' => '500.00000000',
                'net'        => '4000.00000000',
                'components' => [],
            ]);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new ProcessPayrollRunUseCase($runRepo, $payslipRepo, $employeeRepo, $computeMock);
        $result  = $useCase->execute('run-uuid-1');

        $this->assertSame('completed', $result->status);
    }
}
