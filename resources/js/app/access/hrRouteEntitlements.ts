import { hrPermissions } from '@/modules/hr/hrPermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

export const hrRouteEntitlements: readonly EntitlementRule[] = [
    operational('/hr/employees/create', ['hr'], [hrPermissions.employeesCreate]),
    operational('/hr/employees/:id/edit', ['hr'], [hrPermissions.employeesUpdate]),
    operational('/hr/employees/:id', ['hr'], [hrPermissions.employeesView]),
    operational('/hr/employees', ['hr'], [hrPermissions.employeesView]),
];
