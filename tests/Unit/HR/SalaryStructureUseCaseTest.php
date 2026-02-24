<?php

namespace Tests\Unit\HR;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\HR\Application\UseCases\AssignSalaryStructureUseCase;
use Modules\HR\Application\UseCases\ComputePayslipComponentsUseCase;
use Modules\HR\Application\UseCases\CreateSalaryComponentUseCase;
use Modules\HR\Application\UseCases\CreateSalaryStructureUseCase;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Contracts\SalaryComponentRepositoryInterface;
use Modules\HR\Domain\Contracts\SalaryStructureAssignmentRepositoryInterface;
use Modules\HR\Domain\Contracts\SalaryStructureRepositoryInterface;
use Modules\HR\Domain\Enums\SalaryComponentType;
use Modules\HR\Domain\Events\SalaryComponentCreated;
use Modules\HR\Domain\Events\SalaryStructureAssigned;
use Modules\HR\Domain\Events\SalaryStructureCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HR Salary Structure use cases.
 *
 * Covers: component creation, structure creation, structure assignment,
 * payslip component computation (BCMath earnings/deductions).
 */
class SalaryStructureUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // CreateSalaryComponentUseCase
    // -----------------------------------------------------------------------

    public function test_create_salary_component_throws_on_empty_code(): void
    {
        $repo = Mockery::mock(SalaryComponentRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateSalaryComponentUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('cannot be empty');

        $useCase->execute([
            'tenant_id'      => 'tenant-1',
            'name'           => 'Basic Salary',
            'code'           => '   ',
            'type'           => SalaryComponentType::Earning->value,
            'default_amount' => '5000',
        ]);
    }

    public function test_create_salary_component_throws_on_duplicate_code(): void
    {
        $repo = Mockery::mock(SalaryComponentRepositoryInterface::class);
        $repo->shouldReceive('findByCode')
            ->with('tenant-1', 'BASIC')
            ->andReturn((object) ['id' => 'existing-1', 'code' => 'BASIC']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateSalaryComponentUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already exists');

        $useCase->execute([
            'tenant_id'      => 'tenant-1',
            'name'           => 'Basic',
            'code'           => 'basic',
            'type'           => SalaryComponentType::Earning->value,
            'default_amount' => '5000',
        ]);
    }

    public function test_create_salary_component_earning_normalises_amount_and_dispatches_event(): void
    {
        $repo = Mockery::mock(SalaryComponentRepositoryInterface::class);
        $repo->shouldReceive('findByCode')->with('tenant-1', 'BASIC')->andReturnNull();
        $component = (object) [
            'id'             => 'comp-1',
            'code'           => 'BASIC',
            'type'           => SalaryComponentType::Earning->value,
            'default_amount' => '5000.00000000',
        ];
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['code'] === 'BASIC'
                    && $data['type'] === SalaryComponentType::Earning->value
                    && $data['default_amount'] === '5000.00000000'
                    && $data['is_active'] === true;
            })
            ->andReturn($component);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof SalaryComponentCreated
                && $e->componentId === 'comp-1'
                && $e->code === 'BASIC'
                && $e->type === SalaryComponentType::Earning->value);

        $useCase   = new CreateSalaryComponentUseCase($repo);
        $result    = $useCase->execute([
            'tenant_id'      => 'tenant-1',
            'name'           => 'Basic Salary',
            'code'           => 'basic',
            'type'           => SalaryComponentType::Earning->value,
            'default_amount' => '5000',
        ]);

        $this->assertSame('5000.00000000', $result->default_amount);
    }

    public function test_create_salary_component_deduction_sets_correct_type(): void
    {
        $repo = Mockery::mock(SalaryComponentRepositoryInterface::class);
        $repo->shouldReceive('findByCode')->andReturnNull();
        $component = (object) [
            'id'   => 'comp-2',
            'code' => 'PF',
            'type' => SalaryComponentType::Deduction->value,
        ];
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($d) => $d['type'] === SalaryComponentType::Deduction->value)
            ->andReturn($component);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreateSalaryComponentUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'      => 'tenant-1',
            'name'           => 'Provident Fund',
            'code'           => 'pf',
            'type'           => SalaryComponentType::Deduction->value,
            'default_amount' => '500',
        ]);

        $this->assertSame(SalaryComponentType::Deduction->value, $result->type);
    }

    // -----------------------------------------------------------------------
    // CreateSalaryStructureUseCase
    // -----------------------------------------------------------------------

    public function test_create_salary_structure_throws_on_empty_code(): void
    {
        $repo = Mockery::mock(SalaryStructureRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateSalaryStructureUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('cannot be empty');

        $useCase->execute([
            'tenant_id' => 'tenant-1',
            'name'      => 'Standard',
            'code'      => '',
            'lines'     => [['component_id' => 'comp-1']],
        ]);
    }

    public function test_create_salary_structure_throws_when_no_lines(): void
    {
        $repo = Mockery::mock(SalaryStructureRepositoryInterface::class);
        $repo->shouldReceive('findByCode')->andReturnNull();

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateSalaryStructureUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('at least one component line');

        $useCase->execute([
            'tenant_id' => 'tenant-1',
            'name'      => 'Standard',
            'code'      => 'STD',
            'lines'     => [],
        ]);
    }

    public function test_create_salary_structure_success_dispatches_event(): void
    {
        $repo = Mockery::mock(SalaryStructureRepositoryInterface::class);
        $repo->shouldReceive('findByCode')->with('tenant-1', 'STD')->andReturnNull();

        $structure = (object) ['id' => 'str-1', 'code' => 'STD', 'is_active' => true, 'lines' => []];
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['code'] === 'STD'
                    && $data['is_active'] === true
                    && ! empty($data['lines']);
            })
            ->andReturn($structure);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof SalaryStructureCreated
                && $e->structureId === 'str-1'
                && $e->code === 'STD');

        $useCase = new CreateSalaryStructureUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-1',
            'name'      => 'Standard',
            'code'      => 'std',
            'lines'     => [['component_id' => 'comp-1', 'sequence' => 10]],
        ]);

        $this->assertSame('str-1', $result->id);
    }

    // -----------------------------------------------------------------------
    // AssignSalaryStructureUseCase
    // -----------------------------------------------------------------------

    public function test_assign_structure_throws_when_employee_not_found(): void
    {
        $structureRepo  = Mockery::mock(SalaryStructureRepositoryInterface::class);
        $assignmentRepo = Mockery::mock(SalaryStructureAssignmentRepositoryInterface::class);
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->with('emp-1')->andReturnNull();

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new AssignSalaryStructureUseCase($structureRepo, $assignmentRepo, $employeeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Employee');

        $useCase->execute([
            'tenant_id'    => 'tenant-1',
            'employee_id'  => 'emp-1',
            'structure_id' => 'str-1',
            'base_amount'  => '5000',
        ]);
    }

    public function test_assign_structure_throws_when_structure_not_found(): void
    {
        $structureRepo  = Mockery::mock(SalaryStructureRepositoryInterface::class);
        $assignmentRepo = Mockery::mock(SalaryStructureAssignmentRepositoryInterface::class);
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->andReturn((object) ['id' => 'emp-1']);
        $structureRepo->shouldReceive('findById')->with('str-1')->andReturnNull();

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new AssignSalaryStructureUseCase($structureRepo, $assignmentRepo, $employeeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Salary structure');

        $useCase->execute([
            'tenant_id'    => 'tenant-1',
            'employee_id'  => 'emp-1',
            'structure_id' => 'str-1',
            'base_amount'  => '5000',
        ]);
    }

    public function test_assign_structure_throws_when_base_amount_zero(): void
    {
        $structureRepo  = Mockery::mock(SalaryStructureRepositoryInterface::class);
        $assignmentRepo = Mockery::mock(SalaryStructureAssignmentRepositoryInterface::class);
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->andReturn((object) ['id' => 'emp-1']);
        $structureRepo->shouldReceive('findById')->andReturn((object) ['id' => 'str-1', 'is_active' => true]);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new AssignSalaryStructureUseCase($structureRepo, $assignmentRepo, $employeeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Base amount must be greater than zero');

        $useCase->execute([
            'tenant_id'    => 'tenant-1',
            'employee_id'  => 'emp-1',
            'structure_id' => 'str-1',
            'base_amount'  => '0',
        ]);
    }

    public function test_assign_structure_success_dispatches_event(): void
    {
        $structureRepo  = Mockery::mock(SalaryStructureRepositoryInterface::class);
        $assignmentRepo = Mockery::mock(SalaryStructureAssignmentRepositoryInterface::class);
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);

        $employeeRepo->shouldReceive('findById')->andReturn((object) ['id' => 'emp-1']);
        $structureRepo->shouldReceive('findById')->andReturn((object) ['id' => 'str-1', 'is_active' => true]);

        $assignment = (object) ['id' => 'assign-1', 'employee_id' => 'emp-1', 'structure_id' => 'str-1'];
        $assignmentRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['employee_id'] === 'emp-1'
                    && $data['structure_id'] === 'str-1'
                    && $data['base_amount'] === '5000.00000000';
            })
            ->andReturn($assignment);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof SalaryStructureAssigned
                && $e->assignmentId === 'assign-1'
                && $e->employeeId === 'emp-1'
                && $e->structureId === 'str-1');

        $useCase = new AssignSalaryStructureUseCase($structureRepo, $assignmentRepo, $employeeRepo);
        $result  = $useCase->execute([
            'tenant_id'    => 'tenant-1',
            'employee_id'  => 'emp-1',
            'structure_id' => 'str-1',
            'base_amount'  => '5000',
        ]);

        $this->assertSame('assign-1', $result->id);
    }

    // -----------------------------------------------------------------------
    // ComputePayslipComponentsUseCase
    // -----------------------------------------------------------------------

    public function test_compute_payslip_returns_zeros_when_no_assignment(): void
    {
        $assignmentRepo = Mockery::mock(SalaryStructureAssignmentRepositoryInterface::class);
        $structureRepo  = Mockery::mock(SalaryStructureRepositoryInterface::class);

        $assignmentRepo->shouldReceive('findActiveByEmployee')->andReturnNull();

        $useCase = new ComputePayslipComponentsUseCase($assignmentRepo, $structureRepo);
        $result  = $useCase->execute('tenant-1', 'emp-1');

        $this->assertSame('0.00000000', $result['gross']);
        $this->assertSame('0.00000000', $result['deductions']);
        $this->assertSame('0.00000000', $result['net']);
        $this->assertSame([], $result['components']);
    }

    public function test_compute_payslip_calculates_bcmath_gross_deductions_and_net(): void
    {
        $assignmentRepo = Mockery::mock(SalaryStructureAssignmentRepositoryInterface::class);
        $structureRepo  = Mockery::mock(SalaryStructureRepositoryInterface::class);

        $assignment = (object) [
            'structure_id' => 'str-1',
            'base_amount'  => '5000.00000000',
        ];
        $assignmentRepo->shouldReceive('findActiveByEmployee')->andReturn($assignment);

        // Structure with two earnings (basic + HRA) and one deduction (PF).
        // basic = 3000, HRA = 1500 (via override), PF = 500 (deduction)
        // gross = 3000 + 1500 = 4500, deductions = 500, net = 4000
        $basicComponent = (object) ['name' => 'Basic', 'code' => 'BASIC', 'type' => 'earning', 'default_amount' => '3000.00000000'];
        $hraComponent   = (object) ['name' => 'HRA', 'code' => 'HRA', 'type' => 'earning', 'default_amount' => '1000.00000000'];
        $pfComponent    = (object) ['name' => 'PF', 'code' => 'PF', 'type' => 'deduction', 'default_amount' => '500.00000000'];

        $structure = (object) [
            'id'    => 'str-1',
            'lines' => [
                (object) ['component_id' => 'comp-1', 'sequence' => 10, 'override_amount' => null, 'component' => $basicComponent],
                (object) ['component_id' => 'comp-2', 'sequence' => 20, 'override_amount' => '1500.00000000', 'component' => $hraComponent],
                (object) ['component_id' => 'comp-3', 'sequence' => 30, 'override_amount' => null, 'component' => $pfComponent],
            ],
        ];
        $structureRepo->shouldReceive('findWithLines')->with('str-1')->andReturn($structure);

        $useCase = new ComputePayslipComponentsUseCase($assignmentRepo, $structureRepo);
        $result  = $useCase->execute('tenant-1', 'emp-1');

        // BCMath: 3000 + 1500 = 4500 gross
        $this->assertSame('4500.00000000', $result['gross']);
        // BCMath: 500 deductions
        $this->assertSame('500.00000000', $result['deductions']);
        // BCMath: 4500 - 500 = 4000 net
        $this->assertSame('4000.00000000', $result['net']);
        $this->assertCount(3, $result['components']);
        $this->assertSame('1500.00000000', $result['components'][1]['amount']); // override applied
    }
}
