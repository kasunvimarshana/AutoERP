<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Seeders;

use Database\Seeders\Concerns\SeedsAutoErpData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class HRModuleSeeder extends Seeder
{
    use SeedsAutoErpData;

    public function run(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        $tenantId = $this->defaultTenantId();
        if ($tenantId === null) {
            return;
        }

        DB::transaction(function () use ($tenantId): void {
            $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);
            $userId = $this->defaultUserId($tenantId);
            $operationsDepartmentId = $this->department($tenantId, $organizationUnitId, 'OPS', 'Operations', null, true);
            $workshopDepartmentId = $this->department($tenantId, $organizationUnitId, 'WORKSHOP', 'Workshop', $operationsDepartmentId, true);
            $financeDepartmentId = $this->department($tenantId, $organizationUnitId, 'FIN', 'Finance', null, true);
            $this->department($tenantId, $organizationUnitId, 'LEGACY-HR', 'Legacy HR Unit', null, false);

            $managerDesignationId = $this->designation($tenantId, $organizationUnitId, $operationsDepartmentId, 'OPS-MGR', 'Operations Manager', true);
            $technicianDesignationId = $this->designation($tenantId, $organizationUnitId, $workshopDepartmentId, 'TECH', 'Technician', true);
            $accountantDesignationId = $this->designation($tenantId, $organizationUnitId, $financeDepartmentId, 'ACC', 'Accountant', true);
            $this->designation($tenantId, $organizationUnitId, null, 'OLD-DESIG', 'Deprecated Designation', false);

            $permanentTypeId = $this->employmentType($tenantId, $organizationUnitId, 'PERM', 'Permanent', true);
            $probationTypeId = $this->employmentType($tenantId, $organizationUnitId, 'PROB', 'Probation', true);
            $contractTypeId = $this->employmentType($tenantId, $organizationUnitId, 'CONT', 'Contract', true);
            $this->employmentType($tenantId, $organizationUnitId, 'OLD', 'Inactive Employment Type', false);

            $managerId = $this->employee($tenantId, $organizationUnitId, $userId, [
                'employee_code' => 'EMP-DEMO-001',
                'first_name' => 'Ishara',
                'last_name' => 'Senanayake',
                'display_name' => 'Ishara Senanayake',
                'full_name' => 'Ishara Senanayake',
                'gender' => 'female',
                'date_of_birth' => '1988-05-12',
                'national_id_number' => 'NIC-EMP-1001',
                'email' => 'ishara.senanayake@example.test',
                'phone' => '+94 11 555 2101',
                'mobile' => '+94 77 555 2101',
                'department_id' => $operationsDepartmentId,
                'designation_id' => $managerDesignationId,
                'employment_type_id' => $permanentTypeId,
                'reporting_manager_id' => null,
                'joining_date' => '2024-03-01',
                'leaving_date' => null,
                'employment_status' => 'active',
                'is_active' => true,
                'notes' => 'Active manager used for approvals, reporting hierarchy, and HR dashboards.',
            ], '2026-01-12 09:00:00');

            $technicianId = $this->employee($tenantId, $organizationUnitId, $userId, [
                'employee_code' => 'EMP-DEMO-002',
                'first_name' => 'Ruwan',
                'last_name' => 'Fernando',
                'display_name' => 'Ruwan Fernando',
                'full_name' => 'Ruwan Fernando',
                'gender' => 'male',
                'date_of_birth' => '1992-09-20',
                'national_id_number' => 'NIC-EMP-1002',
                'email' => 'ruwan.fernando@example.test',
                'phone' => '+94 11 555 2201',
                'mobile' => '+94 77 555 2201',
                'department_id' => $workshopDepartmentId,
                'designation_id' => $technicianDesignationId,
                'employment_type_id' => $permanentTypeId,
                'reporting_manager_id' => $managerId,
                'joining_date' => '2025-07-01',
                'leaving_date' => null,
                'employment_status' => 'active',
                'is_active' => true,
                'notes' => 'Active technician for service labor, attendance, and payroll workflows.',
            ], '2026-01-12 09:30:00');

            $probationId = $this->employee($tenantId, $organizationUnitId, $userId, [
                'employee_code' => 'EMP-DEMO-003',
                'first_name' => 'Anjali',
                'last_name' => 'De Silva',
                'display_name' => 'Anjali De Silva',
                'full_name' => 'Anjali De Silva',
                'gender' => 'female',
                'date_of_birth' => '1998-02-14',
                'national_id_number' => 'NIC-EMP-1003',
                'email' => 'anjali.desilva@example.test',
                'phone' => '+94 11 555 2301',
                'mobile' => '+94 77 555 2301',
                'department_id' => $financeDepartmentId,
                'designation_id' => $accountantDesignationId,
                'employment_type_id' => $probationTypeId,
                'reporting_manager_id' => $managerId,
                'joining_date' => '2026-02-01',
                'leaving_date' => null,
                'employment_status' => 'probation',
                'is_active' => true,
                'notes' => 'Probation employee for approval and policy edge cases.',
            ], '2026-02-01 09:00:00');

            $terminatedId = $this->employee($tenantId, $organizationUnitId, $userId, [
                'employee_code' => 'EMP-DEMO-900',
                'first_name' => 'Legacy',
                'last_name' => 'Employee',
                'display_name' => 'Legacy Employee',
                'full_name' => 'Legacy Employee',
                'gender' => null,
                'date_of_birth' => '1985-01-01',
                'national_id_number' => 'NIC-EMP-9001',
                'email' => 'legacy.employee@example.test',
                'phone' => null,
                'mobile' => null,
                'department_id' => $operationsDepartmentId,
                'designation_id' => $technicianDesignationId,
                'employment_type_id' => $contractTypeId,
                'reporting_manager_id' => $managerId,
                'joining_date' => '2023-01-15',
                'leaving_date' => '2026-01-31',
                'employment_status' => 'terminated',
                'is_active' => false,
                'notes' => 'Terminated employee retained for history, payslip, and filter testing.',
            ], '2026-01-31 17:00:00');

            if ($managerId !== null) {
                DB::table('departments')->where('id', $operationsDepartmentId)->update([
                    'manager_employee_id' => $managerId,
                    'updated_at' => now(),
                ]);
            }

            $shiftId = $this->shift($tenantId, $organizationUnitId, $userId);
            $salaryStructureId = $this->salaryStructure($tenantId, $organizationUnitId, $userId);
            $annualLeaveId = $this->leaveType($tenantId, $organizationUnitId, $userId, 'AL', 'Annual Leave', true, '14.0000');
            $sickLeaveId = $this->leaveType($tenantId, $organizationUnitId, $userId, 'SL', 'Sick Leave', true, '7.0000');
            $this->leaveType($tenantId, $organizationUnitId, $userId, 'LOP', 'Leave Without Pay', false, null);

            foreach (array_filter([$managerId, $technicianId, $probationId, $terminatedId]) as $employeeId) {
                $this->employeeContact($tenantId, $organizationUnitId, (int) $employeeId, $userId);
                $this->contract($tenantId, $organizationUnitId, (int) $employeeId, $userId);

                if ($shiftId !== null && (int) $employeeId !== $terminatedId) {
                    $this->shiftAssignment($tenantId, $organizationUnitId, (int) $employeeId, $shiftId, $userId);
                }

                if ($salaryStructureId !== null) {
                    $this->salaryAssignment($tenantId, $organizationUnitId, (int) $employeeId, $salaryStructureId, $userId);
                }

                if ($annualLeaveId !== null) {
                    $this->leaveAllocation($tenantId, $organizationUnitId, (int) $employeeId, $annualLeaveId, $userId, '14.0000');
                }

                if ($sickLeaveId !== null) {
                    $this->leaveAllocation($tenantId, $organizationUnitId, (int) $employeeId, $sickLeaveId, $userId, '7.0000');
                }
            }

            if ($technicianId !== null && $annualLeaveId !== null) {
                $this->leaveApplication($tenantId, $organizationUnitId, $technicianId, $annualLeaveId, $userId, 'approved');
            }

            if ($probationId !== null && $sickLeaveId !== null) {
                $this->leaveApplication($tenantId, $organizationUnitId, $probationId, $sickLeaveId, $userId, 'pending');
            }
        }, 3);
    }

    private function department(int $tenantId, ?int $organizationUnitId, string $code, string $name, ?int $parentId, bool $active): int
    {
        $path = $parentId === null ? '/'.strtolower($code) : '/ops/'.strtolower($code);
        $this->upsert('departments', [
            'tenant_id' => $tenantId,
            'department_code' => $code,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', $active ? 'active_department' : 'inactive_department'),
            'parent_id' => $parentId,
            'manager_employee_id' => null,
            'department_name' => $name,
            'depth' => $parentId === null ? 0 : 1,
            'path' => $path,
            'is_active' => $active,
            'description' => 'Seeded department for hierarchy and filter testing.',
        ]);

        return (int) DB::table('departments')->where('tenant_id', $tenantId)->where('department_code', $code)->value('id');
    }

    private function designation(int $tenantId, ?int $organizationUnitId, ?int $departmentId, string $code, string $name, bool $active): int
    {
        $this->upsert('designations', [
            'tenant_id' => $tenantId,
            'designation_code' => $code,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'designation'),
            'department_id' => $departmentId,
            'designation_name' => $name,
            'is_active' => $active,
            'description' => 'Seeded designation.',
        ]);

        return (int) DB::table('designations')->where('tenant_id', $tenantId)->where('designation_code', $code)->value('id');
    }

    private function employmentType(int $tenantId, ?int $organizationUnitId, string $code, string $name, bool $active): int
    {
        $this->upsert('employment_types', [
            'tenant_id' => $tenantId,
            'employment_type_code' => $code,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'employment_type'),
            'employment_type_name' => $name,
            'is_active' => $active,
        ]);

        return (int) DB::table('employment_types')->where('tenant_id', $tenantId)->where('employment_type_code', $code)->value('id');
    }

    /**
     * @param  array<string, mixed>  $employee
     */
    private function employee(int $tenantId, ?int $organizationUnitId, ?int $userId, array $employee, string $statusAt): ?int
    {
        $this->upsert('employees', [
            'tenant_id' => $tenantId,
            'employee_code' => $employee['employee_code'],
        ], $employee + [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', (string) $employee['employment_status']),
            'created_by' => $userId,
            'updated_by' => $userId,
            'activated_by' => $employee['is_active'] ? $userId : null,
            'activated_at' => $employee['is_active'] ? $statusAt : null,
            'terminated_by' => $employee['employment_status'] === 'terminated' ? $userId : null,
            'terminated_at' => $employee['employment_status'] === 'terminated' ? $statusAt : null,
        ]);

        $employeeId = $this->idBy('employees', ['tenant_id' => $tenantId, 'employee_code' => $employee['employee_code']]);
        if ($employeeId === null) {
            return null;
        }

        $this->upsert('employee_status_histories', [
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'to_status' => $employee['employment_status'],
            'reason' => 'Seeded lifecycle state.',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'status_history'),
            'from_status' => 'draft',
            'changed_by' => $userId,
            'changed_at' => $statusAt,
        ]);

        return $employeeId;
    }

    private function employeeContact(int $tenantId, ?int $organizationUnitId, int $employeeId, ?int $userId): void
    {
        $this->upsert('employee_contacts', [
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'contact_name' => 'Emergency Contact',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'emergency_contact'),
            'contact_type' => 'family',
            'relationship' => 'Spouse',
            'email' => null,
            'phone' => '+94 11 555 2999',
            'mobile' => '+94 77 555 2999',
            'is_primary' => true,
            'is_emergency' => true,
            'is_active' => true,
            'notes' => 'Seeded emergency contact.',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function contract(int $tenantId, ?int $organizationUnitId, int $employeeId, ?int $userId): void
    {
        $terminated = DB::table('employees')->where('id', $employeeId)->value('employment_status') === 'terminated';
        $this->upsert('employee_contracts', [
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'start_date' => $terminated ? '2023-01-15' : '2026-01-01',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'contract'),
            'end_date' => $terminated ? '2026-01-31' : null,
            'contract_type' => $terminated ? 'contract' : 'permanent',
            'salary' => $terminated ? '85000.0000' : '125000.0000',
            'salary_frequency' => 'monthly',
            'currency_id' => $this->currencyId(),
            'status' => $terminated ? 'closed' : 'active',
            'terms' => 'Seeded contract for HR workflow testing.',
            'document_path' => null,
            'created_by' => $userId,
        ]);
    }

    private function shift(int $tenantId, ?int $organizationUnitId, ?int $userId): ?int
    {
        $this->upsert('shifts', [
            'tenant_id' => $tenantId,
            'code' => 'DAY',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'shift'),
            'name' => 'Day Shift',
            'shift_type' => 'regular',
            'start_time' => '08:30:00',
            'end_time' => '17:30:00',
            'break_duration' => 60,
            'grace_minutes' => 10,
            'overtime_threshold' => 540,
            'work_days' => $this->json(['mon', 'tue', 'wed', 'thu', 'fri']),
            'is_night_shift' => false,
            'is_active' => true,
            'created_by' => $userId,
        ]);

        return $this->idBy('shifts', ['tenant_id' => $tenantId, 'code' => 'DAY']);
    }

    private function shiftAssignment(int $tenantId, ?int $organizationUnitId, int $employeeId, int $shiftId, ?int $userId): void
    {
        $this->upsert('shift_assignments', [
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'effective_from' => '2026-01-01',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'shift_assignment'),
            'shift_id' => $shiftId,
            'effective_to' => null,
            'created_by' => $userId,
        ]);
    }

    private function salaryStructure(int $tenantId, ?int $organizationUnitId, ?int $userId): ?int
    {
        $basicId = $this->salaryComponent($tenantId, $organizationUnitId, $userId, 'BASIC', 'Basic Salary', 'earning', 'fixed', '100000.0000');
        $allowanceId = $this->salaryComponent($tenantId, $organizationUnitId, $userId, 'TRAVEL', 'Travel Allowance', 'earning', 'fixed', '15000.0000');
        $deductionId = $this->salaryComponent($tenantId, $organizationUnitId, $userId, 'ADV-DED', 'Advance Deduction', 'deduction', 'fixed', '0.0000');

        $this->upsert('salary_structures', [
            'tenant_id' => $tenantId,
            'code' => 'STD-MONTHLY',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'salary_structure'),
            'name' => 'Standard Monthly Salary',
            'is_active' => true,
            'description' => 'Seeded salary structure with earning and deduction components.',
            'created_by' => $userId,
        ]);

        $structureId = $this->idBy('salary_structures', ['tenant_id' => $tenantId, 'code' => 'STD-MONTHLY']);
        if ($structureId === null) {
            return null;
        }

        foreach ([[$basicId, '100000.0000', 1], [$allowanceId, '15000.0000', 2], [$deductionId, '0.0000', 3]] as [$componentId, $value, $sequence]) {
            if ($componentId === null) {
                continue;
            }

            $this->upsert('salary_structure_lines', [
                'tenant_id' => $tenantId,
                'salary_structure_id' => $structureId,
                'salary_component_id' => $componentId,
            ], [
                'organization_unit_id' => $organizationUnitId,
                'metadata' => $this->seedMetadata('hr_module', 'salary_structure_line'),
                'calculation_type' => 'fixed',
                'value' => $value,
                'sequence' => $sequence,
            ]);
        }

        return $structureId;
    }

    private function salaryComponent(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
        string $code,
        string $name,
        string $type,
        string $calculationType,
        string $value,
    ): ?int {
        $this->upsert('salary_components', [
            'tenant_id' => $tenantId,
            'code' => $code,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'salary_component'),
            'name' => $name,
            'type' => $type,
            'calculation_type' => $calculationType,
            'default_value' => $value,
            'is_taxable' => $type === 'earning',
            'affects_net_pay' => true,
            'account_id' => $this->accountId($tenantId, '5000'),
            'is_active' => true,
            'created_by' => $userId,
        ]);

        return $this->idBy('salary_components', ['tenant_id' => $tenantId, 'code' => $code]);
    }

    private function salaryAssignment(int $tenantId, ?int $organizationUnitId, int $employeeId, int $structureId, ?int $userId): void
    {
        $this->upsert('employee_salary_assignments', [
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'effective_from' => '2026-01-01',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'salary_assignment'),
            'salary_structure_id' => $structureId,
            'effective_to' => null,
            'base_salary' => '100000.0000',
            'pay_frequency' => 'monthly',
            'created_by' => $userId,
        ]);
    }

    private function leaveType(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
        string $code,
        string $name,
        bool $paid,
        ?string $maxDays,
    ): ?int {
        $this->upsert('leave_types', [
            'tenant_id' => $tenantId,
            'code' => $code,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'leave_type'),
            'name' => $name,
            'description' => 'Seeded leave type.',
            'is_paid' => $paid,
            'requires_approval' => true,
            'is_active' => true,
            'max_days_per_year' => $maxDays,
            'carry_forward_max' => $code === 'AL' ? '5.0000' : '0.0000',
            'allow_negative_balance' => false,
            'applicable_gender' => null,
            'min_service_days' => 0,
            'created_by' => $userId,
        ]);

        return $this->idBy('leave_types', ['tenant_id' => $tenantId, 'code' => $code]);
    }

    private function leaveAllocation(
        int $tenantId,
        ?int $organizationUnitId,
        int $employeeId,
        int $leaveTypeId,
        ?int $userId,
        string $days,
    ): void {
        $this->upsert('leave_allocations', [
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'year' => 2026,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'leave_allocation'),
            'allocated_days' => $days,
            'used_days' => '1.0000',
            'pending_days' => '1.0000',
            'carried_forward' => '0.0000',
            'expiry_date' => '2026-12-31',
            'created_by' => $userId,
        ]);
    }

    private function leaveApplication(
        int $tenantId,
        ?int $organizationUnitId,
        int $employeeId,
        int $leaveTypeId,
        ?int $userId,
        string $status,
    ): void {
        $this->upsert('leave_applications', [
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'start_date' => $status === 'approved' ? '2026-04-10' : '2026-05-20',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('hr_module', 'leave_application'),
            'end_date' => $status === 'approved' ? '2026-04-10' : '2026-05-21',
            'total_days' => $status === 'approved' ? '1.0000' : '2.0000',
            'half_day_type' => null,
            'reason' => $status === 'approved' ? 'Personal appointment.' : 'Medical observation.',
            'status' => $status,
            'approver_id' => $status === 'approved' ? $userId : null,
            'approver_note' => $status === 'approved' ? 'Approved from seed data.' : null,
            'approved_at' => $status === 'approved' ? '2026-04-08 11:00:00' : null,
            'attachment_path' => null,
            'created_by' => $userId,
        ]);
    }
}
