import { TENANT_MODULE_CODE } from '@/app/access/tenantModules';
import { hrPermissions } from '@/modules/hr/hrPermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

const HR_MODULES = [TENANT_MODULE_CODE.HR] as const;

export const hrRouteEntitlements: readonly EntitlementRule[] = [
    operational('/hr/employees/create', HR_MODULES, [hrPermissions.employeesCreate]),
    operational('/hr/employees/:id/edit', HR_MODULES, [hrPermissions.employeesUpdate]),
    operational('/hr/employees/:id', HR_MODULES, [hrPermissions.employeesView]),
    operational('/hr/employees', HR_MODULES, [hrPermissions.employeesView]),
];
