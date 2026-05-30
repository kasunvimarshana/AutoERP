<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\HR;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class HrRoutesTest extends TestCase
{
    public function testHrRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('hr.departments.index'));
        self::assertTrue(Route::has('hr.departments.store'));
        self::assertTrue(Route::has('hr.departments.show'));
        self::assertTrue(Route::has('hr.departments.update'));
        self::assertTrue(Route::has('hr.departments.destroy'));
        self::assertTrue(Route::has('hr.designations.index'));
        self::assertTrue(Route::has('hr.designations.store'));
        self::assertTrue(Route::has('hr.designations.show'));
        self::assertTrue(Route::has('hr.designations.update'));
        self::assertTrue(Route::has('hr.designations.destroy'));
        self::assertTrue(Route::has('hr.employment-types.index'));
        self::assertTrue(Route::has('hr.employment-types.store'));
        self::assertTrue(Route::has('hr.employment-types.show'));
        self::assertTrue(Route::has('hr.employment-types.update'));
        self::assertTrue(Route::has('hr.employment-types.destroy'));
        self::assertTrue(Route::has('hr.employees.index'));
        self::assertTrue(Route::has('hr.employees.store'));
        self::assertTrue(Route::has('hr.employees.show'));
        self::assertTrue(Route::has('hr.employees.update'));
        self::assertTrue(Route::has('hr.employees.destroy'));
        self::assertTrue(Route::has('hr.employees.lookup'));
        self::assertTrue(Route::has('hr.employees.active-list'));
        self::assertTrue(Route::has('hr.employees.by-department'));
        self::assertTrue(Route::has('hr.employees.by-designation'));
        self::assertTrue(Route::has('hr.employees.status'));
        self::assertTrue(Route::has('hr.employees.validate.assignment-context'));
        self::assertTrue(Route::has('hr.employees.employment-details.show'));
        self::assertTrue(Route::has('hr.employees.employment-details.update'));
        self::assertTrue(Route::has('hr.employees.contacts.index'));
        self::assertTrue(Route::has('hr.employees.contacts.store'));
        self::assertTrue(Route::has('hr.employees.contacts.show'));
        self::assertTrue(Route::has('hr.employees.contacts.update'));
        self::assertTrue(Route::has('hr.employees.contacts.destroy'));
        self::assertTrue(Route::has('hr.employees.addresses.index'));
        self::assertTrue(Route::has('hr.employees.addresses.store'));
        self::assertTrue(Route::has('hr.employees.addresses.update'));
        self::assertTrue(Route::has('hr.employees.addresses.destroy'));
        self::assertTrue(Route::has('hr.employees.user-accesses.index'));
        self::assertTrue(Route::has('hr.employees.user-accesses.store'));
        self::assertTrue(Route::has('hr.employees.user-accesses.link-existing'));
        self::assertTrue(Route::has('hr.employees.user-accesses.deactivate'));
        self::assertTrue(Route::has('hr.employees.user-accesses.unlink'));
        self::assertTrue(Route::has('hr.employee-documents.index'));
        self::assertTrue(Route::has('hr.employee-documents.store'));
        self::assertTrue(Route::has('hr.employee-documents.show'));
        self::assertTrue(Route::has('hr.employee-documents.update'));
        self::assertTrue(Route::has('hr.employee-documents.destroy'));
        self::assertTrue(Route::has('hr.employee-contracts.index'));
        self::assertTrue(Route::has('hr.employee-contracts.store'));
        self::assertTrue(Route::has('hr.employee-contracts.show'));
        self::assertTrue(Route::has('hr.employee-contracts.update'));
        self::assertTrue(Route::has('hr.employee-contracts.destroy'));
        self::assertTrue(Route::has('hr.biometric-devices.index'));
        self::assertTrue(Route::has('hr.biometric-devices.store'));
        self::assertTrue(Route::has('hr.biometric-devices.show'));
        self::assertTrue(Route::has('hr.biometric-devices.update'));
        self::assertTrue(Route::has('hr.biometric-devices.destroy'));
        self::assertTrue(Route::has('hr.holidays.index'));
        self::assertTrue(Route::has('hr.holidays.store'));
        self::assertTrue(Route::has('hr.holidays.show'));
        self::assertTrue(Route::has('hr.holidays.update'));
        self::assertTrue(Route::has('hr.holidays.destroy'));
        self::assertTrue(Route::has('hr.attendance-logs.index'));
        self::assertTrue(Route::has('hr.attendance-logs.store'));
        self::assertTrue(Route::has('hr.attendance-logs.show'));
        self::assertTrue(Route::has('hr.attendance-logs.update'));
        self::assertTrue(Route::has('hr.attendance-logs.destroy'));
        self::assertTrue(Route::has('hr.shifts.index'));
        self::assertTrue(Route::has('hr.shifts.store'));
        self::assertTrue(Route::has('hr.shifts.show'));
        self::assertTrue(Route::has('hr.shifts.update'));
        self::assertTrue(Route::has('hr.shifts.destroy'));
        self::assertTrue(Route::has('hr.shift-assignments.index'));
        self::assertTrue(Route::has('hr.shift-assignments.store'));
        self::assertTrue(Route::has('hr.shift-assignments.show'));
        self::assertTrue(Route::has('hr.shift-assignments.update'));
        self::assertTrue(Route::has('hr.shift-assignments.destroy'));
        self::assertTrue(Route::has('hr.attendance-records.index'));
        self::assertTrue(Route::has('hr.attendance-records.store'));
        self::assertTrue(Route::has('hr.attendance-records.show'));
        self::assertTrue(Route::has('hr.attendance-records.update'));
        self::assertTrue(Route::has('hr.attendance-records.destroy'));
        self::assertTrue(Route::has('hr.leave-types.index'));
        self::assertTrue(Route::has('hr.leave-types.store'));
        self::assertTrue(Route::has('hr.leave-types.show'));
        self::assertTrue(Route::has('hr.leave-types.update'));
        self::assertTrue(Route::has('hr.leave-types.destroy'));
        self::assertTrue(Route::has('hr.leave-policies.index'));
        self::assertTrue(Route::has('hr.leave-policies.store'));
        self::assertTrue(Route::has('hr.leave-policies.show'));
        self::assertTrue(Route::has('hr.leave-policies.update'));
        self::assertTrue(Route::has('hr.leave-policies.destroy'));
        self::assertTrue(Route::has('hr.leave-policy-lines.index'));
        self::assertTrue(Route::has('hr.leave-policy-lines.store'));
        self::assertTrue(Route::has('hr.leave-policy-lines.show'));
        self::assertTrue(Route::has('hr.leave-policy-lines.update'));
        self::assertTrue(Route::has('hr.leave-policy-lines.destroy'));
        self::assertTrue(Route::has('hr.leave-allocations.index'));
        self::assertTrue(Route::has('hr.leave-allocations.store'));
        self::assertTrue(Route::has('hr.leave-allocations.show'));
        self::assertTrue(Route::has('hr.leave-allocations.update'));
        self::assertTrue(Route::has('hr.leave-allocations.destroy'));
        self::assertTrue(Route::has('hr.leave-applications.index'));
        self::assertTrue(Route::has('hr.leave-applications.store'));
        self::assertTrue(Route::has('hr.leave-applications.show'));
        self::assertTrue(Route::has('hr.leave-applications.update'));
        self::assertTrue(Route::has('hr.leave-applications.destroy'));
        self::assertTrue(Route::has('hr.salary-components.index'));
        self::assertTrue(Route::has('hr.salary-components.store'));
        self::assertTrue(Route::has('hr.salary-components.show'));
        self::assertTrue(Route::has('hr.salary-components.update'));
        self::assertTrue(Route::has('hr.salary-components.destroy'));
        self::assertTrue(Route::has('hr.salary-structures.index'));
        self::assertTrue(Route::has('hr.salary-structures.store'));
        self::assertTrue(Route::has('hr.salary-structures.show'));
        self::assertTrue(Route::has('hr.salary-structures.update'));
        self::assertTrue(Route::has('hr.salary-structures.destroy'));
        self::assertTrue(Route::has('hr.salary-structure-lines.index'));
        self::assertTrue(Route::has('hr.salary-structure-lines.store'));
        self::assertTrue(Route::has('hr.salary-structure-lines.show'));
        self::assertTrue(Route::has('hr.salary-structure-lines.update'));
        self::assertTrue(Route::has('hr.salary-structure-lines.destroy'));
        self::assertTrue(Route::has('hr.employee-salary-assignments.index'));
        self::assertTrue(Route::has('hr.employee-salary-assignments.store'));
        self::assertTrue(Route::has('hr.employee-salary-assignments.show'));
        self::assertTrue(Route::has('hr.employee-salary-assignments.update'));
        self::assertTrue(Route::has('hr.employee-salary-assignments.destroy'));
        self::assertTrue(Route::has('hr.payroll-runs.index'));
        self::assertTrue(Route::has('hr.payroll-runs.store'));
        self::assertTrue(Route::has('hr.payroll-runs.show'));
        self::assertTrue(Route::has('hr.payroll-runs.update'));
        self::assertTrue(Route::has('hr.payroll-runs.destroy'));
        self::assertTrue(Route::has('hr.payslips.index'));
        self::assertTrue(Route::has('hr.payslips.store'));
        self::assertTrue(Route::has('hr.payslips.show'));
        self::assertTrue(Route::has('hr.payslips.update'));
        self::assertTrue(Route::has('hr.payslips.destroy'));
        self::assertTrue(Route::has('hr.payslip-lines.index'));
        self::assertTrue(Route::has('hr.payslip-lines.store'));
        self::assertTrue(Route::has('hr.payslip-lines.show'));
        self::assertTrue(Route::has('hr.payslip-lines.update'));
        self::assertTrue(Route::has('hr.payslip-lines.destroy'));
        self::assertTrue(Route::has('hr.performance-cycles.index'));
        self::assertTrue(Route::has('hr.performance-cycles.store'));
        self::assertTrue(Route::has('hr.performance-cycles.show'));
        self::assertTrue(Route::has('hr.performance-cycles.update'));
        self::assertTrue(Route::has('hr.performance-cycles.destroy'));
        self::assertTrue(Route::has('hr.performance-reviews.index'));
        self::assertTrue(Route::has('hr.performance-reviews.store'));
        self::assertTrue(Route::has('hr.performance-reviews.show'));
        self::assertTrue(Route::has('hr.performance-reviews.update'));
        self::assertTrue(Route::has('hr.performance-reviews.destroy'));
    }

    public function testHrRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('hr.departments.index');

        self::assertNotNull($route);

        $middlewares = $route->gatherMiddleware();
        self::assertContains('auth:' . (string) config('module-auth.protected_route_guard', 'auth-api'), $middlewares);
        self::assertContains((string) config('core.current_user.middleware_alias', 'current.user'), $middlewares);
        self::assertContains((string) config('core.current_tenant.middleware_alias', 'current.tenant'), $middlewares);
        self::assertContains(
            (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
            $middlewares,
        );
    }
}
