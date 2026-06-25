import { describe, expect, it } from 'vitest';
import { itemPermissions } from '@/modules/item/itemPermissions';
import { purchasePermissions } from '@/modules/purchase/purchasePermissions';
import { settingsPermissions } from '@/modules/settings/settingsPermissions';
import { navigationSections } from './navigationConfig';
import {
    filterNavigation,
    findNavigationMatch,
} from './navigationUtils';

describe('navigation access and matching', () => {
    it('hides tenant workspaces when tenant context is missing', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: null,
            organizationUnitId: null,
            roles: [],
            permissions: [],
            enabledModules: null,
        });

        expect(sections.flatMap((section) => section.items).map((item) => item.id)).toEqual(['dashboard']);
    });

    it('uses enabled tenant modules and exact permissions without treating navigation as authorization', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: 10,
            organizationUnitId: 20,
            roles: [],
            permissions: ['vehicle-rental.reservations.manage'],
            enabledModules: ['VehicleRental', 'Invoice', 'Payment'],
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).toContain('vehicle-rental');
        expect(itemIds).toContain('invoices');
        expect(itemIds).not.toContain('purchase');
        expect(itemIds).not.toContain('users');
    });

    it('keeps module-only tenant navigation visible while hiding permission-gated access', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: 10,
            organizationUnitId: 20,
            roles: [],
            permissions: [],
            enabledModules: null,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).not.toContain('purchase');
        expect(itemIds).not.toContain('items');
        expect(itemIds).not.toContain('vehicle-rental');
        expect(itemIds).toContain('sales');
        expect(itemIds).toContain('vehicle');
        expect(itemIds).not.toContain('users');
        expect(itemIds).not.toContain('users-access');
    });

    it('shows platform defaults only with its exact permission', () => {
        const withoutPermission = filterNavigation(navigationSections, {
            tenantId: 10,
            organizationUnitId: 20,
            roles: [],
            permissions: [],
            enabledModules: null,
        });
        const withPermission = filterNavigation(navigationSections, {
            tenantId: 10,
            organizationUnitId: 20,
            roles: [],
            permissions: [settingsPermissions.platformDefaultsView],
            enabledModules: null,
        });

        expect(withoutPermission.flatMap((section) => section.items).map((item) => item.id))
            .not.toContain('platform-defaults');
        expect(withPermission.flatMap((section) => section.items).map((item) => item.id))
            .toContain('platform-defaults');
    });

    it('does not grant permission-gated navigation while permissions are loading', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: 10,
            organizationUnitId: 20,
            roles: [],
            permissions: [purchasePermissions.ordersView, itemPermissions.view],
            permissionsLoaded: false,
            enabledModules: null,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).not.toContain('purchase');
        expect(itemIds).not.toContain('items');
        expect(itemIds).toContain('vehicle');
    });

    it('hides organization workflows when no branch or organization unit is selected', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: 10,
            organizationUnitId: null,
            roles: [],
            permissions: [purchasePermissions.ordersView, 'suppliers.view'],
            enabledModules: null,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).not.toContain('purchase');
        expect(itemIds).not.toContain('sales');
        expect(itemIds).toContain('suppliers');
        expect(itemIds).toContain('vehicle');
    });

    it('selects vehicle master-data children before the broader vehicle list route', () => {
        const match = findNavigationMatch('/vehicles/models', '', navigationSections);

        expect(match?.parent?.id).toBe('vehicle');
        expect(match?.item.id).toBe('vehicle-models');
    });

    it('hides the vehicle parent when the vehicle module is unavailable', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: 10,
            organizationUnitId: 20,
            roles: [],
            permissions: [],
            enabledModules: ['supplier', 'customer'],
        });

        expect(sections.flatMap((section) => section.items).map((item) => item.id)).not.toContain('vehicle');
    });

    it('filters item navigation children by exact permissions', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: 10,
            organizationUnitId: 20,
            roles: [],
            permissions: ['item.view'],
            enabledModules: ['item'],
        });
        const itemModule = sections
            .flatMap((section) => section.items)
            .find((item) => item.id === 'items');

        expect(itemModule?.type).toBe('module');
        expect(itemModule?.type === 'module' ? itemModule.children.map((child) => child.label) : []).toEqual([
            'Categories',
            'Brands',
            'Items',
        ]);
    });

    it('selects the query-specific child for shared invoice routes', () => {
        const match = findNavigationMatch('/invoices', '?view=service', navigationSections);

        expect(match?.parent?.id).toBe('vehicle-service');
        expect(match?.item.id).toBe('service-invoices');
    });

    it('keeps agreement query state within the consolidated agreement workspace', () => {
        const match = findNavigationMatch(
            '/vehicle-rental/agreements',
            '?direction=inbound',
            navigationSections,
        );

        expect(match?.parent?.id).toBe('vehicle-rental');
        expect(match?.item.id).toBe('rental-agreements');
    });

    it('keeps running-chart query state within the consolidated chart workspace', () => {
        const match = findNavigationMatch(
            '/vehicle-rental/running-chart',
            '?mode=linked',
            navigationSections,
        );

        expect(match?.parent?.id).toBe('vehicle-rental');
        expect(match?.item.id).toBe('rental-running-chart');
    });

    it('keeps the requested business hierarchy', () => {
        expect(navigationSections.map((section) => section.label ?? '')).toEqual([
            '',
            'Master Data',
            'Access Control',
            'Operations',
            'Finance',
            'Administration',
        ]);

        const operations = navigationSections.find((section) => section.label === 'Operations');
        expect(operations?.items.map((item) => item.label)).toEqual([
            'Warehouses',
            'Purchase',
            'Sales',
            'Vehicle Service',
            'Vehicle Rental',
        ]);

        const rental = operations?.items.find((item) => item.id === 'vehicle-rental');
        expect(rental?.type).toBe('module');
        expect(rental?.type === 'module' ? rental.children.map((child) => child.label) : []).toEqual([
            'Overview',
            'Reservations',
            'Agreements',
            'Vehicle Allocations',
            'Handover & Return',
            'Daily Running Chart',
            'Expenses & Deductions',
            'Billing & Owner Cost',
            'Security Deposits',
            'Vehicle Finance',
            'Vehicle Availability',
            'Rental Reports',
            'Owner Payables',
            'Customer Invoices',
            'Settlements',
        ]);

        const administration = navigationSections.find((section) => section.label === 'Administration');
        expect(administration?.items.map((item) => item.label)).toEqual([
            'Users & Access',
            'Tenant Administration',
            'SaaS Tenants',
            'Tenant Plans',
            'Platform Defaults',
            'Audit Logs',
            'Reference Data',
            'Settings',
        ]);

        const items = navigationSections
            .flatMap((section) => section.items)
            .find((item) => item.label === 'Items');
        const itemLabels = items?.type === 'module'
            ? items.children.map((child) => child.label)
            : [];
        expect(itemLabels).toContain('Create Brand');
        expect(itemLabels).toContain('Create Item');
    });
});
