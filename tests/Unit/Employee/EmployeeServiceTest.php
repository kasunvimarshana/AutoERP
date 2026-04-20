<?php

declare(strict_types=1);

namespace Tests\Unit\Employee;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Employee\Application\Services\CreateEmployeeService;
use Modules\Employee\Application\Services\DeleteEmployeeService;
use Modules\Employee\Application\Services\FindEmployeeService;
use Modules\Employee\Application\Services\UpdateEmployeeService;
use Modules\Employee\Domain\Contracts\EmployeeUserSynchronizerInterface;
use Modules\Employee\Domain\Entities\Employee;
use Modules\Employee\Domain\Exceptions\EmployeeNotFoundException;
use Modules\Employee\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    /** @var EmployeeRepositoryInterface&MockObject */
    private EmployeeRepositoryInterface $repository;

    /** @var EmployeeUserSynchronizerInterface&MockObject */
    private EmployeeUserSynchronizerInterface $userSynchronizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(EmployeeRepositoryInterface::class);
        $this->userSynchronizer = $this->createMock(EmployeeUserSynchronizerInterface::class);
    }

    public function test_create_employee_service_maps_payload_and_saves(): void
    {
        $service = new CreateEmployeeService($this->repository, $this->userSynchronizer);

        $this->userSynchronizer
            ->expects($this->once())
            ->method('resolveUserIdForCreate')
            ->with(9, null, 55, null)
            ->willReturn(55);

        $this->repository
            ->expects($this->once())
            ->method('findByTenantAndUserId')
            ->with(9, 55)
            ->willReturn(null);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (mixed $employee): bool {
                if (! $employee instanceof Employee) {
                    return false;
                }

                return $employee->getTenantId() === 9
                    && $employee->getUserId() === 55
                    && $employee->getEmployeeCode() === 'EMP-001';
            }))
            ->willReturn($this->buildEmployee(701));

        $result = $service->execute([
            'tenant_id' => 9,
            'user_id' => 55,
            'employee_code' => 'EMP-001',
            'job_title' => 'Finance Analyst',
        ]);

        $this->assertInstanceOf(Employee::class, $result);
        $this->assertSame(701, $result->getId());
    }

    public function test_find_employee_service_applies_filters_sort_and_pagination(): void
    {
        $service = new FindEmployeeService($this->repository);

        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository->expects($this->once())->method('resetCriteria')->willReturn($this->repository);
        $this->repository->expects($this->exactly(2))->method('where')->withAnyParameters()->willReturn($this->repository);
        $this->repository->expects($this->once())->method('orderBy')->with('employee_code', 'desc')->willReturn($this->repository);
        $this->repository->expects($this->once())->method('paginate')->with(20, ['*'], 'page', 2)->willReturn($paginator);

        $result = $service->list(
            filters: [
                'tenant_id' => 9,
                'job_title' => 'fin',
                'unknown' => 'ignored',
            ],
            perPage: 20,
            page: 2,
            sort: '-employee_code',
        );

        $this->assertSame($paginator, $result);
    }

    public function test_update_employee_service_throws_when_employee_missing(): void
    {
        $service = new UpdateEmployeeService($this->repository, $this->userSynchronizer);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(EmployeeNotFoundException::class);

        $service->execute([
            'id' => 999,
            'tenant_id' => 9,
            'user_id' => 55,
        ]);
    }

    public function test_delete_employee_service_throws_when_employee_missing(): void
    {
        $service = new DeleteEmployeeService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(555)
            ->willReturn(null);

        $this->expectException(EmployeeNotFoundException::class);

        $service->execute(['id' => 555]);
    }

    private function buildEmployee(int $id): Employee
    {
        return new Employee(
            id: $id,
            tenantId: 9,
            userId: 55,
            employeeCode: 'EMP-001',
            orgUnitId: null,
            jobTitle: 'Finance Analyst',
            hireDate: new \DateTimeImmutable('2024-05-01'),
        );
    }

    public function test_update_employee_service_synchronizes_associated_user(): void
    {
        $service = new UpdateEmployeeService($this->repository, $this->userSynchronizer);

        $existing = $this->buildEmployee(411);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(411)
            ->willReturn($existing);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Employee::class))
            ->willReturn($existing);

        $this->userSynchronizer
            ->expects($this->once())
            ->method('synchronizeForEmployeeUpdate')
            ->with(9, 55, null, ['first_name' => 'Updated']);

        $result = $service->execute([
            'id' => 411,
            'tenant_id' => 9,
            'employee_code' => 'EMP-001',
            'user' => ['first_name' => 'Updated'],
        ]);

        $this->assertInstanceOf(Employee::class, $result);
    }
}
