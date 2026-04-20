<?php

declare(strict_types=1);

namespace Tests\Unit\Employee;

use Modules\Employee\Domain\Entities\Employee;
use PHPUnit\Framework\TestCase;

class EmployeeEntityTest extends TestCase
{
    public function test_constructor_rejects_termination_date_before_hire_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Employee(
            tenantId: 9,
            userId: 22,
            hireDate: new \DateTimeImmutable('2024-06-10'),
            terminationDate: new \DateTimeImmutable('2024-06-01'),
        );
    }

    public function test_update_rejects_termination_date_before_hire_date(): void
    {
        $employee = new Employee(
            tenantId: 9,
            userId: 22,
            hireDate: new \DateTimeImmutable('2024-06-10'),
            terminationDate: null,
        );

        $this->expectException(\InvalidArgumentException::class);

        $employee->update(
            userId: 22,
            employeeCode: 'EMP-001',
            orgUnitId: null,
            jobTitle: 'Engineer',
            hireDate: new \DateTimeImmutable('2024-06-10'),
            terminationDate: new \DateTimeImmutable('2024-06-01'),
            metadata: null,
        );
    }
}
