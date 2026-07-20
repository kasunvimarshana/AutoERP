import { describe, expect, it } from 'vitest';
import { resolveTenantRouteEntitlement } from '@/app/access/resolvedRouteEntitlements';
import { tenantWorkspaceNavigationSections } from '@/app/navigation/tenantWorkspaceNavigation';
import { filterNavigation } from '@/app/navigation/navigationUtils';
import { vehicleRentalPermissions } from './vehicleRentalPermissions';

const expectedPrimaryLinks = [
    '/vehicle-rental',
    '/vehicle-rental/owner-agreements',
    '/vehicle-rental/customer-agreements',
    '/vehicle-rental/running-charts',
    '/vehicle-rental/customer-invoices',
    '/vehicle-rental/owner-settlements',
    '/vehicle-rental/customer-receipts',
    '/vehicle-rental/owner-payments',
    '/vehicle-rental/reports',
];

const navigationContext = {
    tenantId: 1,
    isPlatformOperator: false,
    organizationUnitId: 1,
    roles: [],
    permissionsLoaded: true,
    enabledModules: ['vehicle-rental'],
    enabledModulesLoaded: true,
};

function rentalModule(permissions: string[]) {
    const sections = filterNavigation(tenantWorkspaceNavigationSections, {
        ...navigationContext,
        permissions,
    });
    const operations = sections.find((section) => section.id === 'operations');
    return operations?.items.find((item) => item.id === 'vehicle-rental');
}

describe('Vehicle Rental frontend entry', () => {
    it('registers the tenant-enabled Vehicle Rental navigation module', () => {
        const operations = tenantWorkspaceNavigationSections.find((section) => section.id === 'operations');
        const module = operations?.items.find((item) => item.id === 'vehicle-rental');

        expect(module?.type).toBe('module');
        if (!module || module.type !== 'module') throw new Error('Vehicle Rental navigation module is missing.');

        expect(module.access?.modules).toContain('vehicle-rental');
        expect(module.children.map((child) => child.to)).toEqual(expectedPrimaryLinks);
    });

    it('renders only agreement workspaces for an agreement reader', () => {
        expect(rentalModule([])).toBeUndefined();

        const module = rentalModule([vehicleRentalPermissions.agreementsView]);
        expect(module?.type).toBe('module');
        if (!module || module.type !== 'module') throw new Error('Authorized Vehicle Rental navigation is missing.');

        expect(module.children.map((child) => child.to)).toEqual([
            '/vehicle-rental',
            '/vehicle-rental/owner-agreements',
            '/vehicle-rental/customer-agreements',
        ]);
    });

    it('renders the financial workflow for a calculation reader', () => {
        const module = rentalModule([vehicleRentalPermissions.calculationsView]);
        expect(module?.type).toBe('module');
        if (!module || module.type !== 'module') throw new Error('Authorized Vehicle Rental navigation is missing.');

        expect(module.children.map((child) => child.to)).toEqual([
            '/vehicle-rental',
            '/vehicle-rental/customer-invoices',
            '/vehicle-rental/owner-settlements',
            '/vehicle-rental/customer-receipts',
            '/vehicle-rental/owner-payments',
            '/vehicle-rental/reports',
        ]);
    });

    it.each([
        ['/vehicle-rental/owner-agreements', vehicleRentalPermissions.agreementsView],
        ['/vehicle-rental/customer-agreements', vehicleRentalPermissions.agreementsView],
        ['/vehicle-rental/agreements', vehicleRentalPermissions.agreementsView],
        ['/vehicle-rental/assignments', vehicleRentalPermissions.assignmentsView],
        ['/vehicle-rental/running-charts', vehicleRentalPermissions.runningChartsView],
        ['/vehicle-rental/customer-invoices', vehicleRentalPermissions.calculationsView],
        ['/vehicle-rental/owner-settlements', vehicleRentalPermissions.calculationsView],
        ['/vehicle-rental/customer-receipts', vehicleRentalPermissions.calculationsView],
        ['/vehicle-rental/owner-payments', vehicleRentalPermissions.calculationsView],
        ['/vehicle-rental/reports', vehicleRentalPermissions.calculationsView],
        ['/vehicle-rental/calculations', vehicleRentalPermissions.calculationsView],
    ])('protects %s with the Vehicle Rental module and owned permission', (path, permission) => {
        const entitlement = resolveTenantRouteEntitlement(path);

        expect(entitlement?.modules).toEqual(['vehicle-rental']);
        expect(entitlement?.requiresOrganizationUnit).toBe(true);
        expect(entitlement?.permissions).toEqual([permission]);
    });
});
