<?php

namespace Modules\HR\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HR\Entities\Attendance;
use Modules\HR\Entities\Department;
use Modules\HR\Entities\Employee;
use Modules\HR\Entities\Leave;
use Modules\HR\Entities\LeaveType;
use Modules\HR\Entities\Payroll;
use Modules\HR\Entities\PerformanceReview;
use Modules\HR\Entities\Position;

class HRSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create departments
        $departments = [
            [
                'tenant_id' => 1,
                'code' => 'EXEC',
                'name' => 'Executive',
                'description' => 'Executive management',
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'code' => 'HR',
                'name' => 'Human Resources',
                'description' => 'HR department',
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'code' => 'IT',
                'name' => 'Information Technology',
                'description' => 'IT department',
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'code' => 'SALES',
                'name' => 'Sales',
                'description' => 'Sales department',
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'code' => 'FIN',
                'name' => 'Finance',
                'description' => 'Finance department',
                'status' => 'active',
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }

        // Create positions
        $positions = [
            [
                'tenant_id' => 1,
                'title' => 'Chief Executive Officer',
                'code' => 'CEO',
                'grade' => 'Executive',
                'min_salary' => 150000,
                'max_salary' => 250000,
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'title' => 'HR Manager',
                'code' => 'HRMGR',
                'grade' => 'Manager',
                'min_salary' => 70000,
                'max_salary' => 100000,
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'title' => 'Software Engineer',
                'code' => 'SWE',
                'grade' => 'Senior',
                'min_salary' => 60000,
                'max_salary' => 120000,
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'title' => 'Sales Representative',
                'code' => 'SALESREP',
                'grade' => 'Mid',
                'min_salary' => 40000,
                'max_salary' => 70000,
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'title' => 'Accountant',
                'code' => 'ACC',
                'grade' => 'Mid',
                'min_salary' => 50000,
                'max_salary' => 80000,
                'status' => 'active',
            ],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }

        // Create employees
        $employees = [
            [
                'tenant_id' => 1,
                'employee_number' => 'EMP-2024-000001',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+1-555-0001',
                'hire_date' => '2020-01-15',
                'department_id' => 1,
                'position_id' => 1,
                'employment_type' => 'full-time',
                'status' => 'active',
                'salary' => 200000,
            ],
            [
                'tenant_id' => 1,
                'employee_number' => 'EMP-2024-000002',
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.johnson@example.com',
                'phone' => '+1-555-0002',
                'hire_date' => '2021-03-10',
                'department_id' => 2,
                'position_id' => 2,
                'reports_to' => 1,
                'employment_type' => 'full-time',
                'status' => 'active',
                'salary' => 85000,
            ],
            [
                'tenant_id' => 1,
                'employee_number' => 'EMP-2024-000003',
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'email' => 'michael.brown@example.com',
                'phone' => '+1-555-0003',
                'hire_date' => '2021-06-01',
                'department_id' => 3,
                'position_id' => 3,
                'reports_to' => 1,
                'employment_type' => 'full-time',
                'status' => 'active',
                'salary' => 95000,
            ],
            [
                'tenant_id' => 1,
                'employee_number' => 'EMP-2024-000004',
                'first_name' => 'Emily',
                'last_name' => 'Davis',
                'email' => 'emily.davis@example.com',
                'phone' => '+1-555-0004',
                'hire_date' => '2022-02-15',
                'department_id' => 4,
                'position_id' => 4,
                'reports_to' => 1,
                'employment_type' => 'full-time',
                'status' => 'active',
                'salary' => 55000,
            ],
            [
                'tenant_id' => 1,
                'employee_number' => 'EMP-2024-000005',
                'first_name' => 'David',
                'last_name' => 'Wilson',
                'email' => 'david.wilson@example.com',
                'phone' => '+1-555-0005',
                'hire_date' => '2022-09-01',
                'department_id' => 5,
                'position_id' => 5,
                'reports_to' => 1,
                'employment_type' => 'full-time',
                'status' => 'active',
                'salary' => 65000,
            ],
        ];

        foreach ($employees as $employee) {
            Employee::create($employee);
        }

        // Create leave types
        $leaveTypes = [
            [
                'tenant_id' => 1,
                'name' => 'Annual Leave',
                'code' => 'ANNUAL',
                'description' => 'Annual vacation leave',
                'max_days_per_year' => 20,
                'is_paid' => true,
                'requires_approval' => true,
                'is_carry_forward' => true,
                'max_carry_forward_days' => 5,
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'name' => 'Sick Leave',
                'code' => 'SICK',
                'description' => 'Medical leave',
                'max_days_per_year' => 10,
                'is_paid' => true,
                'requires_approval' => true,
                'is_carry_forward' => false,
                'status' => 'active',
            ],
            [
                'tenant_id' => 1,
                'name' => 'Personal Leave',
                'code' => 'PERSONAL',
                'description' => 'Personal time off',
                'max_days_per_year' => 5,
                'is_paid' => true,
                'requires_approval' => true,
                'is_carry_forward' => false,
                'status' => 'active',
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::create($leaveType);
        }

        // Create sample attendance records
        Attendance::factory()->count(20)->create([
            'employee_id' => 3,
        ]);

        // Create sample leave records
        Leave::factory()->count(5)->create([
            'employee_id' => 3,
            'leave_type_id' => 1,
        ]);

        // Create sample payroll records
        Payroll::factory()->count(3)->create([
            'employee_id' => 3,
        ]);

        // Create sample performance reviews
        PerformanceReview::factory()->count(2)->create([
            'employee_id' => 3,
            'reviewer_id' => 1,
        ]);
    }
}
