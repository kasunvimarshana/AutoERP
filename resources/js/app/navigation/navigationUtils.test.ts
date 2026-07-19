import { describe, expect, it } from 'vitest';
import { itemPermissions } from '@/modules/item/itemPermissions';
import { financePermissions } from '@/modules/finance/financePermissions';
import { inventoryPermissions } from '@/modules/inventory/inventoryPermissions';
import { invoicePermissions } from '@/modules/invoice/invoicePermissions';
import { purchasePermissions } from '@/modules/purchase/purchasePermissions';
import { tenantNavigationSections } from './navigationConfig';
import {
    filterNavigation,
    findNavigationMatch,
} from './navigationUtils';

describe('navigation access and matching', () => {
    it('hides tenant workspaces when tenant context is missing', () => {
        const sections = filterNavigation(tenantNavigationSections, {
            tenantId: null,
            isPlatformOperator: false,
            organizationUnitId: null,
            roles: [],
            permissions: [],
            permissionsLoaded: true,
            enabledModules: null,
            enabledModulesLoaded: true,
        });

        expect(sections.flatMap((section) => section.items).map((item) => item.id)).toEqual(['dashboard']);
    });

    it('uses enabled tenant modules and exact permissions without treating navigation as authorization', () => {
        const sections = filterNavigation(tenantNavigationSections, {
            tenantId: 10,
            isPlatformOperator: false,
            organizationUnitId: 20,
            roles: [],
            permissions: [purchasePermissions.ordersView, invoicePermissions.view],
            permissionsLoaded: true,
            enabledModules: ['purchase', 'invoice', 'payment'],
            enabledModulesLoaded: true,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).toContain('purchase');
        expect(itemIds).toContain('invoices');
        expect(itemIds).not.toContain('inventory');
        expect(itemIds).not.toContain('users');
    });

    it('fails closed when enabled-module data is unavailable', () => {
        const sections = filterNavigation(tenantNavigationSections, {
            tenantId: 10,
            isPlatformOperator: false,
            organizationUnitId: 20,
            roles: [],
            permissions: [],
            permissionsLoaded: true,
            enabledModules: null,
            enabledModulesLoaded: true,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).toContain('dashboard');
        expect(itemIds).not.toContain('purchase');
        expect(itemIds).not.toContain('items');
        expect(itemIds).not.toContain('vehicle');
        expect(itemIds).not.toContain('users');
        expect(itemIds).not.toContain('users-access');
    });

    it('does not grant permission-gated navigation while permissions are loading', () => {
        const sections = filterNavigation(tenantNavigationSections, {
            tenantId: 10,
            isPlatformOperator: false,
            organizationUnitId: 20,
            roles: [],
            permissions: [purchasePermissions.ordersView, itemPermissions.view],
            permissionsLoaded: false,
            enabledModules: ['purchase', 'item', 'vehicle'],
            enabledModulesLoaded: true,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).not.toContain('purchase');
        expect(itemIds).not.toContain('items');
        expect(itemIds).not.toContain('vehicle');
    });

    it('hides organization workflows when no branch or organization unit is selected', () => {
        const sections = filterNavigation(tenantNavigationSections, {
            tenantId: 10,
            isPlatformOperator: false,
            organizationUnitId: null,
            roles: [],
            permissions: [purchasePermissions.ordersView, 'suppliers.view', 'vehicle.view'],
            permissionsLoaded: true,
            enabledModules: ['purchase', 'supplier', 'vehicle'],
            enabledModulesLoaded: true,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).not.toContain('purchase');
        expect(itemIds).not.toContain('suppliers');
        expect(itemIds).not.toContain('vehicle');
    });

    it('selects vehicle master-data children before the broader vehicle list route', () => {
        const match = findNavigationMatch('/vehicles/models', '', tenantNavigationSections);

        expect(match?.parent?.id).toBe('vehicle');
        expect(match?.item.id).toBe('vehicle-models');
    });

    it('hides the vehicle parent when the vehicle module is unavailable', () => {
        const sections = filterNavigation(tenantNavigationSections, {
            tenantId: 10,
            isPlatformOperator: false,
            organizationUnitId: 20,
            roles: [],
            permissions: [],
            permissionsLoaded: true,
            enabledModules: ['supplier', 'customer'],
            enabledModulesLoaded: true,
        });

        expect(sections.flatMap((section) => section.items).map((item) => item.id)).not.toContain('vehicle');
    });

    it('filters item navigation children by exact permissions', () => {
        const sections = filterNavigation(tenantNavigationSections, {
            tenantId: 10,
            isPlatformOperator: false,
            organizationUnitId: 20,
            roles: [],
            permissions: ['item.view'],
            permissionsLoaded: true,
            enabledModules: ['item'],
            enabledModulesLoaded: true,
        });
        const itemModule = sections
            .flatMap((section) => section.items)
            .find((item) => item.id === 'items');

        expect(itemModule?.type).toBe('module');
        expect(itemModule?.type === 'module' ? itemModule.children.map((child) => child.label) : []).toEqual(['Items']);
    });

    it('shows finance navigation through feature-owned route entitlements', () => {
        const sections = filterNavigation(tenantNavigationSections, {
            tenantId: 10,
            isPlatformOperator: false,
            organizationUnitId: 20,
            roles: [],
            permissions: [
                financePermissions.accountsView,
                financePermissions.reportsView,
            ],
            permissionsLoaded: true,
            enabledModules: ['finance'],
            enabledModulesLoaded: true,
        });
        const financeModule = sections
            .flatMap((section) => section.items)
            .find((item) => item.id === 'finance-workspace');

        expect(financeModule?.type).toBe('module');
        expect(financeModule?.type === 'module' ? financeModule.children.map((child) => child.label) : []).toEqual([
            'Chart of Accounts',
            'General Ledger',
            'Trial Balance',
            'Account Balances',
            'Financial Reports',
        ]);
    });

    it('requires an Inventory permission before showing Inventory navigation', () => {
        const withoutPermission = filterNavigation(tenantNavigationSections, {
            tenantId: 10,
            isPlatformOperator: false,
            organizationUnitId: 20,
            roles: [],
            permissions: [],
            permissionsLoaded: true,
            enabledModules: ['inventory'],
            enabledModulesLoaded: true,
        });
        expect(withoutPermission.flatMap((section) => section.items).some((item) => item.id === 'inventory')).toBe(false);

        const withPermission = filterNavigation(tenantNavigationSections, {
            tenantId: 10,
            isPlatformOperator: false,
            organizationUnitId: 20,
            roles: [],
            permissions: [inventoryPermissions.stockView],
            permissionsLoaded: true,
            enabledModules: ['inventory'],
            enabledModulesLoaded: true,
        });
        const inventoryModule = withPermission
            .flatMap((section) => section.items)
            .find((item) => item.id === 'inventory');

        expect(inventoryModule?.type).toBe('module');
        expect(inventoryModule?.type === 'module' ? inventoryModule.children.map((child) => child.label) : []).toEqual(['Inventory']);
        expect(findNavigationMatch('/inventory', '', tenantNavigationSections)?.parent?.id).toBe('inventory');
    });

    it('preserves navigation-specific module constraints for shared route paths', () => {
        const sections = filterNavigation(tenantNavigationSections, {
            tenantId: 10,
            isPlatformOperator: false,
            organizationUnitId: 20,
            roles: [],
            permissions: [invoicePermissions.view],
            permissionsLoaded: true,
            enabledModules: ['invoice'],
            enabledModulesLoaded: true,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).toContain('invoices');
        expect(itemIds).not.toContain('vehicle-service');
    });

    it('selects the query-specific child for shared invoice routes', () => {
        const match = findNavigationMatch('/invoices', '?view=service', tenantNavigationSections);

        expect(match?.parent?.id).toBe('vehicle-service');
        expect(match?.item.id).toBe('service-invoices');
    });

    it('keeps the primary business hierarchy without brittle duplicate snapshots', () => {
        expect(tenantNavigationSections.map((section) => section.label ?? '')).toEqual([
            '',
            'Master Data',
            'Access Control',
            'Operations',
            'Finance',
            'Administration',
        ]);

        const administration = tenantNavigationSections.find((section) => section.label === 'Administration');
        expect(administration?.items.map((item) => item.label)).toEqual([
            'Users & Access',
            'Organization Units',
            'Tenant Administration',
            'Audit Logs',
            'Reference Data',
            'Settings',
        ]);

        const items = tenantNavigationSections
            .flatMap((section) => section.items)
            .find((item) => item.label === 'Items');
        expect(items?.type).toBe('module');
        expect(items?.type === 'module' ? items.children.map((child) => child.label) : []).toEqual([
            'Categories',
            'Create Category',
            'Brands',
            'Create Brand',
            'Items',
            'Create Item',
        ]);
    });
});
