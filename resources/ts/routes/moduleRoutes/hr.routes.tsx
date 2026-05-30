import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const dashboard = () => lazyNamed(() => import('../../modules/hr/pages/HrDashboardPage'), 'HrDashboardPage');
const employees = () => import('../../modules/hr/pages/EmployeePages');
const masterData = () => import('../../modules/hr/pages/HrMasterDataPages');
const operational = () => import('../../modules/hr/pages/HrOperationalPages');

export const hrRoutes: RouteObject[] = [
    { element: dashboard(), path: 'hr' },
    { element: lazyNamed(employees, 'EmployeeListPage'), path: 'hr/employees' },
    { element: lazyNamed(employees, 'EmployeeCreatePage'), path: 'hr/employees/new' },
    { element: lazyNamed(employees, 'EmployeeDetailPage'), path: 'hr/employees/:id' },
    { element: lazyNamed(employees, 'EmployeeEditPage'), path: 'hr/employees/:id/edit' },
    { element: lazyNamed(masterData, 'DepartmentListPage'), path: 'hr/departments' },
    { element: lazyNamed(masterData, 'DepartmentCreatePage'), path: 'hr/departments/new' },
    { element: lazyNamed(masterData, 'DepartmentEditPage'), path: 'hr/departments/:id/edit' },
    { element: lazyNamed(masterData, 'DesignationListPage'), path: 'hr/designations' },
    { element: lazyNamed(masterData, 'DesignationCreatePage'), path: 'hr/designations/new' },
    { element: lazyNamed(masterData, 'DesignationEditPage'), path: 'hr/designations/:id/edit' },
    { element: lazyNamed(masterData, 'EmploymentTypeListPage'), path: 'hr/employment-types' },
    { element: lazyNamed(operational, 'EmployeeLookupPage'), path: 'hr/employee-lookup' },
    { element: lazyNamed(operational, 'AttendanceListPage'), path: 'hr/attendance' },
    { element: lazyNamed(operational, 'LeaveListPage'), path: 'hr/leave' },
    { element: lazyNamed(operational, 'SalaryProfileListPage'), path: 'hr/salary-profiles' },
];
