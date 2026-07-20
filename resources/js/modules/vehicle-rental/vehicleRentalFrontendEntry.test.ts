import { describe, expect, it } from 'vitest';
import { resolveTenantRouteEntitlement } from '@/app/access/resolvedRouteEntitlements';
import { tenantWorkspaceNavigationSections } from '@/app/navigation/tenantWorkspaceNavigation';
import { vehicleRentalPermissions } from './vehicleRentalPermissions';

const expectedLinks = [
    '/vehicle-rental',
    '/vehicle-rental/agreements',
    '/vehicle-rental/assignments',
    '/vehicle-rental/running-charts',
    '/vehicle-rental/calculations',
];

describe('Vehicle Rental frontend entry', () => {
    it('registers the tenant-enabled Vehicle Rental navigation module', () => {
        const operations = tenantWorkspaceNavigationSections.find((section) => section.id === 'operations');
        const module = operations?.items.find((item) => item.id === 'vehicle-rental');

        expect(module?.type).toBe('module');
        if (!module || module.type !== 'module') throw new Error('Vehicle Rental navigation module is missing.');

        expect(module.access?.modules).toContain('vehicle-rental');
        expect(module.children.map((child) => child.to)).toEqual(expectedLinks);
    });

    it.each([
        ['/vehicle-rental/agreements', vehicleRentalPermissions.agreementsView],
        ['/vehicle-rental/assignments', vehicleRentalPermissions.assignmentsView],
        ['/vehicle-rental/running-charts', vehicleRentalPermissions.runningChartsView],
        ['/vehicle-rental/calculations', vehicleRentalPermissions.calculationsView],
    ])('protects %s with the Vehicle Rental module and owned permission', (path, permission) => {
        const entitlement = resolveTenantRouteEntitlement(path);

        expect(entitlement?.modules).toEqual(['vehicle-rental']);
        expect(entitlement?.requiresOrganizationUnit).toBe(true);
        expect(entitlement?.permissions).toEqual([permission]);
    });
});
