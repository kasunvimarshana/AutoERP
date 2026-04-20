<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Domain\Entities\Employee;
use Modules\Employee\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Tests\TestCase;

class EmployeeRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_save_creates_and_updates_employee(): void
    {
        /** @var EmployeeRepositoryInterface $repository */
        $repository = app(EmployeeRepositoryInterface::class);

        $created = $repository->save(new Employee(
            tenantId: 11,
            userId: 1101,
            employeeCode: 'EMP-11-001',
            orgUnitId: null,
            jobTitle: 'Accountant',
            hireDate: new \DateTimeImmutable('2024-01-10'),
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('EMP-11-001', $created->getEmployeeCode());

        $updated = $repository->save(new Employee(
            id: $created->getId(),
            tenantId: 11,
            userId: 1101,
            employeeCode: 'EMP-11-001',
            orgUnitId: null,
            jobTitle: 'Senior Accountant',
            hireDate: new \DateTimeImmutable('2024-01-10'),
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('Senior Accountant', $updated->getJobTitle());
    }

    public function test_find_by_tenant_and_user_id_returns_domain_entity(): void
    {
        $this->insertEmployeeRow(501, 21, 2101, 'EMP-21-001', 'Engineer');
        $this->insertEmployeeRow(502, 22, 2201, 'EMP-22-001', 'Engineer');

        /** @var EmployeeRepositoryInterface $repository */
        $repository = app(EmployeeRepositoryInterface::class);

        $found = $repository->findByTenantAndUserId(21, 2101);

        $this->assertInstanceOf(Employee::class, $found);
        $this->assertSame(501, $found->getId());
        $this->assertSame(21, $found->getTenantId());
    }

    public function test_paginate_and_where_return_mapped_domain_entities(): void
    {
        $this->insertEmployeeRow(601, 31, 3101, 'EMP-31-C', 'Clerk');
        $this->insertEmployeeRow(602, 31, 3102, 'EMP-31-A', 'Analyst');
        $this->insertEmployeeRow(603, 32, 3201, 'EMP-32-X', 'Executive');

        /** @var EmployeeRepositoryInterface $repository */
        $repository = app(EmployeeRepositoryInterface::class);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 31)
            ->orderBy('employee_code', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $items = $paginator->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(Employee::class, $items[0]);
        $this->assertSame('EMP-31-A', $items[0]->getEmployeeCode());
        $this->assertSame('EMP-31-C', $items[1]->getEmployeeCode());

        $collection = $repository
            ->resetCriteria()
            ->where('tenant_id', 31)
            ->get();

        $this->assertCount(2, $collection);
        $this->assertContainsOnlyInstancesOf(Employee::class, $collection);
    }

    private function insertEmployeeRow(int $id, int $tenantId, int $userId, string $employeeCode, string $jobTitle): void
    {
        DB::table('employees')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'employee_code' => $employeeCode,
            'org_unit_id' => null,
            'job_title' => $jobTitle,
            'hire_date' => '2024-01-01',
            'termination_date' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedReferenceData(): void
    {
        $this->insertTenantAndUser(11, 1101, 'employee-1101@example.com');
        $this->insertTenantAndUser(21, 2101, 'employee-2101@example.com');
        $this->insertTenantAndUser(22, 2201, 'employee-2201@example.com');
        $this->insertTenantAndUser(31, 3101, 'employee-3101@example.com');
        $this->insertTenantAndUser(31, 3102, 'employee-3102@example.com');
        $this->insertTenantAndUser(32, 3201, 'employee-3201@example.com');
    }

    private function insertTenantAndUser(int $tenantId, int $userId, string $email): void
    {
        if (! DB::table('tenants')->where('id', $tenantId)->exists()) {
            DB::table('tenants')->insert([
                'id' => $tenantId,
                'name' => 'Tenant '.$tenantId,
                'slug' => 'tenant-'.$tenantId,
                'domain' => null,
                'logo_path' => null,
                'database_config' => null,
                'mail_config' => null,
                'cache_config' => null,
                'queue_config' => null,
                'feature_flags' => null,
                'api_keys' => null,
                'settings' => null,
                'plan' => 'free',
                'tenant_plan_id' => null,
                'status' => 'active',
                'active' => true,
                'trial_ends_at' => null,
                'subscription_ends_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }

        DB::table('users')->insert([
            'id' => $userId,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'first_name' => 'Employee',
            'last_name' => (string) $userId,
            'email' => $email,
            'email_verified_at' => null,
            'password' => 'hashed-password',
            'phone' => null,
            'avatar' => null,
            'status' => 'active',
            'preferences' => null,
            'address' => null,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
