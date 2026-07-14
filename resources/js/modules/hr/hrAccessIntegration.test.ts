import { describe, expect, it } from 'vitest';
import { resolveTenantRouteEntitlement } from '@/app/access/resolvedRouteEntitlements';
import { TENANT_MODULE_CODE } from '@/app/access/tenantModules';
import { tenantWorkspaceNavigationSections } from '@/app/navigation/tenantWorkspaceNavigation';
import { filterNavigation } from '@/app/navigation/navigationUtils';
import { hrPermissions } from './hrPermissions';

function visibleHrChildren(permissions: string[], enabledModules: string[] = [TENANT_MODULE_CODE.HR]): string[] {
    const sections = filterNavigation(tenantWorkspaceNavigationSections, {
        tenantId: 10,
        isPlatformOperator: false,
        organizationUnitId: 20,
        roles: [],
        permissions,
        permissionsLoaded: true,
        enabledModules,
        enabledModulesLoaded: true,
    });
    const hrModule = sections
        .flatMap((section) => section.items)
        .find((item) => item.id === 'human-resources');

    return hrModule?.type === 'module'
        ? hrModule.children.map((child) => child.label)
        : [];
}

describe('HR frontend access integration', () => {
    it('renders employee navigation from the enabled HR module and exact permissions', () => {
        expect(visibleHrChildren([hrPermissions.employeesView])).toEqual(['Employees']);
        expect(visibleHrChildren([hrPermissions.employeesCreate])).toEqual(['Create Employee']);
        expect(visibleHrChildren([
            hrPermissions.employeesView,
            hrPermissions.employeesCreate,
        ])).toEqual(['Employees', 'Create Employee']);
    });

    it('hides HR navigation when the tenant plan does not enable HR', () => {
        expect(visibleHrChildren([hrPermissions.employeesView], [])).toEqual([]);
    });

    it('protects each HR page with its owning backend permission', () => {
        expect(resolveTenantRouteEntitlement('/hr/employees')).toMatchObject({
            modules: [TENANT_MODULE_CODE.HR],
            permissions: [hrPermissions.employeesView],
            requiresOrganizationUnit: true,
        });
        expect(resolveTenantRouteEntitlement('/hr/employees/1')).toMatchObject({
            permissions: [hrPermissions.employeesView],
        });
        expect(resolveTenantRouteEntitlement('/hr/employees/create')).toMatchObject({
            permissions: [hrPermissions.employeesCreate],
        });
        expect(resolveTenantRouteEntitlement('/hr/employees/1/edit')).toMatchObject({
            permissions: [hrPermissions.employeesUpdate],
        });
    });
});
