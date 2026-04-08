<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\Core\Application\Contracts\WriteServiceInterface;
use Modules\Core\Application\DTOs\BaseDto;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Core\Domain\Events\BaseEvent;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\HR\Application\Contracts\ApproveLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CancelLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CreateDepartmentServiceInterface;
use Modules\HR\Application\Contracts\CreateEmployeeServiceInterface;
use Modules\HR\Application\Contracts\CreateLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\CreatePositionServiceInterface;
use Modules\HR\Application\Contracts\DeleteDepartmentServiceInterface;
use Modules\HR\Application\Contracts\DeleteEmployeeServiceInterface;
use Modules\HR\Application\Contracts\DeleteLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\DeletePositionServiceInterface;
use Modules\HR\Application\Contracts\FindDepartmentServiceInterface;
use Modules\HR\Application\Contracts\FindEmployeeServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\FindPositionServiceInterface;
use Modules\HR\Application\Contracts\RejectLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\UpdateDepartmentServiceInterface;
use Modules\HR\Application\Contracts\UpdateEmployeeServiceInterface;
use Modules\HR\Application\Contracts\UpdateLeaveRequestServiceInterface;
use Modules\HR\Application\Contracts\UpdatePositionServiceInterface;
use Modules\HR\Application\DTOs\DepartmentData;
use Modules\HR\Application\DTOs\EmployeeData;
use Modules\HR\Application\DTOs\LeaveRequestData;
use Modules\HR\Application\DTOs\PositionData;
use Modules\HR\Application\DTOs\UpdateDepartmentData;
use Modules\HR\Application\DTOs\UpdateEmployeeData;
use Modules\HR\Application\DTOs\UpdateLeaveRequestData;
use Modules\HR\Application\DTOs\UpdatePositionData;
use Modules\HR\Application\Services\ApproveLeaveRequestService;
use Modules\HR\Application\Services\CancelLeaveRequestService;
use Modules\HR\Application\Services\CreateDepartmentService;
use Modules\HR\Application\Services\CreateEmployeeService;
use Modules\HR\Application\Services\CreateLeaveRequestService;
use Modules\HR\Application\Services\CreatePositionService;
use Modules\HR\Application\Services\DeleteDepartmentService;
use Modules\HR\Application\Services\DeleteEmployeeService;
use Modules\HR\Application\Services\DeleteLeaveRequestService;
use Modules\HR\Application\Services\DeletePositionService;
use Modules\HR\Application\Services\FindDepartmentService;
use Modules\HR\Application\Services\FindEmployeeService;
use Modules\HR\Application\Services\FindLeaveRequestService;
use Modules\HR\Application\Services\FindPositionService;
use Modules\HR\Application\Services\RejectLeaveRequestService;
use Modules\HR\Application\Services\UpdateDepartmentService;
use Modules\HR\Application\Services\UpdateEmployeeService;
use Modules\HR\Application\Services\UpdateLeaveRequestService;
use Modules\HR\Application\Services\UpdatePositionService;
use Modules\HR\Domain\Entities\Department;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Entities\Position;
use Modules\HR\Domain\Events\DepartmentCreated;
use Modules\HR\Domain\Events\DepartmentDeleted;
use Modules\HR\Domain\Events\DepartmentUpdated;
use Modules\HR\Domain\Events\EmployeeCreated;
use Modules\HR\Domain\Events\EmployeeDeleted;
use Modules\HR\Domain\Events\EmployeeUpdated;
use Modules\HR\Domain\Events\LeaveRequestApproved;
use Modules\HR\Domain\Events\LeaveRequestCancelled;
use Modules\HR\Domain\Events\LeaveRequestCreated;
use Modules\HR\Domain\Events\LeaveRequestDeleted;
use Modules\HR\Domain\Events\PositionCreated;
use Modules\HR\Domain\Events\PositionDeleted;
use Modules\HR\Domain\Events\PositionUpdated;
use Modules\HR\Domain\Exceptions\DepartmentNotFoundException;
use Modules\HR\Domain\Exceptions\EmployeeNotFoundException;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\Exceptions\PositionNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;
use Modules\HR\Infrastructure\Http\Controllers\DepartmentController;
use Modules\HR\Infrastructure\Http\Controllers\EmployeeController;
use Modules\HR\Infrastructure\Http\Controllers\LeaveRequestController;
use Modules\HR\Infrastructure\Http\Requests\StoreDepartmentRequest;
use Modules\HR\Infrastructure\Http\Requests\StoreEmployeeRequest;
use Modules\HR\Infrastructure\Http\Requests\StoreLeaveRequestRequest;
use Modules\HR\Infrastructure\Http\Requests\StorePositionRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateDepartmentRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateEmployeeRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateLeaveRequestRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdatePositionRequest;
use Modules\HR\Infrastructure\Http\Resources\DepartmentCollection;
use Modules\HR\Infrastructure\Http\Resources\DepartmentResource;
use Modules\HR\Infrastructure\Http\Resources\EmployeeCollection;
use Modules\HR\Infrastructure\Http\Resources\EmployeeResource;
use Modules\HR\Infrastructure\Http\Resources\LeaveRequestCollection;
use Modules\HR\Infrastructure\Http\Resources\LeaveRequestResource;
use Modules\HR\Infrastructure\Http\Resources\PositionCollection;
use Modules\HR\Infrastructure\Http\Resources\PositionResource;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveRequestModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PositionModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentDepartmentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveRequestRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPositionRepository;
use Modules\HR\Infrastructure\Providers\HRServiceProvider;
use PHPUnit\Framework\TestCase;

class HRModuleTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createTestEmployee(int $id = 1, int $tenantId = 1): Employee
    {
        $employee = new Employee(
            tenantId:       $tenantId,
            firstName:      new Name('John'),
            lastName:       new Name('Doe'),
            email:          new Email('john.doe@example.com'),
            employeeNumber: new Code('EMP-001'),
            hireDate:       new \DateTimeImmutable('2023-01-01'),
            employmentType: 'full_time',
        );
        $ref = new \ReflectionProperty($employee, 'id');
        $ref->setAccessible(true);
        $ref->setValue($employee, $id);
        return $employee;
    }

    private function createTestDepartment(int $id = 1, int $tenantId = 1): Department
    {
        $department = new Department(tenantId: $tenantId, name: new Name('Engineering'));
        $ref = new \ReflectionProperty($department, 'id');
        $ref->setAccessible(true);
        $ref->setValue($department, $id);
        return $department;
    }

    private function createTestPosition(int $id = 1, int $tenantId = 1): Position
    {
        $position = new Position(tenantId: $tenantId, name: new Name('Senior Developer'));
        $ref = new \ReflectionProperty($position, 'id');
        $ref->setAccessible(true);
        $ref->setValue($position, $id);
        return $position;
    }

    private function createTestLeaveRequest(int $id = 1, int $tenantId = 1, int $employeeId = 10): LeaveRequest
    {
        $lr = new LeaveRequest(
            tenantId:   $tenantId,
            employeeId: $employeeId,
            leaveType:  'annual',
            startDate:  new \DateTimeImmutable('2024-06-01'),
            endDate:    new \DateTimeImmutable('2024-06-05'),
        );
        $ref = new \ReflectionProperty($lr, 'id');
        $ref->setAccessible(true);
        $ref->setValue($lr, $id);
        return $lr;
    }

    // ── Exceptions ────────────────────────────────────────────────────────────

    public function test_employee_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(EmployeeNotFoundException::class));
    }

    public function test_employee_not_found_exception_extends_not_found_exception(): void
    {
        $this->assertTrue(is_subclass_of(EmployeeNotFoundException::class, NotFoundException::class));
    }

    public function test_employee_not_found_exception_message_contains_id(): void
    {
        $e = new EmployeeNotFoundException(42);
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertStringContainsString('Employee', $e->getMessage());
    }

    public function test_employee_not_found_exception_without_id(): void
    {
        $e = new EmployeeNotFoundException;
        $this->assertStringContainsString('Employee', $e->getMessage());
    }

    public function test_department_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(DepartmentNotFoundException::class));
    }

    public function test_department_not_found_exception_extends_not_found_exception(): void
    {
        $this->assertTrue(is_subclass_of(DepartmentNotFoundException::class, NotFoundException::class));
    }

    public function test_department_not_found_exception_message_contains_id(): void
    {
        $e = new DepartmentNotFoundException(7);
        $this->assertStringContainsString('7', $e->getMessage());
        $this->assertStringContainsString('Department', $e->getMessage());
    }

    public function test_position_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(PositionNotFoundException::class));
    }

    public function test_position_not_found_exception_extends_not_found_exception(): void
    {
        $this->assertTrue(is_subclass_of(PositionNotFoundException::class, NotFoundException::class));
    }

    public function test_position_not_found_exception_message_contains_id(): void
    {
        $e = new PositionNotFoundException(99);
        $this->assertStringContainsString('99', $e->getMessage());
        $this->assertStringContainsString('Position', $e->getMessage());
    }

    public function test_leave_request_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequestNotFoundException::class));
    }

    public function test_leave_request_not_found_exception_extends_not_found_exception(): void
    {
        $this->assertTrue(is_subclass_of(LeaveRequestNotFoundException::class, NotFoundException::class));
    }

    public function test_leave_request_not_found_exception_message_contains_id(): void
    {
        $e = new LeaveRequestNotFoundException(5);
        $this->assertStringContainsString('5', $e->getMessage());
        $this->assertStringContainsString('LeaveRequest', $e->getMessage());
    }

    // ── Employee Entity ────────────────────────────────────────────────────────

    public function test_employee_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Employee::class));
    }

    public function test_employee_entity_can_be_constructed_full(): void
    {
        $hireDate = new \DateTimeImmutable('2022-03-15');
        $employee = new Employee(
            tenantId:       1,
            firstName:      new Name('Jane'),
            lastName:       new Name('Smith'),
            email:          new Email('jane.smith@example.com'),
            employeeNumber: new Code('EMP-100'),
            hireDate:       $hireDate,
            employmentType: 'full_time',
            status:         'active',
            phone:          new PhoneNumber('555-1234'),
            dateOfBirth:    '1990-05-10',
            gender:         'female',
            address:        '10 Main St',
            departmentId:   2,
            positionId:     3,
            managerId:      4,
            salary:         75000.0,
            currency:       'USD',
            orgUnitId:      5,
            metadata:       new Metadata(['badge' => 'gold']),
            isActive:       true,
        );

        $this->assertSame(1, $employee->getTenantId());
        $this->assertSame('Jane', $employee->getFirstName()->value());
        $this->assertSame('Smith', $employee->getLastName()->value());
        $this->assertSame('jane.smith@example.com', $employee->getEmail()->value());
        $this->assertSame('EMP-100', $employee->getEmployeeNumber()->value());
        $this->assertSame($hireDate, $employee->getHireDate());
        $this->assertSame('full_time', $employee->getEmploymentType());
        $this->assertSame('active', $employee->getStatus());
        $this->assertSame('555-1234', $employee->getPhone()->value());
        $this->assertSame('1990-05-10', $employee->getDateOfBirth());
        $this->assertSame('female', $employee->getGender());
        $this->assertSame('10 Main St', $employee->getAddress());
        $this->assertSame(2, $employee->getDepartmentId());
        $this->assertSame(3, $employee->getPositionId());
        $this->assertSame(4, $employee->getManagerId());
        $this->assertSame(75000.0, $employee->getSalary());
        $this->assertSame('USD', $employee->getCurrency());
        $this->assertSame(5, $employee->getOrgUnitId());
        $this->assertTrue($employee->isActive());
        $this->assertNull($employee->getId());
    }

    public function test_employee_entity_minimal_construction(): void
    {
        $employee = new Employee(
            tenantId:       2,
            firstName:      new Name('Bob'),
            lastName:       new Name('Jones'),
            email:          new Email('bob.jones@example.com'),
            employeeNumber: new Code('EMP-002'),
            hireDate:       new \DateTimeImmutable('2023-06-01'),
            employmentType: 'part_time',
        );

        $this->assertNull($employee->getId());
        $this->assertSame(2, $employee->getTenantId());
        $this->assertNull($employee->getPhone());
        $this->assertNull($employee->getDateOfBirth());
        $this->assertNull($employee->getGender());
        $this->assertNull($employee->getAddress());
        $this->assertNull($employee->getDepartmentId());
        $this->assertNull($employee->getPositionId());
        $this->assertNull($employee->getManagerId());
        $this->assertNull($employee->getSalary());
        $this->assertSame('USD', $employee->getCurrency());
        $this->assertNull($employee->getOrgUnitId());
        $this->assertTrue($employee->isActive());
        $this->assertSame('active', $employee->getStatus());
        $this->assertInstanceOf(Metadata::class, $employee->getMetadata());
    }

    public function test_employee_entity_has_timestamps(): void
    {
        $employee = $this->createTestEmployee();
        $this->assertInstanceOf(\DateTimeInterface::class, $employee->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $employee->getUpdatedAt());
    }

    public function test_employee_entity_update_details(): void
    {
        $employee = $this->createTestEmployee();
        $newHireDate = new \DateTimeImmutable('2024-01-01');

        $employee->updateDetails(
            new Name('Updated'), new Name('Name'), new Email('updated@example.com'),
            new PhoneNumber('+1234567890'), '1985-12-25', 'male', '99 New Road',
            new Code('EMP-999'), $newHireDate, 'contract', 'on_leave',
            3, 4, 5, 90000.0, 'EUR', 6, new Metadata(['updated' => true]), false
        );

        $this->assertSame('Updated', $employee->getFirstName()->value());
        $this->assertSame('Name', $employee->getLastName()->value());
        $this->assertSame('updated@example.com', $employee->getEmail()->value());
        $this->assertSame('+1234567890', $employee->getPhone()->value());
        $this->assertSame('1985-12-25', $employee->getDateOfBirth());
        $this->assertSame('male', $employee->getGender());
        $this->assertSame('99 New Road', $employee->getAddress());
        $this->assertSame('EMP-999', $employee->getEmployeeNumber()->value());
        $this->assertSame($newHireDate, $employee->getHireDate());
        $this->assertSame('contract', $employee->getEmploymentType());
        $this->assertSame('on_leave', $employee->getStatus());
        $this->assertSame(3, $employee->getDepartmentId());
        $this->assertSame(90000.0, $employee->getSalary());
        $this->assertSame('EUR', $employee->getCurrency());
        $this->assertFalse($employee->isActive());
    }

    public function test_employee_entity_update_details_clears_nullable_fields(): void
    {
        $employee = new Employee(
            tenantId: 1, firstName: new Name('John'), lastName: new Name('Doe'),
            email: new Email('john@example.com'), employeeNumber: new Code('EMP-001'),
            hireDate: new \DateTimeImmutable('2023-01-01'), employmentType: 'full_time',
            phone: new PhoneNumber('555-1234'), dateOfBirth: '1990-01-01',
            gender: 'male', address: '123 St', departmentId: 2, positionId: 3,
        );

        $employee->updateDetails(
            new Name('John'), new Name('Doe'), new Email('john@example.com'),
            null, null, null, null,
            new Code('EMP-001'), new \DateTimeImmutable('2023-01-01'),
            'full_time', 'active', null, null, null, null, 'USD', null, null, true
        );

        $this->assertNull($employee->getPhone());
        $this->assertNull($employee->getDateOfBirth());
        $this->assertNull($employee->getGender());
        $this->assertNull($employee->getAddress());
        $this->assertNull($employee->getDepartmentId());
        $this->assertNull($employee->getPositionId());
    }

    public function test_employee_entity_terminate(): void
    {
        $employee = $this->createTestEmployee();
        $employee->terminate();
        $this->assertSame('terminated', $employee->getStatus());
    }

    public function test_employee_entity_activate(): void
    {
        $employee = new Employee(
            tenantId: 1, firstName: new Name('John'), lastName: new Name('Doe'),
            email: new Email('john@example.com'), employeeNumber: new Code('EMP-001'),
            hireDate: new \DateTimeImmutable('2023-01-01'), employmentType: 'full_time',
            isActive: false,
        );
        $employee->activate();
        $this->assertTrue($employee->isActive());
    }

    public function test_employee_entity_deactivate(): void
    {
        $employee = $this->createTestEmployee();
        $employee->deactivate();
        $this->assertFalse($employee->isActive());
    }

    public function test_employee_entity_update_changes_updated_at(): void
    {
        $before = new \DateTimeImmutable('2020-01-01');
        $employee = new Employee(
            tenantId: 1, firstName: new Name('John'), lastName: new Name('Doe'),
            email: new Email('john@example.com'), employeeNumber: new Code('EMP-001'),
            hireDate: new \DateTimeImmutable('2023-01-01'), employmentType: 'full_time',
            updatedAt: $before,
        );

        $employee->updateDetails(
            new Name('John'), new Name('Doe'), new Email('john@example.com'),
            null, null, null, null,
            new Code('EMP-001'), new \DateTimeImmutable('2023-01-01'),
            'full_time', 'active', null, null, null, null, 'USD', null, null, true
        );

        $this->assertGreaterThan($before->getTimestamp(), $employee->getUpdatedAt()->getTimestamp());
    }

    // ── Department Entity ─────────────────────────────────────────────────────

    public function test_department_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Department::class));
    }

    public function test_department_entity_can_be_constructed_full(): void
    {
        $department = new Department(
            tenantId: 1, name: new Name('HR Department'),
            code: new Code('HR-001'), description: 'Human Resources',
            managerId: 5, parentId: null, lft: 1, rgt: 4,
            metadata: new Metadata(['floor' => 2]), isActive: true,
        );

        $this->assertSame(1, $department->getTenantId());
        $this->assertSame('HR Department', $department->getName()->value());
        $this->assertSame('HR-001', $department->getCode()->value());
        $this->assertSame('Human Resources', $department->getDescription());
        $this->assertSame(5, $department->getManagerId());
        $this->assertNull($department->getParentId());
        $this->assertSame(1, $department->getLft());
        $this->assertSame(4, $department->getRgt());
        $this->assertTrue($department->isActive());
        $this->assertNull($department->getId());
    }

    public function test_department_entity_minimal_construction(): void
    {
        $department = new Department(tenantId: 2, name: new Name('Finance'));

        $this->assertNull($department->getId());
        $this->assertNull($department->getCode());
        $this->assertNull($department->getDescription());
        $this->assertNull($department->getManagerId());
        $this->assertNull($department->getParentId());
        $this->assertSame(0, $department->getLft());
        $this->assertSame(0, $department->getRgt());
        $this->assertTrue($department->isActive());
        $this->assertInstanceOf(Metadata::class, $department->getMetadata());
    }

    public function test_department_entity_has_timestamps(): void
    {
        $dept = $this->createTestDepartment();
        $this->assertInstanceOf(\DateTimeInterface::class, $dept->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $dept->getUpdatedAt());
    }

    public function test_department_entity_update_details(): void
    {
        $department = $this->createTestDepartment();

        $department->updateDetails(
            new Name('Operations'), new Code('OPS-001'), 'Operations dept',
            10, null, new Metadata(['region' => 'EMEA']), false
        );

        $this->assertSame('Operations', $department->getName()->value());
        $this->assertSame('OPS-001', $department->getCode()->value());
        $this->assertSame('Operations dept', $department->getDescription());
        $this->assertSame(10, $department->getManagerId());
        $this->assertNull($department->getParentId());
        $this->assertFalse($department->isActive());
    }

    public function test_department_entity_update_clears_nullable_fields(): void
    {
        $department = new Department(
            tenantId: 1, name: new Name('Dept'),
            code: new Code('D-001'), description: 'Some desc',
            managerId: 3, parentId: 2,
        );

        $department->updateDetails(new Name('Dept'), null, null, null, null, null, true);

        $this->assertNull($department->getCode());
        $this->assertNull($department->getDescription());
        $this->assertNull($department->getManagerId());
        $this->assertNull($department->getParentId());
    }

    public function test_department_entity_activate(): void
    {
        $department = new Department(tenantId: 1, name: new Name('Dept'), isActive: false);
        $department->activate();
        $this->assertTrue($department->isActive());
    }

    public function test_department_entity_deactivate(): void
    {
        $department = $this->createTestDepartment();
        $department->deactivate();
        $this->assertFalse($department->isActive());
    }

    public function test_department_entity_update_changes_updated_at(): void
    {
        $before = new \DateTimeImmutable('2020-01-01');
        $dept = new Department(tenantId: 1, name: new Name('Dept'), updatedAt: $before);

        $dept->updateDetails(new Name('Dept2'), null, null, null, null, null, true);

        $this->assertGreaterThan($before->getTimestamp(), $dept->getUpdatedAt()->getTimestamp());
    }

    // ── Position Entity ───────────────────────────────────────────────────────

    public function test_position_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Position::class));
    }

    public function test_position_entity_can_be_constructed_full(): void
    {
        $position = new Position(
            tenantId: 1, name: new Name('Software Engineer'),
            code: new Code('SE-001'), description: 'Develops software',
            grade: 'L3', departmentId: 2, metadata: new Metadata(['level' => 3]), isActive: true,
        );

        $this->assertSame(1, $position->getTenantId());
        $this->assertSame('Software Engineer', $position->getName()->value());
        $this->assertSame('SE-001', $position->getCode()->value());
        $this->assertSame('Develops software', $position->getDescription());
        $this->assertSame('L3', $position->getGrade());
        $this->assertSame(2, $position->getDepartmentId());
        $this->assertTrue($position->isActive());
        $this->assertNull($position->getId());
    }

    public function test_position_entity_minimal_construction(): void
    {
        $position = new Position(tenantId: 3, name: new Name('Analyst'));

        $this->assertNull($position->getId());
        $this->assertSame(3, $position->getTenantId());
        $this->assertNull($position->getCode());
        $this->assertNull($position->getDescription());
        $this->assertNull($position->getGrade());
        $this->assertNull($position->getDepartmentId());
        $this->assertTrue($position->isActive());
        $this->assertInstanceOf(Metadata::class, $position->getMetadata());
    }

    public function test_position_entity_has_timestamps(): void
    {
        $position = $this->createTestPosition();
        $this->assertInstanceOf(\DateTimeInterface::class, $position->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $position->getUpdatedAt());
    }

    public function test_position_entity_update_details(): void
    {
        $position = $this->createTestPosition();

        $position->updateDetails(
            new Name('Principal Engineer'), new Code('PE-001'),
            'Leads engineering', 'L5', 3, new Metadata(['level' => 5]), false
        );

        $this->assertSame('Principal Engineer', $position->getName()->value());
        $this->assertSame('PE-001', $position->getCode()->value());
        $this->assertSame('Leads engineering', $position->getDescription());
        $this->assertSame('L5', $position->getGrade());
        $this->assertSame(3, $position->getDepartmentId());
        $this->assertFalse($position->isActive());
    }

    public function test_position_entity_update_clears_nullable_fields(): void
    {
        $position = new Position(
            tenantId: 1, name: new Name('Dev'),
            code: new Code('D-001'), description: 'desc',
            grade: 'L1', departmentId: 2,
        );

        $position->updateDetails(new Name('Dev'), null, null, null, null, null, true);

        $this->assertNull($position->getCode());
        $this->assertNull($position->getDescription());
        $this->assertNull($position->getGrade());
        $this->assertNull($position->getDepartmentId());
    }

    public function test_position_entity_activate(): void
    {
        $position = new Position(tenantId: 1, name: new Name('Dev'), isActive: false);
        $position->activate();
        $this->assertTrue($position->isActive());
    }

    public function test_position_entity_deactivate(): void
    {
        $position = $this->createTestPosition();
        $position->deactivate();
        $this->assertFalse($position->isActive());
    }

    // ── LeaveRequest Entity ───────────────────────────────────────────────────

    public function test_leave_request_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequest::class));
    }

    public function test_leave_request_entity_can_be_constructed_full(): void
    {
        $start = new \DateTimeImmutable('2024-07-01');
        $end   = new \DateTimeImmutable('2024-07-05');

        $lr = new LeaveRequest(
            tenantId: 1, employeeId: 10, leaveType: 'sick',
            startDate: $start, endDate: $end, reason: 'Illness',
            status: 'pending', approvedBy: null, approvedAt: null,
            notes: null, metadata: new Metadata(['source' => 'portal']),
        );

        $this->assertSame(1, $lr->getTenantId());
        $this->assertSame(10, $lr->getEmployeeId());
        $this->assertSame('sick', $lr->getLeaveType());
        $this->assertSame($start, $lr->getStartDate());
        $this->assertSame($end, $lr->getEndDate());
        $this->assertSame('Illness', $lr->getReason());
        $this->assertSame('pending', $lr->getStatus());
        $this->assertNull($lr->getApprovedBy());
        $this->assertNull($lr->getApprovedAt());
        $this->assertNull($lr->getNotes());
        $this->assertNull($lr->getId());
    }

    public function test_leave_request_entity_minimal_construction(): void
    {
        $lr = new LeaveRequest(
            tenantId: 2, employeeId: 5, leaveType: 'annual',
            startDate: new \DateTimeImmutable('2024-08-01'),
            endDate: new \DateTimeImmutable('2024-08-10'),
        );

        $this->assertNull($lr->getId());
        $this->assertSame(2, $lr->getTenantId());
        $this->assertNull($lr->getReason());
        $this->assertSame('pending', $lr->getStatus());
        $this->assertNull($lr->getApprovedBy());
        $this->assertNull($lr->getNotes());
        $this->assertInstanceOf(Metadata::class, $lr->getMetadata());
    }

    public function test_leave_request_entity_has_timestamps(): void
    {
        $lr = $this->createTestLeaveRequest();
        $this->assertInstanceOf(\DateTimeInterface::class, $lr->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $lr->getUpdatedAt());
    }

    public function test_leave_request_entity_approve(): void
    {
        $lr = $this->createTestLeaveRequest();
        $lr->approve(99, 'Looks good');
        $this->assertSame('approved', $lr->getStatus());
        $this->assertSame(99, $lr->getApprovedBy());
        $this->assertInstanceOf(\DateTimeInterface::class, $lr->getApprovedAt());
        $this->assertSame('Looks good', $lr->getNotes());
    }

    public function test_leave_request_entity_reject(): void
    {
        $lr = $this->createTestLeaveRequest();
        $lr->reject(88, 'Busy period');
        $this->assertSame('rejected', $lr->getStatus());
        $this->assertSame(88, $lr->getApprovedBy());
        $this->assertSame('Busy period', $lr->getNotes());
    }

    public function test_leave_request_entity_cancel(): void
    {
        $lr = $this->createTestLeaveRequest();
        $lr->cancel();
        $this->assertSame('cancelled', $lr->getStatus());
    }

    public function test_leave_request_entity_is_pending(): void
    {
        $lr = $this->createTestLeaveRequest();
        $this->assertTrue($lr->isPending());
        $lr->approve(1, null);
        $this->assertFalse($lr->isPending());
    }

    public function test_leave_request_entity_is_approved(): void
    {
        $lr = $this->createTestLeaveRequest();
        $this->assertFalse($lr->isApproved());
        $lr->approve(1, null);
        $this->assertTrue($lr->isApproved());
    }

    // ── Domain Events ─────────────────────────────────────────────────────────

    public function test_employee_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(EmployeeCreated::class));
    }

    public function test_employee_created_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(EmployeeCreated::class, BaseEvent::class));
    }

    public function test_employee_created_event_holds_employee(): void
    {
        $employee = $this->createTestEmployee(1, 2);
        $event    = new EmployeeCreated($employee);
        $this->assertSame($employee, $event->employee);
        $this->assertSame(2, $event->tenantId);
    }

    public function test_employee_deleted_event_class_exists(): void
    {
        $this->assertTrue(class_exists(EmployeeDeleted::class));
    }

    public function test_employee_deleted_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(EmployeeDeleted::class, BaseEvent::class));
    }

    public function test_employee_deleted_event_holds_id_and_tenant_id(): void
    {
        $event = new EmployeeDeleted(42, 3);
        $this->assertSame(42, $event->employeeId);
        $this->assertSame(3, $event->tenantId);
    }

    public function test_employee_updated_event_class_exists(): void
    {
        $this->assertTrue(class_exists(EmployeeUpdated::class));
    }

    public function test_employee_updated_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(EmployeeUpdated::class, BaseEvent::class));
    }

    public function test_leave_request_approved_event_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequestApproved::class));
    }

    public function test_leave_request_approved_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(LeaveRequestApproved::class, BaseEvent::class));
    }

    public function test_leave_request_approved_event_holds_leave_request(): void
    {
        $lr    = $this->createTestLeaveRequest(5, 1, 10);
        $event = new LeaveRequestApproved($lr);
        $this->assertSame($lr, $event->leaveRequest);
        $this->assertSame(1, $event->tenantId);
    }

    public function test_leave_request_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequestCreated::class));
    }

    public function test_leave_request_created_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(LeaveRequestCreated::class, BaseEvent::class));
    }

    public function test_leave_request_deleted_event_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequestDeleted::class));
    }

    public function test_leave_request_deleted_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(LeaveRequestDeleted::class, BaseEvent::class));
    }

    public function test_department_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(DepartmentCreated::class));
    }

    public function test_department_created_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(DepartmentCreated::class, BaseEvent::class));
    }

    public function test_department_deleted_event_class_exists(): void
    {
        $this->assertTrue(class_exists(DepartmentDeleted::class));
    }

    public function test_department_deleted_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(DepartmentDeleted::class, BaseEvent::class));
    }

    public function test_department_updated_event_class_exists(): void
    {
        $this->assertTrue(class_exists(DepartmentUpdated::class));
    }

    public function test_department_updated_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(DepartmentUpdated::class, BaseEvent::class));
    }

    public function test_position_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(PositionCreated::class));
    }

    public function test_position_created_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(PositionCreated::class, BaseEvent::class));
    }

    public function test_position_deleted_event_class_exists(): void
    {
        $this->assertTrue(class_exists(PositionDeleted::class));
    }

    public function test_position_deleted_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(PositionDeleted::class, BaseEvent::class));
    }

    public function test_position_updated_event_class_exists(): void
    {
        $this->assertTrue(class_exists(PositionUpdated::class));
    }

    public function test_position_updated_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(PositionUpdated::class, BaseEvent::class));
    }

    // ── Repository Interfaces ─────────────────────────────────────────────────

    public function test_employee_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(EmployeeRepositoryInterface::class));
    }

    public function test_employee_repository_interface_extends_repository_interface(): void
    {
        $this->assertTrue(is_subclass_of(EmployeeRepositoryInterface::class, RepositoryInterface::class));
    }

    public function test_employee_repository_interface_declares_save(): void
    {
        $this->assertTrue(method_exists(EmployeeRepositoryInterface::class, 'save'));
    }

    public function test_employee_repository_interface_declares_get_by_department(): void
    {
        $this->assertTrue(method_exists(EmployeeRepositoryInterface::class, 'getByDepartment'));
    }

    public function test_employee_repository_interface_declares_get_by_manager(): void
    {
        $this->assertTrue(method_exists(EmployeeRepositoryInterface::class, 'getByManager'));
    }

    public function test_department_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DepartmentRepositoryInterface::class));
    }

    public function test_department_repository_interface_extends_repository_interface(): void
    {
        $this->assertTrue(is_subclass_of(DepartmentRepositoryInterface::class, RepositoryInterface::class));
    }

    public function test_department_repository_interface_declares_save(): void
    {
        $this->assertTrue(method_exists(DepartmentRepositoryInterface::class, 'save'));
    }

    public function test_department_repository_interface_declares_get_tree(): void
    {
        $this->assertTrue(method_exists(DepartmentRepositoryInterface::class, 'getTree'));
    }

    public function test_department_repository_interface_declares_get_by_parent(): void
    {
        $this->assertTrue(method_exists(DepartmentRepositoryInterface::class, 'getByParent'));
    }

    public function test_position_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(PositionRepositoryInterface::class));
    }

    public function test_position_repository_interface_extends_repository_interface(): void
    {
        $this->assertTrue(is_subclass_of(PositionRepositoryInterface::class, RepositoryInterface::class));
    }

    public function test_position_repository_interface_declares_save(): void
    {
        $this->assertTrue(method_exists(PositionRepositoryInterface::class, 'save'));
    }

    public function test_position_repository_interface_declares_get_by_department(): void
    {
        $this->assertTrue(method_exists(PositionRepositoryInterface::class, 'getByDepartment'));
    }

    public function test_leave_request_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(LeaveRequestRepositoryInterface::class));
    }

    public function test_leave_request_repository_interface_extends_repository_interface(): void
    {
        $this->assertTrue(is_subclass_of(LeaveRequestRepositoryInterface::class, RepositoryInterface::class));
    }

    public function test_leave_request_repository_interface_declares_save(): void
    {
        $this->assertTrue(method_exists(LeaveRequestRepositoryInterface::class, 'save'));
    }

    public function test_leave_request_repository_interface_declares_get_by_employee(): void
    {
        $this->assertTrue(method_exists(LeaveRequestRepositoryInterface::class, 'getByEmployee'));
    }

    public function test_leave_request_repository_interface_declares_get_pending_by_employee(): void
    {
        $this->assertTrue(method_exists(LeaveRequestRepositoryInterface::class, 'getPendingByEmployee'));
    }

    // ── Service Interfaces ────────────────────────────────────────────────────

    public function test_find_employee_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindEmployeeServiceInterface::class));
    }

    public function test_find_employee_service_interface_extends_read_service_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindEmployeeServiceInterface::class, ReadServiceInterface::class));
    }

    public function test_find_employee_service_interface_declares_get_by_department(): void
    {
        $this->assertTrue(method_exists(FindEmployeeServiceInterface::class, 'getByDepartment'));
    }

    public function test_find_employee_service_interface_declares_get_by_manager(): void
    {
        $this->assertTrue(method_exists(FindEmployeeServiceInterface::class, 'getByManager'));
    }

    public function test_create_employee_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreateEmployeeServiceInterface::class));
    }

    public function test_create_employee_service_interface_extends_write_service_interface(): void
    {
        $this->assertTrue(is_subclass_of(CreateEmployeeServiceInterface::class, WriteServiceInterface::class));
    }

    public function test_update_employee_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateEmployeeServiceInterface::class));
    }

    public function test_delete_employee_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeleteEmployeeServiceInterface::class));
    }

    public function test_find_department_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindDepartmentServiceInterface::class));
    }

    public function test_find_department_service_interface_extends_read_service_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindDepartmentServiceInterface::class, ReadServiceInterface::class));
    }

    public function test_create_department_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreateDepartmentServiceInterface::class));
    }

    public function test_update_department_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateDepartmentServiceInterface::class));
    }

    public function test_delete_department_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeleteDepartmentServiceInterface::class));
    }

    public function test_find_position_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindPositionServiceInterface::class));
    }

    public function test_find_position_service_interface_extends_read_service_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindPositionServiceInterface::class, ReadServiceInterface::class));
    }

    public function test_create_position_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreatePositionServiceInterface::class));
    }

    public function test_update_position_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdatePositionServiceInterface::class));
    }

    public function test_delete_position_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeletePositionServiceInterface::class));
    }

    public function test_find_leave_request_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindLeaveRequestServiceInterface::class));
    }

    public function test_find_leave_request_service_interface_extends_read_service_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindLeaveRequestServiceInterface::class, ReadServiceInterface::class));
    }

    public function test_find_leave_request_service_interface_declares_get_by_employee(): void
    {
        $this->assertTrue(method_exists(FindLeaveRequestServiceInterface::class, 'getByEmployee'));
    }

    public function test_find_leave_request_service_interface_declares_get_pending_by_employee(): void
    {
        $this->assertTrue(method_exists(FindLeaveRequestServiceInterface::class, 'getPendingByEmployee'));
    }

    public function test_create_leave_request_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CreateLeaveRequestServiceInterface::class));
    }

    public function test_update_leave_request_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateLeaveRequestServiceInterface::class));
    }

    public function test_delete_leave_request_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(DeleteLeaveRequestServiceInterface::class));
    }

    public function test_approve_leave_request_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(ApproveLeaveRequestServiceInterface::class));
    }

    public function test_reject_leave_request_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(RejectLeaveRequestServiceInterface::class));
    }

    // ── DTOs ──────────────────────────────────────────────────────────────────

    public function test_employee_data_class_exists(): void
    {
        $this->assertTrue(class_exists(EmployeeData::class));
    }

    public function test_employee_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(EmployeeData::class, BaseDto::class));
    }

    public function test_employee_data_from_array(): void
    {
        $dto = EmployeeData::fromArray([
            'tenant_id' => 1, 'first_name' => 'Alice', 'last_name' => 'Wonderland',
            'email' => 'alice@example.com', 'employee_number' => 'EMP-050',
            'hire_date' => '2023-01-15', 'employment_type' => 'full_time',
            'status' => 'active', 'currency' => 'USD', 'is_active' => true,
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame('Alice', $dto->first_name);
        $this->assertSame('alice@example.com', $dto->email);
        $this->assertSame('EMP-050', $dto->employee_number);
        $this->assertTrue($dto->is_active);
    }

    public function test_employee_data_to_array(): void
    {
        $dto = EmployeeData::fromArray([
            'tenant_id' => 2, 'first_name' => 'Bob', 'last_name' => 'Builder',
            'email' => 'bob@example.com', 'employee_number' => 'EMP-002',
            'hire_date' => '2023-02-01', 'employment_type' => 'contract',
        ]);

        $arr = $dto->toArray();
        $this->assertArrayHasKey('tenant_id', $arr);
        $this->assertArrayHasKey('first_name', $arr);
        $this->assertArrayHasKey('email', $arr);
    }

    public function test_update_employee_data_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateEmployeeData::class));
    }

    public function test_update_employee_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(UpdateEmployeeData::class, BaseDto::class));
    }

    public function test_update_employee_data_is_provided_tracks_supplied_keys(): void
    {
        $dto = UpdateEmployeeData::fromArray(['id' => 1, 'first_name' => 'Charlie', 'status' => 'on_leave']);

        $this->assertTrue($dto->isProvided('first_name'));
        $this->assertTrue($dto->isProvided('status'));
        $this->assertFalse($dto->isProvided('last_name'));
        $this->assertFalse($dto->isProvided('email'));
        $this->assertFalse($dto->isProvided('salary'));
    }

    public function test_update_employee_data_to_array_only_emits_provided_keys(): void
    {
        $dto = UpdateEmployeeData::fromArray(['id' => 5, 'first_name' => 'Dave', 'is_active' => false]);
        $arr = $dto->toArray();

        $this->assertArrayHasKey('first_name', $arr);
        $this->assertArrayHasKey('is_active', $arr);
        $this->assertArrayNotHasKey('last_name', $arr);
        $this->assertArrayNotHasKey('email', $arr);
    }

    public function test_department_data_class_exists(): void
    {
        $this->assertTrue(class_exists(DepartmentData::class));
    }

    public function test_department_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(DepartmentData::class, BaseDto::class));
    }

    public function test_department_data_from_array(): void
    {
        $dto = DepartmentData::fromArray([
            'tenant_id' => 1, 'name' => 'Marketing',
            'code' => 'MKT', 'description' => 'Marketing Dept', 'is_active' => true,
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame('Marketing', $dto->name);
        $this->assertSame('MKT', $dto->code);
        $this->assertTrue($dto->is_active);
    }

    public function test_update_department_data_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateDepartmentData::class));
    }

    public function test_update_department_data_is_provided_tracks_supplied_keys(): void
    {
        $dto = UpdateDepartmentData::fromArray(['id' => 1, 'name' => 'Sales', 'is_active' => false]);

        $this->assertTrue($dto->isProvided('name'));
        $this->assertTrue($dto->isProvided('is_active'));
        $this->assertFalse($dto->isProvided('code'));
        $this->assertFalse($dto->isProvided('description'));
    }

    public function test_update_department_data_to_array_only_emits_provided_keys(): void
    {
        $dto = UpdateDepartmentData::fromArray(['id' => 2, 'name' => 'Legal']);
        $arr = $dto->toArray();

        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayNotHasKey('code', $arr);
    }

    public function test_position_data_class_exists(): void
    {
        $this->assertTrue(class_exists(PositionData::class));
    }

    public function test_position_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(PositionData::class, BaseDto::class));
    }

    public function test_position_data_from_array(): void
    {
        $dto = PositionData::fromArray([
            'tenant_id' => 1, 'name' => 'Manager',
            'code' => 'MGR', 'grade' => 'M1', 'department_id' => 3, 'is_active' => true,
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame('Manager', $dto->name);
        $this->assertSame('M1', $dto->grade);
        $this->assertSame(3, $dto->department_id);
    }

    public function test_update_position_data_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdatePositionData::class));
    }

    public function test_update_position_data_is_provided_tracks_supplied_keys(): void
    {
        $dto = UpdatePositionData::fromArray(['id' => 1, 'name' => 'Director', 'grade' => 'D1']);

        $this->assertTrue($dto->isProvided('name'));
        $this->assertTrue($dto->isProvided('grade'));
        $this->assertFalse($dto->isProvided('code'));
        $this->assertFalse($dto->isProvided('department_id'));
    }

    public function test_update_position_data_to_array_only_emits_provided_keys(): void
    {
        $dto = UpdatePositionData::fromArray(['id' => 3, 'grade' => 'L2']);
        $arr = $dto->toArray();

        $this->assertArrayHasKey('grade', $arr);
        $this->assertArrayNotHasKey('name', $arr);
    }

    public function test_leave_request_data_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequestData::class));
    }

    public function test_leave_request_data_extends_base_dto(): void
    {
        $this->assertTrue(is_subclass_of(LeaveRequestData::class, BaseDto::class));
    }

    public function test_leave_request_data_from_array(): void
    {
        $dto = LeaveRequestData::fromArray([
            'tenant_id' => 1, 'employee_id' => 10, 'leave_type' => 'annual',
            'start_date' => '2024-09-01', 'end_date' => '2024-09-05',
            'reason' => 'Vacation', 'status' => 'pending',
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame(10, $dto->employee_id);
        $this->assertSame('annual', $dto->leave_type);
        $this->assertSame('Vacation', $dto->reason);
    }

    public function test_update_leave_request_data_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateLeaveRequestData::class));
    }

    public function test_update_leave_request_data_is_provided_tracks_supplied_keys(): void
    {
        $dto = UpdateLeaveRequestData::fromArray(['id' => 1, 'leave_type' => 'sick', 'reason' => 'Flu']);

        $this->assertTrue($dto->isProvided('leave_type'));
        $this->assertTrue($dto->isProvided('reason'));
        $this->assertFalse($dto->isProvided('start_date'));
        $this->assertFalse($dto->isProvided('status'));
    }

    public function test_update_leave_request_data_to_array_only_emits_provided_keys(): void
    {
        $dto = UpdateLeaveRequestData::fromArray(['id' => 4, 'status' => 'approved']);
        $arr = $dto->toArray();

        $this->assertArrayHasKey('status', $arr);
        $this->assertArrayNotHasKey('leave_type', $arr);
    }

    // ── FindEmployeeService ───────────────────────────────────────────────────

    public function test_find_employee_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindEmployeeService::class));
    }

    public function test_find_employee_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(FindEmployeeService::class, BaseService::class));
    }

    public function test_find_employee_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindEmployeeService::class, FindEmployeeServiceInterface::class));
    }

    public function test_find_employee_service_find_delegates_to_repository(): void
    {
        $employee = $this->createTestEmployee(5);
        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->expects($this->once())->method('find')->with(5)->willReturn($employee);

        $service = new FindEmployeeService($repo);
        $this->assertSame($employee, $service->find(5));
    }

    public function test_find_employee_service_find_returns_null_when_missing(): void
    {
        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new FindEmployeeService($repo);
        $this->assertNull($service->find(9999));
    }

    public function test_find_employee_service_get_by_department_delegates(): void
    {
        $employees = [$this->createTestEmployee(1), $this->createTestEmployee(2)];
        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->expects($this->once())->method('getByDepartment')->with(3)->willReturn($employees);

        $service = new FindEmployeeService($repo);
        $this->assertSame($employees, $service->getByDepartment(3));
    }

    public function test_find_employee_service_get_by_manager_delegates(): void
    {
        $employees = [$this->createTestEmployee(1)];
        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->expects($this->once())->method('getByManager')->with(7)->willReturn($employees);

        $service = new FindEmployeeService($repo);
        $this->assertSame($employees, $service->getByManager(7));
    }

    public function test_find_employee_service_handle_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $repo    = $this->createMock(EmployeeRepositoryInterface::class);
        $service = new FindEmployeeService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, []);
    }

    // ── CreateEmployeeService ─────────────────────────────────────────────────

    public function test_create_employee_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreateEmployeeService::class));
    }

    public function test_create_employee_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(CreateEmployeeService::class, BaseService::class));
    }

    public function test_create_employee_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(CreateEmployeeService::class, CreateEmployeeServiceInterface::class));
    }

    public function test_create_employee_service_dispatches_created_event(): void
    {
        $rc = new \ReflectionClass(CreateEmployeeService::class);
        $this->assertStringContainsString('EmployeeCreated', file_get_contents($rc->getFileName()));
    }

    public function test_create_employee_service_uses_repository_save(): void
    {
        $rc = new \ReflectionClass(CreateEmployeeService::class);
        $this->assertStringContainsString('->save(', file_get_contents($rc->getFileName()));
    }

    // ── UpdateEmployeeService ─────────────────────────────────────────────────

    public function test_update_employee_service_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateEmployeeService::class));
    }

    public function test_update_employee_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(UpdateEmployeeService::class, UpdateEmployeeServiceInterface::class));
    }

    public function test_update_employee_service_uses_update_dto(): void
    {
        $rc = new \ReflectionClass(UpdateEmployeeService::class);
        $this->assertStringContainsString('UpdateEmployeeData::fromArray', file_get_contents($rc->getFileName()));
    }

    public function test_update_employee_service_uses_is_provided(): void
    {
        $rc = new \ReflectionClass(UpdateEmployeeService::class);
        $this->assertStringContainsString('isProvided(', file_get_contents($rc->getFileName()));
    }

    public function test_update_employee_service_throws_when_not_found(): void
    {
        $this->expectException(EmployeeNotFoundException::class);
        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new UpdateEmployeeService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 999, 'first_name' => 'John']);
    }

    public function test_update_employee_service_dispatches_updated_event(): void
    {
        $rc = new \ReflectionClass(UpdateEmployeeService::class);
        $this->assertStringContainsString('EmployeeUpdated', file_get_contents($rc->getFileName()));
    }

    // ── DeleteEmployeeService ─────────────────────────────────────────────────

    public function test_delete_employee_service_class_exists(): void
    {
        $this->assertTrue(class_exists(DeleteEmployeeService::class));
    }

    public function test_delete_employee_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(DeleteEmployeeService::class, DeleteEmployeeServiceInterface::class));
    }

    public function test_delete_employee_service_throws_when_not_found(): void
    {
        $this->expectException(EmployeeNotFoundException::class);
        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new DeleteEmployeeService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 404]);
    }

    public function test_delete_employee_service_dispatches_deleted_event(): void
    {
        $rc = new \ReflectionClass(DeleteEmployeeService::class);
        $this->assertStringContainsString('EmployeeDeleted', file_get_contents($rc->getFileName()));
    }

    // ── FindDepartmentService ─────────────────────────────────────────────────

    public function test_find_department_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindDepartmentService::class));
    }

    public function test_find_department_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(FindDepartmentService::class, BaseService::class));
    }

    public function test_find_department_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindDepartmentService::class, FindDepartmentServiceInterface::class));
    }

    public function test_find_department_service_find_delegates_to_repository(): void
    {
        $dept = $this->createTestDepartment(3);
        $repo = $this->createMock(DepartmentRepositoryInterface::class);
        $repo->expects($this->once())->method('find')->with(3)->willReturn($dept);

        $service = new FindDepartmentService($repo);
        $this->assertSame($dept, $service->find(3));
    }

    public function test_find_department_service_handle_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $repo    = $this->createMock(DepartmentRepositoryInterface::class);
        $service = new FindDepartmentService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, []);
    }

    // ── CreateDepartmentService ───────────────────────────────────────────────

    public function test_create_department_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreateDepartmentService::class));
    }

    public function test_create_department_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(CreateDepartmentService::class, CreateDepartmentServiceInterface::class));
    }

    public function test_create_department_service_dispatches_created_event(): void
    {
        $rc = new \ReflectionClass(CreateDepartmentService::class);
        $this->assertStringContainsString('DepartmentCreated', file_get_contents($rc->getFileName()));
    }

    // ── UpdateDepartmentService ───────────────────────────────────────────────

    public function test_update_department_service_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateDepartmentService::class));
    }

    public function test_update_department_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(UpdateDepartmentService::class, UpdateDepartmentServiceInterface::class));
    }

    public function test_update_department_service_uses_is_provided(): void
    {
        $rc = new \ReflectionClass(UpdateDepartmentService::class);
        $this->assertStringContainsString('isProvided(', file_get_contents($rc->getFileName()));
    }

    public function test_update_department_service_throws_when_not_found(): void
    {
        $this->expectException(DepartmentNotFoundException::class);
        $repo = $this->createMock(DepartmentRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new UpdateDepartmentService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 999, 'name' => 'Engineering']);
    }

    // ── DeleteDepartmentService ───────────────────────────────────────────────

    public function test_delete_department_service_class_exists(): void
    {
        $this->assertTrue(class_exists(DeleteDepartmentService::class));
    }

    public function test_delete_department_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(DeleteDepartmentService::class, DeleteDepartmentServiceInterface::class));
    }

    public function test_delete_department_service_throws_when_not_found(): void
    {
        $this->expectException(DepartmentNotFoundException::class);
        $repo = $this->createMock(DepartmentRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new DeleteDepartmentService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 404]);
    }

    // ── FindPositionService ───────────────────────────────────────────────────

    public function test_find_position_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindPositionService::class));
    }

    public function test_find_position_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindPositionService::class, FindPositionServiceInterface::class));
    }

    public function test_find_position_service_find_delegates_to_repository(): void
    {
        $position = $this->createTestPosition(4);
        $repo = $this->createMock(PositionRepositoryInterface::class);
        $repo->expects($this->once())->method('find')->with(4)->willReturn($position);

        $service = new FindPositionService($repo);
        $this->assertSame($position, $service->find(4));
    }

    public function test_find_position_service_handle_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $repo    = $this->createMock(PositionRepositoryInterface::class);
        $service = new FindPositionService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, []);
    }

    // ── CreatePositionService ─────────────────────────────────────────────────

    public function test_create_position_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreatePositionService::class));
    }

    public function test_create_position_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(CreatePositionService::class, CreatePositionServiceInterface::class));
    }

    public function test_create_position_service_dispatches_created_event(): void
    {
        $rc = new \ReflectionClass(CreatePositionService::class);
        $this->assertStringContainsString('PositionCreated', file_get_contents($rc->getFileName()));
    }

    // ── UpdatePositionService ─────────────────────────────────────────────────

    public function test_update_position_service_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdatePositionService::class));
    }

    public function test_update_position_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(UpdatePositionService::class, UpdatePositionServiceInterface::class));
    }

    public function test_update_position_service_uses_is_provided(): void
    {
        $rc = new \ReflectionClass(UpdatePositionService::class);
        $this->assertStringContainsString('isProvided(', file_get_contents($rc->getFileName()));
    }

    public function test_update_position_service_throws_when_not_found(): void
    {
        $this->expectException(PositionNotFoundException::class);
        $repo = $this->createMock(PositionRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new UpdatePositionService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 999, 'name' => 'VP']);
    }

    // ── DeletePositionService ─────────────────────────────────────────────────

    public function test_delete_position_service_class_exists(): void
    {
        $this->assertTrue(class_exists(DeletePositionService::class));
    }

    public function test_delete_position_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(DeletePositionService::class, DeletePositionServiceInterface::class));
    }

    public function test_delete_position_service_throws_when_not_found(): void
    {
        $this->expectException(PositionNotFoundException::class);
        $repo = $this->createMock(PositionRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new DeletePositionService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 404]);
    }

    // ── FindLeaveRequestService ───────────────────────────────────────────────

    public function test_find_leave_request_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindLeaveRequestService::class));
    }

    public function test_find_leave_request_service_extends_base_service(): void
    {
        $this->assertTrue(is_subclass_of(FindLeaveRequestService::class, BaseService::class));
    }

    public function test_find_leave_request_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(FindLeaveRequestService::class, FindLeaveRequestServiceInterface::class));
    }

    public function test_find_leave_request_service_find_delegates_to_repository(): void
    {
        $lr = $this->createTestLeaveRequest(7);
        $repo = $this->createMock(LeaveRequestRepositoryInterface::class);
        $repo->expects($this->once())->method('find')->with(7)->willReturn($lr);

        $service = new FindLeaveRequestService($repo);
        $this->assertSame($lr, $service->find(7));
    }

    public function test_find_leave_request_service_get_by_employee_delegates(): void
    {
        $lrs = [$this->createTestLeaveRequest(1)];
        $repo = $this->createMock(LeaveRequestRepositoryInterface::class);
        $repo->expects($this->once())->method('getByEmployee')->with(5)->willReturn($lrs);

        $service = new FindLeaveRequestService($repo);
        $this->assertSame($lrs, $service->getByEmployee(5));
    }

    public function test_find_leave_request_service_get_pending_by_employee_delegates(): void
    {
        $lrs = [$this->createTestLeaveRequest(1)];
        $repo = $this->createMock(LeaveRequestRepositoryInterface::class);
        $repo->expects($this->once())->method('getPendingByEmployee')->with(5)->willReturn($lrs);

        $service = new FindLeaveRequestService($repo);
        $this->assertSame($lrs, $service->getPendingByEmployee(5));
    }

    public function test_find_leave_request_service_handle_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $repo    = $this->createMock(LeaveRequestRepositoryInterface::class);
        $service = new FindLeaveRequestService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, []);
    }

    // ── CreateLeaveRequestService ─────────────────────────────────────────────

    public function test_create_leave_request_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreateLeaveRequestService::class));
    }

    public function test_create_leave_request_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(CreateLeaveRequestService::class, CreateLeaveRequestServiceInterface::class));
    }

    public function test_create_leave_request_service_dispatches_created_event(): void
    {
        $rc = new \ReflectionClass(CreateLeaveRequestService::class);
        $this->assertStringContainsString('LeaveRequestCreated', file_get_contents($rc->getFileName()));
    }

    // ── UpdateLeaveRequestService ─────────────────────────────────────────────

    public function test_update_leave_request_service_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateLeaveRequestService::class));
    }

    public function test_update_leave_request_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(UpdateLeaveRequestService::class, UpdateLeaveRequestServiceInterface::class));
    }

    public function test_update_leave_request_service_throws_when_not_found(): void
    {
        $this->expectException(LeaveRequestNotFoundException::class);
        $repo = $this->createMock(LeaveRequestRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new UpdateLeaveRequestService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 999, 'leave_type' => 'sick']);
    }

    // ── DeleteLeaveRequestService ─────────────────────────────────────────────

    public function test_delete_leave_request_service_class_exists(): void
    {
        $this->assertTrue(class_exists(DeleteLeaveRequestService::class));
    }

    public function test_delete_leave_request_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(DeleteLeaveRequestService::class, DeleteLeaveRequestServiceInterface::class));
    }

    public function test_delete_leave_request_service_throws_when_not_found(): void
    {
        $this->expectException(LeaveRequestNotFoundException::class);
        $repo = $this->createMock(LeaveRequestRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new DeleteLeaveRequestService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 404]);
    }

    // ── ApproveLeaveRequestService ────────────────────────────────────────────

    public function test_approve_leave_request_service_class_exists(): void
    {
        $this->assertTrue(class_exists(ApproveLeaveRequestService::class));
    }

    public function test_approve_leave_request_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(ApproveLeaveRequestService::class, ApproveLeaveRequestServiceInterface::class));
    }

    public function test_approve_leave_request_service_throws_when_not_found(): void
    {
        $this->expectException(LeaveRequestNotFoundException::class);
        $repo = $this->createMock(LeaveRequestRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new ApproveLeaveRequestService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 404, 'approved_by' => 1, 'notes' => null]);
    }

    public function test_approve_leave_request_service_dispatches_approved_event(): void
    {
        $rc = new \ReflectionClass(ApproveLeaveRequestService::class);
        $this->assertStringContainsString('LeaveRequestApproved', file_get_contents($rc->getFileName()));
    }

    // ── RejectLeaveRequestService ─────────────────────────────────────────────

    public function test_reject_leave_request_service_class_exists(): void
    {
        $this->assertTrue(class_exists(RejectLeaveRequestService::class));
    }

    public function test_reject_leave_request_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(RejectLeaveRequestService::class, RejectLeaveRequestServiceInterface::class));
    }

    public function test_reject_leave_request_service_throws_when_not_found(): void
    {
        $this->expectException(LeaveRequestNotFoundException::class);
        $repo = $this->createMock(LeaveRequestRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new RejectLeaveRequestService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 404, 'approved_by' => 1, 'notes' => null]);
    }

    // ── HTTP Controllers ──────────────────────────────────────────────────────

    public function test_employee_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(EmployeeController::class));
    }

    public function test_employee_controller_extends_authorized_controller(): void
    {
        $this->assertTrue(is_subclass_of(EmployeeController::class, \Modules\Core\Infrastructure\Http\Controllers\AuthorizedController::class));
    }

    public function test_employee_controller_injects_find_service(): void
    {
        $rc = new \ReflectionClass(EmployeeController::class);
        $this->assertStringContainsString('FindEmployeeServiceInterface', file_get_contents($rc->getFileName()));
    }

    public function test_employee_controller_injects_create_service(): void
    {
        $rc = new \ReflectionClass(EmployeeController::class);
        $this->assertStringContainsString('CreateEmployeeServiceInterface', file_get_contents($rc->getFileName()));
    }

    public function test_employee_controller_injects_update_service(): void
    {
        $rc = new \ReflectionClass(EmployeeController::class);
        $this->assertStringContainsString('UpdateEmployeeServiceInterface', file_get_contents($rc->getFileName()));
    }

    public function test_employee_controller_injects_delete_service(): void
    {
        $rc = new \ReflectionClass(EmployeeController::class);
        $this->assertStringContainsString('DeleteEmployeeServiceInterface', file_get_contents($rc->getFileName()));
    }

    public function test_employee_controller_has_crud_methods(): void
    {
        $this->assertTrue(method_exists(EmployeeController::class, 'index'));
        $this->assertTrue(method_exists(EmployeeController::class, 'store'));
        $this->assertTrue(method_exists(EmployeeController::class, 'show'));
        $this->assertTrue(method_exists(EmployeeController::class, 'update'));
        $this->assertTrue(method_exists(EmployeeController::class, 'destroy'));
    }

    public function test_employee_controller_has_by_department_method(): void
    {
        $this->assertTrue(method_exists(EmployeeController::class, 'byDepartment'));
    }

    public function test_leave_request_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequestController::class));
    }

    public function test_leave_request_controller_extends_authorized_controller(): void
    {
        $this->assertTrue(is_subclass_of(LeaveRequestController::class, \Modules\Core\Infrastructure\Http\Controllers\AuthorizedController::class));
    }

    public function test_leave_request_controller_injects_approve_service(): void
    {
        $rc = new \ReflectionClass(LeaveRequestController::class);
        $this->assertStringContainsString('ApproveLeaveRequestServiceInterface', file_get_contents($rc->getFileName()));
    }

    public function test_leave_request_controller_injects_reject_service(): void
    {
        $rc = new \ReflectionClass(LeaveRequestController::class);
        $this->assertStringContainsString('RejectLeaveRequestServiceInterface', file_get_contents($rc->getFileName()));
    }

    public function test_leave_request_controller_has_crud_methods(): void
    {
        $this->assertTrue(method_exists(LeaveRequestController::class, 'index'));
        $this->assertTrue(method_exists(LeaveRequestController::class, 'store'));
        $this->assertTrue(method_exists(LeaveRequestController::class, 'show'));
        $this->assertTrue(method_exists(LeaveRequestController::class, 'update'));
        $this->assertTrue(method_exists(LeaveRequestController::class, 'destroy'));
    }

    public function test_leave_request_controller_has_approve_method(): void
    {
        $this->assertTrue(method_exists(LeaveRequestController::class, 'approve'));
    }

    public function test_leave_request_controller_has_reject_method(): void
    {
        $this->assertTrue(method_exists(LeaveRequestController::class, 'reject'));
    }

    public function test_leave_request_controller_has_by_employee_method(): void
    {
        $this->assertTrue(method_exists(LeaveRequestController::class, 'byEmployee'));
    }

    public function test_department_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(DepartmentController::class));
    }

    public function test_department_controller_extends_authorized_controller(): void
    {
        $this->assertTrue(is_subclass_of(DepartmentController::class, \Modules\Core\Infrastructure\Http\Controllers\AuthorizedController::class));
    }

    // ── HTTP Requests ─────────────────────────────────────────────────────────

    public function test_store_employee_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreEmployeeRequest::class));
    }

    public function test_store_employee_request_has_rules(): void
    {
        $req = new StoreEmployeeRequest;
        $rules = $req->rules();
        $this->assertArrayHasKey('tenant_id', $rules);
        $this->assertArrayHasKey('first_name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('employee_number', $rules);
    }

    public function test_update_employee_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateEmployeeRequest::class));
    }

    public function test_update_employee_request_uses_sometimes_required(): void
    {
        $req = new UpdateEmployeeRequest;
        $rules = $req->rules();
        $this->assertStringContainsString('sometimes', $rules['first_name']);
    }

    public function test_store_department_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreDepartmentRequest::class));
    }

    public function test_update_department_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateDepartmentRequest::class));
    }

    public function test_store_position_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StorePositionRequest::class));
    }

    public function test_update_position_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdatePositionRequest::class));
    }

    public function test_store_leave_request_request_class_exists(): void
    {
        $this->assertTrue(class_exists(StoreLeaveRequestRequest::class));
    }

    public function test_update_leave_request_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateLeaveRequestRequest::class));
    }

    // ── HTTP Resources ────────────────────────────────────────────────────────

    public function test_employee_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(EmployeeResource::class));
    }

    public function test_employee_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(EmployeeCollection::class));
    }

    public function test_employee_collection_references_employee_resource(): void
    {
        $rc = new \ReflectionClass(EmployeeCollection::class);
        $this->assertStringContainsString('EmployeeResource', file_get_contents($rc->getFileName()));
    }

    public function test_department_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(DepartmentResource::class));
    }

    public function test_department_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(DepartmentCollection::class));
    }

    public function test_position_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(PositionResource::class));
    }

    public function test_position_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(PositionCollection::class));
    }

    public function test_leave_request_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequestResource::class));
    }

    public function test_leave_request_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequestCollection::class));
    }

    // ── Eloquent Models ───────────────────────────────────────────────────────

    public function test_employee_model_class_exists(): void
    {
        $this->assertTrue(class_exists(EmployeeModel::class));
    }

    public function test_employee_model_uses_soft_deletes(): void
    {
        $uses = class_uses_recursive(EmployeeModel::class);
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, $uses);
    }

    public function test_employee_model_table_name(): void
    {
        $model = new EmployeeModel;
        $this->assertStringContainsString('hr_employees', $model->getTable());
    }

    public function test_employee_model_has_expected_fillable_fields(): void
    {
        $model    = new EmployeeModel;
        $fillable = $model->getFillable();
        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('first_name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('employee_number', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    public function test_employee_model_has_expected_casts(): void
    {
        $model = new EmployeeModel;
        $casts = $model->getCasts();
        $this->assertArrayHasKey('tenant_id', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('metadata', $casts);
    }

    public function test_department_model_class_exists(): void
    {
        $this->assertTrue(class_exists(DepartmentModel::class));
    }

    public function test_department_model_uses_soft_deletes(): void
    {
        $uses = class_uses_recursive(DepartmentModel::class);
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, $uses);
    }

    public function test_department_model_table_name(): void
    {
        $model = new DepartmentModel;
        $this->assertStringContainsString('hr_departments', $model->getTable());
    }

    public function test_department_model_has_expected_fillable_fields(): void
    {
        $model    = new DepartmentModel;
        $fillable = $model->getFillable();
        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('lft', $fillable);
        $this->assertContains('rgt', $fillable);
    }

    public function test_department_model_has_expected_casts(): void
    {
        $model = new DepartmentModel;
        $casts = $model->getCasts();
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('metadata', $casts);
        $this->assertArrayHasKey('lft', $casts);
    }

    public function test_position_model_class_exists(): void
    {
        $this->assertTrue(class_exists(PositionModel::class));
    }

    public function test_position_model_uses_soft_deletes(): void
    {
        $uses = class_uses_recursive(PositionModel::class);
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, $uses);
    }

    public function test_position_model_table_name(): void
    {
        $model = new PositionModel;
        $this->assertStringContainsString('hr_positions', $model->getTable());
    }

    public function test_position_model_has_expected_fillable_fields(): void
    {
        $model    = new PositionModel;
        $fillable = $model->getFillable();
        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    public function test_leave_request_model_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequestModel::class));
    }

    public function test_leave_request_model_uses_soft_deletes(): void
    {
        $uses = class_uses_recursive(LeaveRequestModel::class);
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, $uses);
    }

    public function test_leave_request_model_table_name(): void
    {
        $model = new LeaveRequestModel;
        $this->assertStringContainsString('hr_leave_requests', $model->getTable());
    }

    public function test_leave_request_model_has_expected_fillable_fields(): void
    {
        $model    = new LeaveRequestModel;
        $fillable = $model->getFillable();
        $this->assertContains('employee_id', $fillable);
        $this->assertContains('leave_type', $fillable);
        $this->assertContains('status', $fillable);
    }

    public function test_leave_request_model_has_expected_casts(): void
    {
        $model = new LeaveRequestModel;
        $casts = $model->getCasts();
        $this->assertArrayHasKey('employee_id', $casts);
        $this->assertArrayHasKey('metadata', $casts);
        $this->assertArrayHasKey('approved_at', $casts);
    }

    // ── Eloquent Repositories ─────────────────────────────────────────────────

    public function test_eloquent_employee_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentEmployeeRepository::class));
    }

    public function test_eloquent_employee_repository_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(EloquentEmployeeRepository::class, EmployeeRepositoryInterface::class));
    }

    public function test_eloquent_employee_repository_has_save_method(): void
    {
        $this->assertTrue(method_exists(EloquentEmployeeRepository::class, 'save'));
    }

    public function test_eloquent_department_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentDepartmentRepository::class));
    }

    public function test_eloquent_department_repository_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(EloquentDepartmentRepository::class, DepartmentRepositoryInterface::class));
    }

    public function test_eloquent_position_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentPositionRepository::class));
    }

    public function test_eloquent_position_repository_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(EloquentPositionRepository::class, PositionRepositoryInterface::class));
    }

    public function test_eloquent_leave_request_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(EloquentLeaveRequestRepository::class));
    }

    public function test_eloquent_leave_request_repository_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(EloquentLeaveRequestRepository::class, LeaveRequestRepositoryInterface::class));
    }

    // ── Service Provider ──────────────────────────────────────────────────────

    public function test_hr_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(HRServiceProvider::class));
    }

    public function test_hr_service_provider_extends_service_provider(): void
    {
        $this->assertTrue(is_subclass_of(HRServiceProvider::class, \Illuminate\Support\ServiceProvider::class));
    }

    public function test_hr_service_provider_binds_employee_interfaces(): void
    {
        $rc = new \ReflectionClass(HRServiceProvider::class);
        $source = file_get_contents($rc->getFileName());
        $this->assertStringContainsString('EmployeeRepositoryInterface::class', $source);
        $this->assertStringContainsString('FindEmployeeServiceInterface::class', $source);
        $this->assertStringContainsString('CreateEmployeeServiceInterface::class', $source);
    }

    public function test_hr_service_provider_binds_department_interfaces(): void
    {
        $rc = new \ReflectionClass(HRServiceProvider::class);
        $source = file_get_contents($rc->getFileName());
        $this->assertStringContainsString('DepartmentRepositoryInterface::class', $source);
        $this->assertStringContainsString('FindDepartmentServiceInterface::class', $source);
    }

    public function test_hr_service_provider_binds_position_interfaces(): void
    {
        $rc = new \ReflectionClass(HRServiceProvider::class);
        $source = file_get_contents($rc->getFileName());
        $this->assertStringContainsString('PositionRepositoryInterface::class', $source);
        $this->assertStringContainsString('FindPositionServiceInterface::class', $source);
    }

    public function test_hr_service_provider_binds_leave_request_interfaces(): void
    {
        $rc = new \ReflectionClass(HRServiceProvider::class);
        $source = file_get_contents($rc->getFileName());
        $this->assertStringContainsString('LeaveRequestRepositoryInterface::class', $source);
        $this->assertStringContainsString('ApproveLeaveRequestServiceInterface::class', $source);
        $this->assertStringContainsString('RejectLeaveRequestServiceInterface::class', $source);
    }

    public function test_hr_service_provider_loads_routes(): void
    {
        $rc = new \ReflectionClass(HRServiceProvider::class);
        $this->assertStringContainsString('loadRoutesFrom', file_get_contents($rc->getFileName()));
    }

    public function test_hr_service_provider_loads_migrations(): void
    {
        $rc = new \ReflectionClass(HRServiceProvider::class);
        $this->assertStringContainsString('loadMigrationsFrom', file_get_contents($rc->getFileName()));
    }

    public function test_hr_service_provider_is_registered_in_bootstrap_providers(): void
    {
        $providers = require __DIR__ . '/../../bootstrap/providers.php';
        $this->assertContains(HRServiceProvider::class, $providers);
    }

    // ── Routes ────────────────────────────────────────────────────────────────

    public function test_hr_routes_file_exists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../app/Modules/HR/routes/api.php');
    }

    public function test_hr_routes_contains_employees_resource(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('employees', $source);
    }

    public function test_hr_routes_contains_departments_resource(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('departments', $source);
    }

    public function test_hr_routes_contains_positions_resource(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('positions', $source);
    }

    public function test_hr_routes_contains_leave_requests_resource(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('leave-requests', $source);
    }

    public function test_hr_routes_contains_approve_route(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('approve', $source);
    }

    public function test_hr_routes_contains_reject_route(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('reject', $source);
    }

    // ── Database Migrations ───────────────────────────────────────────────────

    public function test_hr_employees_migration_file_exists(): void
    {
        $files = glob(__DIR__ . '/../../app/Modules/HR/database/migrations/*create_hr_employees_table*');
        $this->assertNotEmpty($files);
    }

    public function test_hr_departments_migration_file_exists(): void
    {
        $files = glob(__DIR__ . '/../../app/Modules/HR/database/migrations/*create_hr_departments_table*');
        $this->assertNotEmpty($files);
    }

    public function test_hr_positions_migration_file_exists(): void
    {
        $files = glob(__DIR__ . '/../../app/Modules/HR/database/migrations/*create_hr_positions_table*');
        $this->assertNotEmpty($files);
    }

    public function test_hr_leave_requests_migration_file_exists(): void
    {
        $files = glob(__DIR__ . '/../../app/Modules/HR/database/migrations/*create_hr_leave_requests_table*');
        $this->assertNotEmpty($files);
    }

    public function test_hr_employees_migration_creates_expected_columns(): void
    {
        $files  = glob(__DIR__ . '/../../app/Modules/HR/database/migrations/*create_hr_employees_table*');
        $source = file_get_contents($files[0]);
        $this->assertStringContainsString('tenant_id', $source);
        $this->assertStringContainsString('first_name', $source);
        $this->assertStringContainsString('employee_number', $source);
        $this->assertStringContainsString('is_active', $source);
        $this->assertStringContainsString('softDeletes', $source);
    }

    public function test_hr_departments_migration_creates_expected_columns(): void
    {
        $files  = glob(__DIR__ . '/../../app/Modules/HR/database/migrations/*create_hr_departments_table*');
        $source = file_get_contents($files[0]);
        $this->assertStringContainsString('tenant_id', $source);
        $this->assertStringContainsString('is_active', $source);
    }

    public function test_hr_leave_requests_migration_creates_expected_columns(): void
    {
        $files  = glob(__DIR__ . '/../../app/Modules/HR/database/migrations/*create_hr_leave_requests_table*');
        $source = file_get_contents($files[0]);
        $this->assertStringContainsString('employee_id', $source);
        $this->assertStringContainsString('leave_type', $source);
        $this->assertStringContainsString('status', $source);
        $this->assertStringContainsString('approved_by', $source);
    }

    // ── Employee userId feature ────────────────────────────────────────────────

    public function test_employee_entity_has_user_id_field(): void
    {
        $employee = $this->createTestEmployee();
        $this->assertNull($employee->getUserId());
    }

    public function test_employee_entity_stores_user_id_via_constructor(): void
    {
        $employee = new \Modules\HR\Domain\Entities\Employee(
            tenantId:       1,
            firstName:      new Name('Jane'),
            lastName:       new Name('Smith'),
            email:          new Email('jane@example.com'),
            employeeNumber: new Code('EMP-002'),
            hireDate:       new \DateTimeImmutable('2023-01-01'),
            employmentType: 'full_time',
            userId:         42,
        );
        $this->assertSame(42, $employee->getUserId());
    }

    public function test_employee_link_to_user_sets_user_id(): void
    {
        $employee = $this->createTestEmployee();
        $employee->linkToUser(99);
        $this->assertSame(99, $employee->getUserId());
    }

    public function test_employee_link_to_user_updates_updated_at(): void
    {
        $employee  = $this->createTestEmployee();
        $employee->linkToUser(5);
        $this->assertNotNull($employee->getUpdatedAt());
    }

    public function test_employee_update_details_accepts_user_id(): void
    {
        $employee = $this->createTestEmployee();
        $employee->updateDetails(
            new Name('John'), new Name('Doe'),
            new Email('john@example.com'), null,
            null, null, null,
            new Code('EMP-001'), new \DateTimeImmutable('2023-01-01'),
            'full_time', 'active',
            null, null, null, null, 'USD', null, null, true, 77
        );
        $this->assertSame(77, $employee->getUserId());
    }

    public function test_employee_data_dto_has_user_id_field(): void
    {
        $dto = \Modules\HR\Application\DTOs\EmployeeData::fromArray([
            'tenant_id'       => 1,
            'first_name'      => 'John',
            'last_name'       => 'Doe',
            'email'           => 'john@example.com',
            'employee_number' => 'EMP-001',
            'hire_date'       => '2023-01-01',
            'employment_type' => 'full_time',
            'currency'        => 'USD',
            'user_id'         => 55,
        ]);
        $this->assertSame(55, $dto->user_id);
    }

    public function test_update_employee_data_dto_tracks_user_id(): void
    {
        $dto = \Modules\HR\Application\DTOs\UpdateEmployeeData::fromArray(['id' => 1, 'user_id' => 77]);
        $this->assertTrue($dto->isProvided('user_id'));
        $this->assertSame(77, $dto->user_id);
    }

    public function test_employee_resource_includes_user_id(): void
    {
        $employee = $this->createTestEmployee();
        $employee->linkToUser(10);
        $resource = new \Modules\HR\Infrastructure\Http\Resources\EmployeeResource($employee);
        $array    = $resource->toArray(new \Illuminate\Http\Request());
        $this->assertArrayHasKey('user_id', $array);
        $this->assertSame(10, $array['user_id']);
    }

    // ── EmployeeLinkedToUser event ─────────────────────────────────────────────

    public function test_employee_linked_to_user_event_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Events\EmployeeLinkedToUser::class));
    }

    public function test_employee_linked_to_user_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Domain\Events\EmployeeLinkedToUser::class, \Modules\Core\Domain\Events\BaseEvent::class));
    }

    public function test_employee_linked_to_user_event_stores_employee(): void
    {
        $employee = $this->createTestEmployee();
        $event    = new \Modules\HR\Domain\Events\EmployeeLinkedToUser($employee);
        $this->assertSame($employee, $event->employee);
    }

    // ── LinkEmployeeToUserService ──────────────────────────────────────────────

    public function test_link_employee_to_user_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\LinkEmployeeToUserServiceInterface::class));
    }

    public function test_link_employee_to_user_service_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\Services\LinkEmployeeToUserService::class));
    }

    public function test_link_employee_to_user_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(
            \Modules\HR\Application\Services\LinkEmployeeToUserService::class,
            \Modules\HR\Application\Contracts\LinkEmployeeToUserServiceInterface::class
        ));
    }

    // ── FindEmployeeService findByUserId ───────────────────────────────────────

    public function test_find_employee_service_interface_has_find_by_user_id(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Application\Contracts\FindEmployeeServiceInterface::class, 'findByUserId')
        );
    }

    public function test_find_employee_service_has_find_by_user_id(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Application\Services\FindEmployeeService::class, 'findByUserId')
        );
    }

    public function test_employee_repository_interface_has_find_by_user_id(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface::class, 'findByUserId')
        );
    }

    // ── EmployeeSelfServiceController ─────────────────────────────────────────

    public function test_employee_self_service_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Controllers\EmployeeSelfServiceController::class));
    }

    public function test_employee_self_service_controller_has_profile_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Infrastructure\Http\Controllers\EmployeeSelfServiceController::class, 'profile')
        );
    }

    public function test_employee_self_service_controller_has_leave_requests_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Infrastructure\Http\Controllers\EmployeeSelfServiceController::class, 'leaveRequests')
        );
    }

    // ── Attendance Domain ─────────────────────────────────────────────────────

    public function test_attendance_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Entities\Attendance::class));
    }

    public function test_attendance_entity_can_be_constructed(): void
    {
        $attendance = new \Modules\HR\Domain\Entities\Attendance(
            tenantId:    1,
            employeeId:  5,
            date:        '2024-01-15',
            checkInTime: new \DateTimeImmutable('2024-01-15 09:00:00'),
            status:      'present',
        );
        $this->assertSame(1, $attendance->getTenantId());
        $this->assertSame(5, $attendance->getEmployeeId());
        $this->assertSame('2024-01-15', $attendance->getDate());
        $this->assertSame('present', $attendance->getStatus());
        $this->assertNull($attendance->getCheckOutTime());
    }

    public function test_attendance_entity_check_out(): void
    {
        $attendance = new \Modules\HR\Domain\Entities\Attendance(
            tenantId:    1,
            employeeId:  5,
            date:        '2024-01-15',
            checkInTime: new \DateTimeImmutable('2024-01-15 09:00:00'),
            status:      'present',
        );
        $attendance->checkOut(new \DateTimeImmutable('2024-01-15 17:00:00'), 8.0);
        $this->assertNotNull($attendance->getCheckOutTime());
        $this->assertSame(8.0, $attendance->getHoursWorked());
    }

    public function test_attendance_entity_update_details(): void
    {
        $attendance = new \Modules\HR\Domain\Entities\Attendance(
            tenantId:    1,
            employeeId:  5,
            date:        '2024-01-15',
            checkInTime: new \DateTimeImmutable('2024-01-15 09:00:00'),
            status:      'present',
        );
        $attendance->updateDetails('2024-01-16', new \DateTimeImmutable('2024-01-16 08:30:00'), 'late', 'Arrived late', 7.5);
        $this->assertSame('2024-01-16', $attendance->getDate());
        $this->assertSame('late', $attendance->getStatus());
        $this->assertSame('Arrived late', $attendance->getNotes());
        $this->assertSame(7.5, $attendance->getHoursWorked());
    }

    public function test_attendance_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Exceptions\AttendanceNotFoundException::class));
    }

    public function test_attendance_not_found_exception_extends_not_found_exception(): void
    {
        $this->assertTrue(is_subclass_of(
            \Modules\HR\Domain\Exceptions\AttendanceNotFoundException::class,
            \Modules\Core\Domain\Exceptions\NotFoundException::class
        ));
    }

    public function test_attendance_not_found_exception_message_contains_id(): void
    {
        $e = new \Modules\HR\Domain\Exceptions\AttendanceNotFoundException(7);
        $this->assertStringContainsString('7', $e->getMessage());
        $this->assertStringContainsString('Attendance', $e->getMessage());
    }

    public function test_attendance_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface::class));
    }

    public function test_attendance_repository_interface_has_get_by_employee(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface::class, 'getByEmployee')
        );
    }

    public function test_attendance_repository_interface_has_save(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface::class, 'save')
        );
    }

    // ── Attendance Events ──────────────────────────────────────────────────────

    public function test_attendance_created_event_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Events\AttendanceCreated::class));
    }

    public function test_attendance_updated_event_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Events\AttendanceUpdated::class));
    }

    public function test_attendance_deleted_event_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Events\AttendanceDeleted::class));
    }

    public function test_attendance_events_extend_base_event(): void
    {
        $attendance = new \Modules\HR\Domain\Entities\Attendance(
            tenantId: 1, employeeId: 5, date: '2024-01-15',
            checkInTime: new \DateTimeImmutable('2024-01-15 09:00:00'), status: 'present',
        );
        $created = new \Modules\HR\Domain\Events\AttendanceCreated($attendance);
        $updated = new \Modules\HR\Domain\Events\AttendanceUpdated($attendance);
        $this->assertInstanceOf(\Modules\Core\Domain\Events\BaseEvent::class, $created);
        $this->assertInstanceOf(\Modules\Core\Domain\Events\BaseEvent::class, $updated);
    }

    // ── Attendance Services ────────────────────────────────────────────────────

    public function test_find_attendance_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\FindAttendanceServiceInterface::class));
    }

    public function test_create_attendance_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\CreateAttendanceServiceInterface::class));
    }

    public function test_update_attendance_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\UpdateAttendanceServiceInterface::class));
    }

    public function test_delete_attendance_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\DeleteAttendanceServiceInterface::class));
    }

    public function test_find_attendance_service_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\Services\FindAttendanceService::class));
    }

    public function test_create_attendance_service_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\Services\CreateAttendanceService::class));
    }

    public function test_update_attendance_service_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\Services\UpdateAttendanceService::class));
    }

    public function test_delete_attendance_service_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\Services\DeleteAttendanceService::class));
    }

    // ── Attendance DTOs ────────────────────────────────────────────────────────

    public function test_attendance_data_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\DTOs\AttendanceData::class));
    }

    public function test_update_attendance_data_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\DTOs\UpdateAttendanceData::class));
    }

    public function test_attendance_data_dto_is_provided_tracks_fields(): void
    {
        $dto = \Modules\HR\Application\DTOs\UpdateAttendanceData::fromArray(['id' => 1, 'status' => 'late']);
        $this->assertTrue($dto->isProvided('status'));
        $this->assertFalse($dto->isProvided('notes'));
    }

    // ── Attendance Infrastructure ──────────────────────────────────────────────

    public function test_attendance_model_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceModel::class));
    }

    public function test_attendance_model_has_correct_table(): void
    {
        $model = new \Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceModel();
        $this->assertSame('hr_attendance', $model->getTable());
    }

    public function test_eloquent_attendance_repository_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttendanceRepository::class));
    }

    public function test_attendance_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Controllers\AttendanceController::class));
    }

    public function test_attendance_controller_has_by_employee_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Infrastructure\Http\Controllers\AttendanceController::class, 'byEmployee')
        );
    }

    public function test_attendance_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Resources\AttendanceResource::class));
    }

    public function test_attendance_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Resources\AttendanceCollection::class));
    }

    public function test_store_attendance_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\StoreAttendanceRequest::class));
    }

    public function test_update_attendance_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\UpdateAttendanceRequest::class));
    }

    // ── Attendance Migration ───────────────────────────────────────────────────

    public function test_hr_attendance_migration_file_exists(): void
    {
        $files = glob(__DIR__ . '/../../app/Modules/HR/database/migrations/*create_hr_attendance_table*');
        $this->assertNotEmpty($files);
    }

    public function test_hr_attendance_migration_creates_expected_columns(): void
    {
        $files  = glob(__DIR__ . '/../../app/Modules/HR/database/migrations/*create_hr_attendance_table*');
        $source = file_get_contents($files[0]);
        $this->assertStringContainsString('employee_id', $source);
        $this->assertStringContainsString('check_in_time', $source);
        $this->assertStringContainsString('check_out_time', $source);
        $this->assertStringContainsString('status', $source);
        $this->assertStringContainsString('softDeletes', $source);
    }

    // ── user_id Migration ──────────────────────────────────────────────────────

    public function test_hr_user_id_migration_file_exists(): void
    {
        $files = glob(__DIR__ . '/../../app/Modules/HR/database/migrations/*add_user_id_to_hr_employees*');
        $this->assertNotEmpty($files);
    }

    // ── Application UseCases ───────────────────────────────────────────────────

    public function test_use_case_create_employee_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\CreateEmployee::class));
    }

    public function test_use_case_get_employee_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\GetEmployee::class));
    }

    public function test_use_case_list_employees_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\ListEmployees::class));
    }

    public function test_use_case_update_employee_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\UpdateEmployee::class));
    }

    public function test_use_case_delete_employee_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\DeleteEmployee::class));
    }

    public function test_use_case_create_department_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\CreateDepartment::class));
    }

    public function test_use_case_get_department_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\GetDepartment::class));
    }

    public function test_use_case_list_departments_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\ListDepartments::class));
    }

    public function test_use_case_update_department_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\UpdateDepartment::class));
    }

    public function test_use_case_delete_department_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\DeleteDepartment::class));
    }

    public function test_use_case_create_position_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\CreatePosition::class));
    }

    public function test_use_case_get_position_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\GetPosition::class));
    }

    public function test_use_case_list_positions_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\ListPositions::class));
    }

    public function test_use_case_update_position_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\UpdatePosition::class));
    }

    public function test_use_case_delete_position_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\DeletePosition::class));
    }

    public function test_use_case_create_leave_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\CreateLeaveRequest::class));
    }

    public function test_use_case_get_leave_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\GetLeaveRequest::class));
    }

    public function test_use_case_list_leave_requests_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\ListLeaveRequests::class));
    }

    public function test_use_case_update_leave_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\UpdateLeaveRequest::class));
    }

    public function test_use_case_delete_leave_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\DeleteLeaveRequest::class));
    }

    public function test_use_case_approve_leave_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\ApproveLeaveRequest::class));
    }

    public function test_use_case_reject_leave_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\RejectLeaveRequest::class));
    }

    public function test_use_case_create_attendance_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\CreateAttendance::class));
    }

    public function test_use_case_get_attendance_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\GetAttendance::class));
    }

    public function test_use_case_list_attendance_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\ListAttendance::class));
    }

    public function test_use_case_update_attendance_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\UpdateAttendance::class));
    }

    public function test_use_case_delete_attendance_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\DeleteAttendance::class));
    }

    // ── HRServiceProvider Attendance bindings ──────────────────────────────────

    public function test_hr_service_provider_references_attendance_repository(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/Infrastructure/Providers/HRServiceProvider.php');
        $this->assertStringContainsString('AttendanceRepositoryInterface', $source);
        $this->assertStringContainsString('EloquentAttendanceRepository', $source);
    }

    public function test_hr_service_provider_references_attendance_services(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/Infrastructure/Providers/HRServiceProvider.php');
        $this->assertStringContainsString('FindAttendanceServiceInterface', $source);
        $this->assertStringContainsString('CreateAttendanceServiceInterface', $source);
        $this->assertStringContainsString('UpdateAttendanceServiceInterface', $source);
        $this->assertStringContainsString('DeleteAttendanceServiceInterface', $source);
    }

    // ── Routes ────────────────────────────────────────────────────────────────

    public function test_routes_file_has_attendance_routes(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('attendance', $source);
        $this->assertStringContainsString('AttendanceController', $source);
    }

    public function test_routes_file_has_link_user_route(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('link-user', $source);
        $this->assertStringContainsString('linkUser', $source);
    }

    public function test_routes_file_has_self_service_routes(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('EmployeeSelfServiceController', $source);
        $this->assertStringContainsString("'me'", $source);
    }

    // ── EmployeeController linkUser method ─────────────────────────────────────

    public function test_employee_controller_has_link_user_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Infrastructure\Http\Controllers\EmployeeController::class, 'linkUser')
        );
    }

    public function test_link_employee_to_user_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\LinkEmployeeToUserRequest::class));
    }

    // ── Biometric device abstraction layer ────────────────────────────────────

    public function test_biometric_device_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Domain\Biometric\BiometricDeviceInterface::class));
    }

    public function test_biometric_device_interface_declares_required_methods(): void
    {
        $methods = get_class_methods(\Modules\HR\Domain\Biometric\BiometricDeviceInterface::class);
        $this->assertContains('getType',     $methods);
        $this->assertContains('getDeviceId', $methods);
        $this->assertContains('scan',        $methods);
        $this->assertContains('identify',    $methods);
        $this->assertContains('enroll',      $methods);
        $this->assertContains('isAvailable', $methods);
    }

    public function test_biometric_scan_result_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Biometric\BiometricScanResult::class));
    }

    public function test_biometric_scan_result_success_factory(): void
    {
        $result = \Modules\HR\Domain\Biometric\BiometricScanResult::success(
            deviceType: 'fingerprint',
            deviceId:   'fp-001',
            template:   'dGVzdA==',
            confidence: 0.98,
            employeeId: 42,
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame('fingerprint', $result->getDeviceType());
        $this->assertSame('fp-001',      $result->getDeviceId());
        $this->assertSame('dGVzdA==',    $result->getTemplate());
        $this->assertSame(0.98,          $result->getConfidence());
        $this->assertSame(42,            $result->getEmployeeId());
    }

    public function test_biometric_scan_result_failure_factory(): void
    {
        $result = \Modules\HR\Domain\Biometric\BiometricScanResult::failure(
            deviceType: 'fingerprint',
            deviceId:   'fp-001',
        );

        $this->assertFalse($result->isSuccess());
        $this->assertSame('', $result->getTemplate());
        $this->assertSame(0.0, $result->getConfidence());
        $this->assertNull($result->getEmployeeId());
    }

    public function test_biometric_device_type_constants_exist(): void
    {
        $this->assertSame('fingerprint', \Modules\HR\Domain\Biometric\BiometricDeviceType::FINGERPRINT);
        $this->assertSame('face',        \Modules\HR\Domain\Biometric\BiometricDeviceType::FACE);
        $this->assertSame('iris',        \Modules\HR\Domain\Biometric\BiometricDeviceType::IRIS);
        $this->assertSame('rfid',        \Modules\HR\Domain\Biometric\BiometricDeviceType::RFID);
        $this->assertSame('palm_vein',   \Modules\HR\Domain\Biometric\BiometricDeviceType::PALM_VEIN);
    }

    public function test_biometric_device_exception_extends_runtime_exception(): void
    {
        $this->assertTrue(
            is_subclass_of(\Modules\HR\Domain\Biometric\BiometricDeviceException::class, \RuntimeException::class)
        );
    }

    public function test_biometric_device_registry_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Biometric\BiometricDeviceRegistryInterface::class));
    }

    public function test_biometric_attendance_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Biometric\BiometricAttendanceServiceInterface::class));
    }

    public function test_biometric_enrollment_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Biometric\BiometricEnrollmentServiceInterface::class));
    }

    public function test_biometric_device_registry_implements_interface(): void
    {
        $this->assertTrue(
            is_a(
                \Modules\HR\Infrastructure\Biometric\BiometricDeviceRegistry::class,
                \Modules\HR\Application\Biometric\BiometricDeviceRegistryInterface::class,
                true
            )
        );
    }

    public function test_fingerprint_device_adapter_implements_biometric_device_interface(): void
    {
        $this->assertTrue(
            is_a(
                \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter::class,
                \Modules\HR\Domain\Biometric\BiometricDeviceInterface::class,
                true
            )
        );
    }

    public function test_fingerprint_device_adapter_returns_correct_type(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter('fp-test');
        $this->assertSame('fingerprint', $device->getType());
        $this->assertSame('fp-test', $device->getDeviceId());
    }

    public function test_fingerprint_device_adapter_is_available(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter('fp-test', true);
        $this->assertTrue($device->isAvailable());
    }

    public function test_fingerprint_device_adapter_not_available_when_stub_off(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter('fp-test', false);
        $this->assertFalse($device->isAvailable());
    }

    public function test_fingerprint_device_adapter_scan_returns_scan_result(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter('fp-test', true);
        $result = $device->scan();
        $this->assertInstanceOf(\Modules\HR\Domain\Biometric\BiometricScanResult::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    public function test_fingerprint_device_adapter_scan_fails_when_unavailable(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter('fp-test', false);
        $result = $device->scan();
        $this->assertFalse($result->isSuccess());
    }

    public function test_fingerprint_device_adapter_enroll_and_identify(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter('fp-test');
        $device->enroll(7, 'template-abc');
        $this->assertSame(7, $device->identify('template-abc'));
    }

    public function test_fingerprint_device_adapter_identify_returns_null_for_unknown_template(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter('fp-test');
        $this->assertNull($device->identify('unknown-template'));
    }

    public function test_null_biometric_device_implements_interface(): void
    {
        $this->assertTrue(
            is_a(
                \Modules\HR\Infrastructure\Biometric\NullBiometricDevice::class,
                \Modules\HR\Domain\Biometric\BiometricDeviceInterface::class,
                true
            )
        );
    }

    public function test_null_biometric_device_is_not_available(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\NullBiometricDevice;
        $this->assertFalse($device->isAvailable());
    }

    public function test_null_biometric_device_identify_returns_null(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\NullBiometricDevice;
        $this->assertNull($device->identify('any-template'));
    }

    public function test_null_biometric_device_enroll_returns_true(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\NullBiometricDevice;
        $this->assertTrue($device->enroll(1, 'template'));
    }

    public function test_biometric_device_registry_register_and_get(): void
    {
        $registry = new \Modules\HR\Infrastructure\Biometric\BiometricDeviceRegistry;
        $device   = new \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter('fp-001');
        $registry->register($device);

        $this->assertTrue($registry->has('fp-001'));
        $this->assertSame($device, $registry->get('fp-001'));
    }

    public function test_biometric_device_registry_throws_for_unknown_device(): void
    {
        $registry = new \Modules\HR\Infrastructure\Biometric\BiometricDeviceRegistry;
        $this->expectException(\Modules\HR\Domain\Biometric\BiometricDeviceException::class);
        $registry->get('nonexistent');
    }

    public function test_biometric_device_registry_all_returns_registered_devices(): void
    {
        $registry = new \Modules\HR\Infrastructure\Biometric\BiometricDeviceRegistry;
        $d1 = new \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter('fp-001');
        $d2 = new \Modules\HR\Infrastructure\Biometric\NullBiometricDevice('null-001');
        $registry->register($d1);
        $registry->register($d2);

        $all = $registry->all();
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('fp-001',   $all);
        $this->assertArrayHasKey('null-001', $all);
    }

    public function test_biometric_enrollment_service_implements_interface(): void
    {
        $this->assertTrue(
            is_a(
                \Modules\HR\Infrastructure\Biometric\BiometricEnrollmentService::class,
                \Modules\HR\Application\Biometric\BiometricEnrollmentServiceInterface::class,
                true
            )
        );
    }

    public function test_biometric_enrollment_service_enroll_delegates_to_device(): void
    {
        $device = new \Modules\HR\Infrastructure\Biometric\FingerprintDeviceAdapter('fp-test');
        $registry = new \Modules\HR\Infrastructure\Biometric\BiometricDeviceRegistry;
        $registry->register($device);

        $service = new \Modules\HR\Infrastructure\Biometric\BiometricEnrollmentService($registry);
        $result  = $service->enroll(99, 'fp-test', 'my-template');

        $this->assertTrue($result);
        $this->assertSame(99, $device->identify('my-template'));
    }

    public function test_biometric_attendance_service_implements_interface(): void
    {
        $this->assertTrue(
            is_a(
                \Modules\HR\Infrastructure\Biometric\BiometricAttendanceService::class,
                \Modules\HR\Application\Biometric\BiometricAttendanceServiceInterface::class,
                true
            )
        );
    }

    public function test_biometric_attendance_controller_class_exists(): void
    {
        $this->assertTrue(
            class_exists(\Modules\HR\Infrastructure\Http\Controllers\BiometricAttendanceController::class)
        );
    }

    public function test_biometric_attendance_controller_has_required_methods(): void
    {
        $class = \Modules\HR\Infrastructure\Http\Controllers\BiometricAttendanceController::class;
        $this->assertTrue(method_exists($class, 'checkIn'));
        $this->assertTrue(method_exists($class, 'checkOut'));
        $this->assertTrue(method_exists($class, 'enroll'));
        $this->assertTrue(method_exists($class, 'devices'));
    }

    public function test_biometric_check_in_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\BiometricCheckInRequest::class));
    }

    public function test_biometric_enroll_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\BiometricEnrollRequest::class));
    }

    public function test_routes_file_has_biometric_routes(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('biometric', $source);
        $this->assertStringContainsString('BiometricAttendanceController', $source);
        $this->assertStringContainsString('check-in',  $source);
        $this->assertStringContainsString('check-out', $source);
        $this->assertStringContainsString('enroll',    $source);
    }

    public function test_hr_service_provider_references_biometric_interfaces(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/Infrastructure/Providers/HRServiceProvider.php');
        $this->assertStringContainsString('BiometricDeviceRegistryInterface', $source);
        $this->assertStringContainsString('BiometricAttendanceServiceInterface', $source);
        $this->assertStringContainsString('BiometricEnrollmentServiceInterface', $source);
    }

    public function test_hr_service_provider_references_biometric_implementations(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/Infrastructure/Providers/HRServiceProvider.php');
        $this->assertStringContainsString('BiometricDeviceRegistry', $source);
        $this->assertStringContainsString('BiometricAttendanceService', $source);
        $this->assertStringContainsString('BiometricEnrollmentService', $source);
        $this->assertStringContainsString('FingerprintDeviceAdapter',   $source);
    }

    // ── Cancel Leave Request ──────────────────────────────────────────────────

    public function test_cancel_leave_request_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(CancelLeaveRequestServiceInterface::class));
    }

    public function test_cancel_leave_request_service_interface_extends_write_service_interface(): void
    {
        $this->assertTrue(is_subclass_of(CancelLeaveRequestServiceInterface::class, \Modules\Core\Application\Contracts\WriteServiceInterface::class));
    }

    public function test_cancel_leave_request_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CancelLeaveRequestService::class));
    }

    public function test_cancel_leave_request_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(CancelLeaveRequestService::class, CancelLeaveRequestServiceInterface::class));
    }

    public function test_use_case_cancel_leave_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\CancelLeaveRequest::class));
    }

    public function test_use_case_cancel_leave_request_has_execute_method(): void
    {
        $this->assertTrue(method_exists(\Modules\HR\Application\UseCases\CancelLeaveRequest::class, 'execute'));
    }

    public function test_leave_request_cancelled_event_class_exists(): void
    {
        $this->assertTrue(class_exists(LeaveRequestCancelled::class));
    }

    public function test_leave_request_cancelled_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(LeaveRequestCancelled::class, BaseEvent::class));
    }

    public function test_leave_request_cancelled_event_holds_leave_request(): void
    {
        $lr    = $this->createTestLeaveRequest();
        $event = new LeaveRequestCancelled($lr);
        $this->assertSame($lr, $event->leaveRequest);
    }

    public function test_leave_request_controller_has_cancel_method(): void
    {
        $this->assertTrue(method_exists(LeaveRequestController::class, 'cancel'));
    }

    public function test_routes_file_has_cancel_leave_request_route(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString("leave-requests/{id}/cancel", $source);
    }

    public function test_hr_service_provider_references_cancel_leave_request_service(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/Infrastructure/Providers/HRServiceProvider.php');
        $this->assertStringContainsString('CancelLeaveRequestServiceInterface', $source);
        $this->assertStringContainsString('CancelLeaveRequestService', $source);
    }

    // ── Employee Self Service Enhancements ────────────────────────────────────

    public function test_employee_self_service_controller_has_submit_leave_request_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Infrastructure\Http\Controllers\EmployeeSelfServiceController::class, 'submitLeaveRequest')
        );
    }

    public function test_employee_self_service_controller_has_cancel_leave_request_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Infrastructure\Http\Controllers\EmployeeSelfServiceController::class, 'cancelLeaveRequest')
        );
    }

    public function test_employee_self_service_controller_has_attendance_method(): void
    {
        $this->assertTrue(
            method_exists(\Modules\HR\Infrastructure\Http\Controllers\EmployeeSelfServiceController::class, 'attendance')
        );
    }

    public function test_self_service_leave_request_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\SelfServiceLeaveRequestRequest::class));
    }

    public function test_routes_file_has_self_service_submit_leave_request_route(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('submitLeaveRequest', $source);
    }

    public function test_routes_file_has_self_service_attendance_route(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString("'attendance'", $source);
    }

    public function test_routes_file_has_self_service_cancel_leave_request_route(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('cancelLeaveRequest', $source);
    }

    // ── Payroll Sub-Module ────────────────────────────────────────────────────

    public function test_payroll_record_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Exceptions\PayrollRecordNotFoundException::class));
    }

    public function test_payroll_record_exception_extends_not_found_exception(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Domain\Exceptions\PayrollRecordNotFoundException::class, \Modules\Core\Domain\Exceptions\NotFoundException::class));
    }

    public function test_payroll_record_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Entities\PayrollRecord::class));
    }

    public function test_payroll_record_entity_can_be_constructed(): void
    {
        $record = new \Modules\HR\Domain\Entities\PayrollRecord(
            tenantId: 1,
            employeeId: 2,
            payPeriodStart: '2026-01-01',
            payPeriodEnd: '2026-01-31',
            grossSalary: 5000.0,
            netSalary: 4000.0,
        );
        $this->assertSame(1, $record->getTenantId());
        $this->assertSame(2, $record->getEmployeeId());
        $this->assertSame('draft', $record->getStatus());
    }

    public function test_payroll_record_entity_process_changes_status(): void
    {
        $record = new \Modules\HR\Domain\Entities\PayrollRecord(1, 2, '2026-01-01', '2026-01-31', 5000.0, 4000.0);
        $record->process();
        $this->assertSame('processed', $record->getStatus());
    }

    public function test_payroll_record_entity_mark_as_paid_changes_status(): void
    {
        $record = new \Modules\HR\Domain\Entities\PayrollRecord(1, 2, '2026-01-01', '2026-01-31', 5000.0, 4000.0);
        $record->markAsPaid();
        $this->assertSame('paid', $record->getStatus());
    }

    public function test_payroll_record_entity_is_draft(): void
    {
        $record = new \Modules\HR\Domain\Entities\PayrollRecord(1, 2, '2026-01-01', '2026-01-31', 5000.0, 4000.0);
        $this->assertTrue($record->isDraft());
    }

    public function test_payroll_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface::class));
    }

    public function test_find_payroll_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\FindPayrollServiceInterface::class));
    }

    public function test_create_payroll_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\CreatePayrollServiceInterface::class));
    }

    public function test_update_payroll_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\UpdatePayrollServiceInterface::class));
    }

    public function test_delete_payroll_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\DeletePayrollServiceInterface::class));
    }

    public function test_process_payroll_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\ProcessPayrollServiceInterface::class));
    }

    public function test_find_payroll_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\FindPayrollService::class, \Modules\HR\Application\Contracts\FindPayrollServiceInterface::class));
    }

    public function test_create_payroll_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\CreatePayrollService::class, \Modules\HR\Application\Contracts\CreatePayrollServiceInterface::class));
    }

    public function test_update_payroll_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\UpdatePayrollService::class, \Modules\HR\Application\Contracts\UpdatePayrollServiceInterface::class));
    }

    public function test_delete_payroll_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\DeletePayrollService::class, \Modules\HR\Application\Contracts\DeletePayrollServiceInterface::class));
    }

    public function test_process_payroll_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\ProcessPayrollService::class, \Modules\HR\Application\Contracts\ProcessPayrollServiceInterface::class));
    }

    public function test_payroll_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Controllers\PayrollController::class));
    }

    public function test_payroll_controller_has_required_methods(): void
    {
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PayrollController::class, 'index'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PayrollController::class, 'show'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PayrollController::class, 'store'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PayrollController::class, 'update'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PayrollController::class, 'destroy'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PayrollController::class, 'process'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PayrollController::class, 'byEmployee'));
    }

    public function test_payroll_record_created_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Domain\Events\PayrollRecordCreated::class, \Modules\Core\Domain\Events\BaseEvent::class));
    }

    public function test_payroll_record_processed_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Domain\Events\PayrollRecordProcessed::class, \Modules\Core\Domain\Events\BaseEvent::class));
    }

    public function test_routes_file_has_payroll_routes(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('payroll', $source);
        $this->assertStringContainsString('PayrollController', $source);
    }

    public function test_hr_service_provider_references_payroll_services(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/Infrastructure/Providers/HRServiceProvider.php');
        $this->assertStringContainsString('FindPayrollServiceInterface', $source);
        $this->assertStringContainsString('CreatePayrollServiceInterface', $source);
        $this->assertStringContainsString('ProcessPayrollServiceInterface', $source);
    }

    public function test_payroll_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\DTOs\PayrollData::class));
    }

    public function test_update_payroll_data_has_provided_keys_pattern(): void
    {
        $dto = \Modules\HR\Application\DTOs\UpdatePayrollData::fromArray(['gross_salary' => 5000.0]);
        $this->assertTrue($dto->isProvided('gross_salary'));
        $this->assertFalse($dto->isProvided('net_salary'));
    }

    public function test_use_case_get_payroll_record_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\GetPayrollRecord::class));
    }

    public function test_use_case_list_payroll_records_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\ListPayrollRecords::class));
    }

    public function test_use_case_create_payroll_record_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\CreatePayrollRecord::class));
    }

    public function test_use_case_update_payroll_record_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\UpdatePayrollRecord::class));
    }

    public function test_use_case_delete_payroll_record_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\DeletePayrollRecord::class));
    }

    public function test_use_case_process_payroll_record_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\ProcessPayrollRecord::class));
    }

    public function test_store_payroll_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\StorePayrollRequest::class));
    }

    public function test_update_payroll_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\UpdatePayrollRequest::class));
    }

    public function test_payroll_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Resources\PayrollResource::class));
    }

    public function test_payroll_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Resources\PayrollCollection::class));
    }

    // ── PerformanceReview Sub-Module ──────────────────────────────────────────

    public function test_performance_review_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Exceptions\PerformanceReviewNotFoundException::class));
    }

    public function test_performance_review_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Entities\PerformanceReview::class));
    }

    public function test_performance_review_entity_can_be_constructed(): void
    {
        $review = new \Modules\HR\Domain\Entities\PerformanceReview(
            tenantId: 1,
            employeeId: 2,
            reviewerId: 3,
            reviewPeriodStart: '2026-01-01',
            reviewPeriodEnd: '2026-03-31',
            rating: 4.5,
        );
        $this->assertSame(1, $review->getTenantId());
        $this->assertSame(2, $review->getEmployeeId());
        $this->assertSame(4.5, $review->getRating());
        $this->assertSame('draft', $review->getStatus());
    }

    public function test_performance_review_entity_submit_changes_status(): void
    {
        $review = new \Modules\HR\Domain\Entities\PerformanceReview(1, 2, 3, '2026-01-01', '2026-03-31', 4.0);
        $review->submit();
        $this->assertSame('submitted', $review->getStatus());
    }

    public function test_performance_review_entity_acknowledge_changes_status(): void
    {
        $review = new \Modules\HR\Domain\Entities\PerformanceReview(1, 2, 3, '2026-01-01', '2026-03-31', 4.0);
        $review->acknowledge();
        $this->assertSame('acknowledged', $review->getStatus());
    }

    public function test_performance_review_entity_is_draft(): void
    {
        $review = new \Modules\HR\Domain\Entities\PerformanceReview(1, 2, 3, '2026-01-01', '2026-03-31', 4.0);
        $this->assertTrue($review->isDraft());
    }

    public function test_performance_review_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface::class));
    }

    public function test_find_performance_review_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\FindPerformanceReviewServiceInterface::class));
    }

    public function test_create_performance_review_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\CreatePerformanceReviewServiceInterface::class));
    }

    public function test_update_performance_review_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\UpdatePerformanceReviewServiceInterface::class));
    }

    public function test_delete_performance_review_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\DeletePerformanceReviewServiceInterface::class));
    }

    public function test_submit_performance_review_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\SubmitPerformanceReviewServiceInterface::class));
    }

    public function test_find_performance_review_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\FindPerformanceReviewService::class, \Modules\HR\Application\Contracts\FindPerformanceReviewServiceInterface::class));
    }

    public function test_create_performance_review_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\CreatePerformanceReviewService::class, \Modules\HR\Application\Contracts\CreatePerformanceReviewServiceInterface::class));
    }

    public function test_update_performance_review_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\UpdatePerformanceReviewService::class, \Modules\HR\Application\Contracts\UpdatePerformanceReviewServiceInterface::class));
    }

    public function test_delete_performance_review_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\DeletePerformanceReviewService::class, \Modules\HR\Application\Contracts\DeletePerformanceReviewServiceInterface::class));
    }

    public function test_submit_performance_review_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\SubmitPerformanceReviewService::class, \Modules\HR\Application\Contracts\SubmitPerformanceReviewServiceInterface::class));
    }

    public function test_performance_review_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Controllers\PerformanceReviewController::class));
    }

    public function test_performance_review_controller_has_required_methods(): void
    {
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PerformanceReviewController::class, 'index'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PerformanceReviewController::class, 'show'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PerformanceReviewController::class, 'store'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PerformanceReviewController::class, 'update'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PerformanceReviewController::class, 'destroy'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PerformanceReviewController::class, 'submit'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\PerformanceReviewController::class, 'byEmployee'));
    }

    public function test_performance_review_created_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Domain\Events\PerformanceReviewCreated::class, \Modules\Core\Domain\Events\BaseEvent::class));
    }

    public function test_performance_review_submitted_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Domain\Events\PerformanceReviewSubmitted::class, \Modules\Core\Domain\Events\BaseEvent::class));
    }

    public function test_routes_file_has_performance_review_routes(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('performance-reviews', $source);
        $this->assertStringContainsString('PerformanceReviewController', $source);
    }

    public function test_hr_service_provider_references_performance_review_services(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/Infrastructure/Providers/HRServiceProvider.php');
        $this->assertStringContainsString('FindPerformanceReviewServiceInterface', $source);
        $this->assertStringContainsString('CreatePerformanceReviewServiceInterface', $source);
        $this->assertStringContainsString('SubmitPerformanceReviewServiceInterface', $source);
    }

    public function test_performance_review_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\DTOs\PerformanceReviewData::class));
    }

    public function test_update_performance_review_data_has_provided_keys_pattern(): void
    {
        $dto = \Modules\HR\Application\DTOs\UpdatePerformanceReviewData::fromArray(['rating' => 4.5]);
        $this->assertTrue($dto->isProvided('rating'));
        $this->assertFalse($dto->isProvided('comments'));
    }

    public function test_use_case_get_performance_review_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\GetPerformanceReview::class));
    }

    public function test_use_case_list_performance_reviews_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\ListPerformanceReviews::class));
    }

    public function test_use_case_create_performance_review_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\CreatePerformanceReview::class));
    }

    public function test_use_case_update_performance_review_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\UpdatePerformanceReview::class));
    }

    public function test_use_case_delete_performance_review_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\DeletePerformanceReview::class));
    }

    public function test_use_case_submit_performance_review_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\SubmitPerformanceReview::class));
    }

    public function test_store_performance_review_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\StorePerformanceReviewRequest::class));
    }

    public function test_update_performance_review_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\UpdatePerformanceReviewRequest::class));
    }

    public function test_performance_review_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Resources\PerformanceReviewResource::class));
    }

    public function test_performance_review_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Resources\PerformanceReviewCollection::class));
    }

    // ── Training Sub-Module ───────────────────────────────────────────────────

    public function test_training_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Exceptions\TrainingNotFoundException::class));
    }

    public function test_training_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Domain\Entities\Training::class));
    }

    public function test_training_entity_can_be_constructed(): void
    {
        $training = new \Modules\HR\Domain\Entities\Training(
            tenantId: 1,
            title: 'PHP Workshop',
            startDate: '2026-06-01',
        );
        $this->assertSame(1, $training->getTenantId());
        $this->assertSame('PHP Workshop', $training->getTitle());
        $this->assertSame('scheduled', $training->getStatus());
        $this->assertTrue($training->isActive());
    }

    public function test_training_entity_start_changes_status(): void
    {
        $training = new \Modules\HR\Domain\Entities\Training(1, 'PHP Workshop', '2026-06-01');
        $training->start();
        $this->assertSame('in_progress', $training->getStatus());
    }

    public function test_training_entity_complete_changes_status(): void
    {
        $training = new \Modules\HR\Domain\Entities\Training(1, 'PHP Workshop', '2026-06-01');
        $training->complete();
        $this->assertSame('completed', $training->getStatus());
    }

    public function test_training_entity_cancel_changes_status(): void
    {
        $training = new \Modules\HR\Domain\Entities\Training(1, 'PHP Workshop', '2026-06-01');
        $training->cancel();
        $this->assertSame('cancelled', $training->getStatus());
        $this->assertFalse($training->isActive());
    }

    public function test_training_entity_is_active(): void
    {
        $training = new \Modules\HR\Domain\Entities\Training(1, 'PHP Workshop', '2026-06-01');
        $this->assertTrue($training->isActive());
    }

    public function test_training_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface::class));
    }

    public function test_find_training_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\FindTrainingServiceInterface::class));
    }

    public function test_create_training_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\CreateTrainingServiceInterface::class));
    }

    public function test_update_training_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\UpdateTrainingServiceInterface::class));
    }

    public function test_delete_training_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\HR\Application\Contracts\DeleteTrainingServiceInterface::class));
    }

    public function test_find_training_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\FindTrainingService::class, \Modules\HR\Application\Contracts\FindTrainingServiceInterface::class));
    }

    public function test_create_training_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\CreateTrainingService::class, \Modules\HR\Application\Contracts\CreateTrainingServiceInterface::class));
    }

    public function test_update_training_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\UpdateTrainingService::class, \Modules\HR\Application\Contracts\UpdateTrainingServiceInterface::class));
    }

    public function test_delete_training_service_implements_interface(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Application\Services\DeleteTrainingService::class, \Modules\HR\Application\Contracts\DeleteTrainingServiceInterface::class));
    }

    public function test_training_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Controllers\TrainingController::class));
    }

    public function test_training_controller_has_required_methods(): void
    {
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\TrainingController::class, 'index'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\TrainingController::class, 'show'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\TrainingController::class, 'store'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\TrainingController::class, 'update'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\TrainingController::class, 'destroy'));
        $this->assertTrue(method_exists(\Modules\HR\Infrastructure\Http\Controllers\TrainingController::class, 'byStatus'));
    }

    public function test_training_created_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Domain\Events\TrainingCreated::class, \Modules\Core\Domain\Events\BaseEvent::class));
    }

    public function test_training_deleted_event_extends_base_event(): void
    {
        $this->assertTrue(is_subclass_of(\Modules\HR\Domain\Events\TrainingDeleted::class, \Modules\Core\Domain\Events\BaseEvent::class));
    }

    public function test_routes_file_has_training_routes(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/routes/api.php');
        $this->assertStringContainsString('training', $source);
        $this->assertStringContainsString('TrainingController', $source);
    }

    public function test_hr_service_provider_references_training_services(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Modules/HR/Infrastructure/Providers/HRServiceProvider.php');
        $this->assertStringContainsString('FindTrainingServiceInterface', $source);
        $this->assertStringContainsString('CreateTrainingServiceInterface', $source);
        $this->assertStringContainsString('DeleteTrainingServiceInterface', $source);
    }

    public function test_training_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\DTOs\TrainingData::class));
    }

    public function test_update_training_data_has_provided_keys_pattern(): void
    {
        $dto = \Modules\HR\Application\DTOs\UpdateTrainingData::fromArray(['title' => 'New Title']);
        $this->assertTrue($dto->isProvided('title'));
        $this->assertFalse($dto->isProvided('description'));
    }

    public function test_use_case_get_training_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\GetTraining::class));
    }

    public function test_use_case_list_trainings_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\ListTrainings::class));
    }

    public function test_use_case_create_training_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\CreateTraining::class));
    }

    public function test_use_case_update_training_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\UpdateTraining::class));
    }

    public function test_use_case_delete_training_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Application\UseCases\DeleteTraining::class));
    }

    public function test_store_training_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\StoreTrainingRequest::class));
    }

    public function test_update_training_request_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Requests\UpdateTrainingRequest::class));
    }

    public function test_training_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Resources\TrainingResource::class));
    }

    public function test_training_collection_class_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\HR\Infrastructure\Http\Resources\TrainingCollection::class));
    }
}

