# HR (Human Resources) Module

---

**⚠️ IMPLEMENTATION PRINCIPLE**: Rely strictly on native Laravel and Vue features. Always implement functionality manually instead of using third-party libraries.

---


## Overview
Complete Human Resources module for managing employees, departments, attendance, leave, payroll, and performance reviews in a multi-tenant Laravel ERP/CRM system.

## Features

### 1. Employee Management
- Complete employee lifecycle (hiring to termination)
- Employee records with personal and employment details
- Department and position assignment
- Manager-subordinate relationships
- Employee search and filtering

### 2. Department Management
- Hierarchical department structure (parent/child)
- Department managers
- Department-based employee grouping

### 3. Position Management
- Job positions with titles and codes
- Salary bands (min/max)
- Grade levels
- Position responsibilities

### 4. Attendance Tracking
- Daily check-in/check-out
- Automatic work hours calculation
- Attendance status (present, absent, late, half-day, on-leave)
- Historical attendance records

### 5. Leave Management
- Multiple leave types (annual, sick, personal, etc.)
- Leave balance tracking
- Approval workflow
- Leave calendar and history

### 6. Payroll Processing
- Monthly payroll calculation
- Basic salary + allowances - deductions
- Gross and net salary computation
- Payslip generation
- Payment processing

### 7. Performance Management
- Performance reviews with ratings (1-5 scale)
- Strengths and areas for improvement
- Goals and achievements tracking
- Multi-period reviews

## Module Structure

```
Modules/HR/
├── Config/                 # Module configuration
├── Database/
│   ├── Factories/          # 8 Model factories for testing
│   ├── Migrations/         # 8 Database migrations
│   └── Seeders/            # Sample data seeder
├── Entities/               # 8 Eloquent models
├── Events/                 # 4 Domain events
├── Http/
│   ├── Controllers/Api/    # 8 API controllers
│   ├── Requests/           # 16 Form request validators
│   └── Resources/          # 8 API resources
├── Providers/              # Service providers
├── Repositories/           # 8 Repositories + interfaces
├── Routes/                 # API and web routes
└── Services/               # 4 Business logic services
```

## Entities (Models)

1. **Employee** - Employee records with full details
2. **Department** - Organizational departments (hierarchical)
3. **Position** - Job positions with salary bands
4. **Attendance** - Daily attendance tracking
5. **Leave** - Leave requests and approvals
6. **LeaveType** - Leave type definitions
7. **Payroll** - Monthly payroll entries
8. **PerformanceReview** - Performance evaluations

## API Endpoints (50+)

### Employees
- `GET /api/v1/hr/employees` - List employees
- `POST /api/v1/hr/employees` - Create employee
- `GET /api/v1/hr/employees/{id}` - Get employee
- `PUT /api/v1/hr/employees/{id}` - Update employee
- `DELETE /api/v1/hr/employees/{id}` - Delete employee
- `GET /api/v1/hr/employees/search` - Search employees
- `GET /api/v1/hr/employees/department/{id}` - Get by department
- `POST /api/v1/hr/employees/{id}/terminate` - Terminate employee

### Departments
- `GET /api/v1/hr/departments` - List departments
- `POST /api/v1/hr/departments` - Create department
- `GET /api/v1/hr/departments/{id}` - Get department
- `PUT /api/v1/hr/departments/{id}` - Update department
- `DELETE /api/v1/hr/departments/{id}` - Delete department
- `GET /api/v1/hr/departments/tree` - Get department tree
- `GET /api/v1/hr/departments/{id}/employees` - Get department employees

### Positions
- `GET /api/v1/hr/positions` - List positions
- `POST /api/v1/hr/positions` - Create position
- `GET /api/v1/hr/positions/{id}` - Get position
- `PUT /api/v1/hr/positions/{id}` - Update position
- `DELETE /api/v1/hr/positions/{id}` - Delete position
- `GET /api/v1/hr/positions/{id}/employees` - Get employees by position

### Attendance
- `GET /api/v1/hr/attendances` - List attendance
- `POST /api/v1/hr/attendances` - Create attendance
- `GET /api/v1/hr/attendances/{id}` - Get attendance
- `PUT /api/v1/hr/attendances/{id}` - Update attendance
- `DELETE /api/v1/hr/attendances/{id}` - Delete attendance
- `POST /api/v1/hr/attendances/check-in` - Check-in employee
- `POST /api/v1/hr/attendances/check-out` - Check-out employee
- `GET /api/v1/hr/attendances/employee/{id}` - Get employee attendance

### Leave
- `GET /api/v1/hr/leaves` - List leaves
- `POST /api/v1/hr/leaves` - Create leave request
- `GET /api/v1/hr/leaves/{id}` - Get leave
- `PUT /api/v1/hr/leaves/{id}` - Update leave
- `DELETE /api/v1/hr/leaves/{id}` - Delete leave
- `POST /api/v1/hr/leaves/{id}/approve` - Approve leave
- `POST /api/v1/hr/leaves/{id}/reject` - Reject leave
- `GET /api/v1/hr/leaves/balance` - Get leave balance
- `GET /api/v1/hr/leaves/employee/{id}` - Get employee leaves

### Leave Types
- `GET /api/v1/hr/leave-types` - List leave types
- `POST /api/v1/hr/leave-types` - Create leave type
- `GET /api/v1/hr/leave-types/{id}` - Get leave type
- `PUT /api/v1/hr/leave-types/{id}` - Update leave type
- `DELETE /api/v1/hr/leave-types/{id}` - Delete leave type
- `GET /api/v1/hr/leave-types/list` - Get active leave types

### Payroll
- `GET /api/v1/hr/payrolls` - List payrolls
- `POST /api/v1/hr/payrolls` - Create payroll
- `GET /api/v1/hr/payrolls/{id}` - Get payroll
- `PUT /api/v1/hr/payrolls/{id}` - Update payroll
- `DELETE /api/v1/hr/payrolls/{id}` - Delete payroll
- `POST /api/v1/hr/payrolls/calculate` - Calculate payroll
- `POST /api/v1/hr/payrolls/{id}/process-payment` - Process payment
- `GET /api/v1/hr/payrolls/{id}/payslip` - Generate payslip
- `GET /api/v1/hr/payrolls/employee/{id}` - Get employee payrolls

### Performance Reviews
- `GET /api/v1/hr/performance-reviews` - List reviews
- `POST /api/v1/hr/performance-reviews` - Create review
- `GET /api/v1/hr/performance-reviews/{id}` - Get review
- `PUT /api/v1/hr/performance-reviews/{id}` - Update review
- `DELETE /api/v1/hr/performance-reviews/{id}` - Delete review
- `GET /api/v1/hr/performance-reviews/employee/{id}` - Get employee reviews

## Events

1. **EmployeeHired** - Fired when an employee is hired
2. **LeaveApproved** - Fired when leave is approved
3. **PayrollProcessed** - Fired when payroll is processed
4. **PerformanceReviewCompleted** - Fired when a review is completed

## Technical Features

- **Clean Architecture** - Separation of concerns
- **Repository Pattern** - Data access abstraction
- **Service Layer** - Business logic encapsulation
- **Form Validation** - Request validation for all operations
- **API Resources** - Consistent API responses
- **Database Transactions** - Data integrity
- **Soft Deletes** - Safe data removal
- **Tenant Isolation** - Multi-tenancy support
- **Audit Trail** - Created/updated tracking
- **Type Hints** - Full PHP 8.2+ type safety
- **PHPDoc** - Comprehensive documentation

## Installation

The module is automatically registered via the module service provider.

### Run Migrations

```bash
php artisan migrate
```

### Seed Sample Data

```bash
php artisan db:seed --class=Modules\\HR\\Database\\Seeders\\HRSeeder
```

## Usage Examples

### Create an Employee

```php
POST /api/v1/hr/employees
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "hire_date": "2024-01-15",
    "department_id": 1,
    "position_id": 1,
    "employment_type": "full-time",
    "status": "active",
    "salary": 75000
}
```

### Check-in Employee

```php
POST /api/v1/hr/attendances/check-in
{
    "employee_id": 1
}
```

### Request Leave

```php
POST /api/v1/hr/leaves
{
    "employee_id": 1,
    "leave_type_id": 1,
    "start_date": "2024-03-01",
    "end_date": "2024-03-05",
    "reason": "Vacation"
}
```

### Calculate Payroll

```php
POST /api/v1/hr/payrolls/calculate
{
    "employee_id": 1,
    "month": 3,
    "year": 2024,
    "allowances": 500,
    "deductions": 200
}
```

## Dependencies

- **Core Module** - Base repository, service, and traits
- **Tenancy Module** - Multi-tenant support
- Laravel 11.x
- PHP 8.2+
- PostgreSQL

## Testing

```bash
# Run module tests
php artisan test --testsuite=HR
```

## License

Part of the AutoERP system.
