/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { TENANT_MODULE_CODE } from '@/app/access/tenantModules';
import { hrPermissions } from '@/modules/hr/hrPermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

const HR_MODULES = [TENANT_MODULE_CODE.HR] as const;

export const hrWorkspaceViewPermissions = [
    hrPermissions.employeesView,
    hrPermissions.masterDataView,
    hrPermissions.masterDataManage,
] as const;

export const hrRouteEntitlements: readonly EntitlementRule[] = [
    operational('/hr/employees/create', HR_MODULES, [hrPermissions.employeesCreate]),
    operational('/hr/employees/:id/edit', HR_MODULES, [hrPermissions.employeesUpdate]),
    operational('/hr/employees/:id', HR_MODULES, [hrPermissions.employeesView]),
    operational('/hr/employees', HR_MODULES, hrWorkspaceViewPermissions),
    operational('/hr/roster', HR_MODULES, [hrPermissions.shiftsView]),
    operational('/hr/attendance', HR_MODULES, [hrPermissions.attendanceView]),
    operational('/hr/leaves', HR_MODULES, [hrPermissions.leavesView]),
    operational('/hr/biometric-devices', HR_MODULES, [hrPermissions.biometricView]),
    operational('/payroll/structures', HR_MODULES, ['payroll.structures.manage', 'payroll.view']),
    operational('/payroll/pay-runs', HR_MODULES, ['payroll.view', 'payroll.pay-runs.calculate']),
    operational('/payroll/loans', HR_MODULES, ['payroll.view']),
    operational('/payroll/payslips', HR_MODULES, ['payroll.payslips.view', 'payroll.view']),
];
