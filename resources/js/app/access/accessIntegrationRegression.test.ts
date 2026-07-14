import { describe, expect, it } from 'vitest';
import { inventoryPermissions } from '@/modules/inventory/inventoryPermissions';
import { financePermissions } from '@/modules/finance/financePermissions';
import { hrPermissions } from '@/modules/hr/hrPermissions';
import { uomPermissions } from '@/modules/uom/uomPermissions';
import { tenantWorkspaceNavigationSections } from '@/app/navigation/tenantWorkspaceNavigation';
import { filterNavigation } from '@/app/navigation/navigationUtils';
import { resolveTenantRouteEntitlement } from './resolvedRouteEntitlements';

function visibleItemLabels(permissions: string[], enabledModules: string[]): string[] {
    return filterNavigation(tenantWorkspaceNavigationSections, {
        tenantId: 10,
        isPlatformOperator: false,
        organizationUnitId: 20,
        roles: [],
        permissions,
        permissionsLoaded: true,
        enabledModules,
        enabledModulesLoaded: true,
    }).flatMap((section) => section.items.flatMap((item) => (
        item.type === 'module'
            ? [item.label, ...item.children.map((child) => child.label)]
            : [item.label]
    )));
}

describe('tenant access integration regressions', () => {
    it('does not expose Inventory without an Inventory permission', () => {
        expect(visibleItemLabels([], ['inventory'])).not.toContain('Inventory');
        expect(visibleItemLabels([inventoryPermissions.stockView], ['inventory'])).toContain('Inventory');
        expect(resolveTenantRouteEntitlement('/inventory')?.permissions).toContain(inventoryPermissions.stockView);
    });

    it('exposes UOM workspaces only for their owning permissions', () => {
        const labels = visibleItemLabels([uomPermissions.view], []);

        expect(labels).toContain('Units of Measure');
        expect(labels).toContain('Units');
        expect(labels).not.toContain('Create Unit');
        expect(resolveTenantRouteEntitlement('/uoms/create')?.permissions).toEqual([uomPermissions.create]);
    });

    it('exposes HR master data independently from employee access', () => {
        const labels = visibleItemLabels([hrPermissions.masterDataView], ['hr']);

        expect(labels).toContain('Human Resources');
        expect(labels).toContain('Departments');
        expect(labels).not.toContain('Employees');
    });

    it('requires a financial source-view permission for vouchers', () => {
        expect(visibleItemLabels([], [])).not.toContain('Vouchers');
        expect(visibleItemLabels([financePermissions.journalsView], ['finance'])).toContain('Vouchers');
        expect(resolveTenantRouteEntitlement('/vouchers')?.permissions).toContain(financePermissions.journalsView);
    });
});
