<?php

declare(strict_types=1);

namespace Modules\Hr\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Hr\Models\HrDepartment;
use Modules\Hr\Models\HrDesignation;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeSkillAssignment;
use Modules\Hr\Models\HrEmploymentType;
use Modules\Hr\Models\HrSkill;

final class HrSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('hr_employees')) {
            return;
        }

        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            $tenantId = (int) $tenant->getKey();
            $organizationUnitId = $organizationUnit?->getKey();
            $departments = $this->seedDepartments($tenantId, $organizationUnitId);
            $designations = $this->seedDesignations($tenantId, $organizationUnitId);
            $employmentTypes = $this->seedEmploymentTypes($tenantId, $organizationUnitId);
            $skills = $this->seedSkills($tenantId, $organizationUnitId);

            $supervisor = HrEmployee::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'employee_number' => 'EMP-000001'],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'code' => 'EMP-SUPERVISOR',
                    'first_name' => 'Service',
                    'last_name' => 'Supervisor',
                    'display_name' => 'Service Supervisor',
                    'email' => 'supervisor@example.com',
                    'department_id' => $departments['SERVICE']->getKey(),
                    'designation_id' => $designations['SUPERVISOR']->getKey(),
                    'employment_type_id' => $employmentTypes['FULL-TIME']->getKey(),
                    'reporting_manager_id' => null,
                    'joined_date' => '2026-01-01',
                    'status' => 'active',
                    'availability_status' => 'available',
                    'default_hourly_rate' => '40.000000',
                    'default_daily_rate' => '0.000000',
                    'default_service_rate' => '0.000000',
                    'metadata' => ['seed_source' => 'hr_module'],
                ],
            );

            $technician = HrEmployee::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'employee_number' => 'EMP-000002'],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'code' => 'EMP-TECHNICIAN',
                    'first_name' => 'Auto',
                    'last_name' => 'Technician',
                    'display_name' => 'Auto Technician',
                    'email' => 'technician@example.com',
                    'department_id' => $departments['SERVICE']->getKey(),
                    'designation_id' => $designations['TECHNICIAN']->getKey(),
                    'employment_type_id' => $employmentTypes['FULL-TIME']->getKey(),
                    'reporting_manager_id' => $supervisor->getKey(),
                    'joined_date' => '2026-01-01',
                    'status' => 'active',
                    'availability_status' => 'available',
                    'default_hourly_rate' => '25.000000',
                    'default_daily_rate' => '0.000000',
                    'default_service_rate' => '0.000000',
                    'metadata' => ['seed_source' => 'hr_module'],
                ],
            );

            if (Schema::hasTable('hr_employee_skill_assignments')) {
                foreach (['GENERAL-SERVICE', 'DIAGNOSTICS'] as $index => $skillCode) {
                    HrEmployeeSkillAssignment::query()->updateOrCreate(
                        [
                            'employee_id' => $technician->getKey(),
                            'skill_id' => $skills[$skillCode]->getKey(),
                        ],
                        [
                            'tenant_id' => $tenantId,
                            'organization_unit_id' => $organizationUnitId,
                            'proficiency_level' => $index === 0 ? 'advanced' : 'intermediate',
                            'years_of_experience' => '3.000000',
                            'is_primary' => $index === 0,
                        ],
                    );
                }
            }
        }, 3);
    }

    /**
     * @return array<string,HrDepartment>
     */
    private function seedDepartments(int $tenantId, ?int $organizationUnitId): array
    {
        $records = [];
        foreach (['SERVICE' => 'Service', 'ADMIN' => 'Administration'] as $code => $name) {
            $records[$code] = HrDepartment::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'parent_id' => null,
                    'name' => $name,
                    'description' => 'Default HR department.',
                    'is_active' => true,
                    'sort_order' => count($records) + 1,
                ],
            );
        }

        return $records;
    }

    /**
     * @return array<string,HrDesignation>
     */
    private function seedDesignations(int $tenantId, ?int $organizationUnitId): array
    {
        $records = [];
        foreach (['SUPERVISOR' => 'Service Supervisor', 'TECHNICIAN' => 'Technician'] as $code => $name) {
            $records[$code] = HrDesignation::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $name,
                    'description' => 'Default HR designation.',
                    'is_active' => true,
                    'sort_order' => count($records) + 1,
                ],
            );
        }

        return $records;
    }

    /**
     * @return array<string,HrEmploymentType>
     */
    private function seedEmploymentTypes(int $tenantId, ?int $organizationUnitId): array
    {
        $records = [];
        foreach (['FULL-TIME' => 'Full Time', 'CONTRACT' => 'Contract'] as $code => $name) {
            $records[$code] = HrEmploymentType::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $name,
                    'description' => 'Default HR employment type.',
                    'is_active' => true,
                    'sort_order' => count($records) + 1,
                ],
            );
        }

        return $records;
    }

    /**
     * @return array<string,HrSkill>
     */
    private function seedSkills(int $tenantId, ?int $organizationUnitId): array
    {
        $records = [];
        foreach (['GENERAL-SERVICE' => 'General Service', 'DIAGNOSTICS' => 'Diagnostics'] as $code => $name) {
            $records[$code] = HrSkill::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $name,
                    'description' => 'Default technician skill.',
                    'is_active' => true,
                ],
            );
        }

        return $records;
    }
}
