import { describe, expect, it } from 'vitest';
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

    it('uses enabled tenant modules and permission prefixes without treating navigation as authorization', () => {
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

    it('keeps ordinary tenant navigation visible while hiding role-restricted access control', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: 10,
            organizationUnitId: 20,
            roles: [],
            permissions: [],
            enabledModules: null,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).toContain('purchase');
        expect(itemIds).toContain('vehicle-rental');
        expect(itemIds).not.toContain('users');
        expect(itemIds).not.toContain('users-access');
    });

    it('hides organization workflows when no branch or organization unit is selected', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: 10,
            organizationUnitId: null,
            roles: [],
            permissions: [],
            enabledModules: null,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).not.toContain('purchase');
        expect(itemIds).not.toContain('sales');
        expect(itemIds).toContain('suppliers');
    });

    it('selects the query-specific child for shared invoice routes', () => {
        const match = findNavigationMatch('/invoices', '?view=service', navigationSections);

        expect(match?.parent?.id).toBe('vehicle-service');
        expect(match?.item.id).toBe('service-invoices');
    });

    it('selects the requested agreement direction', () => {
        const match = findNavigationMatch(
            '/vehicle-rental/agreements',
            '?direction=inbound',
            navigationSections,
        );

        expect(match?.parent?.id).toBe('vehicle-rental');
        expect(match?.item.id).toBe('owner-agreements');
    });

    it('selects the requested running chart mode', () => {
        const match = findNavigationMatch(
            '/vehicle-rental/running-chart',
            '?mode=linked',
            navigationSections,
        );

        expect(match?.parent?.id).toBe('vehicle-rental');
        expect(match?.item.id).toBe('linked-running-charts');
    });

    it('keeps the requested business hierarchy and excludes action links', () => {
        const labels = navigationSections.map((section) => ({
            section: section.label ?? '',
            items: section.items.map((item) => ({
                label: item.label,
                children: item.type === 'module' ? item.children.map((child) => child.label) : [],
            })),
        }));

        expect(labels).toEqual([
            { section: '', items: [{ label: 'Dashboard', children: [] }] },
            { section: 'Master Data', items: [
                { label: 'Suppliers', children: ['Supplier List', 'Supplier Vehicles'] },
                { label: 'Customers', children: ['Customer List', 'Customer Vehicles'] },
                { label: 'Items', children: ['Item List'] },
            ] },
            { section: 'Access Control', items: [
                { label: 'Users', children: ['User List', 'Roles', 'Permissions'] },
            ] },
            { section: 'Operations', items: [
                { label: 'Purchase', children: ['Fast Purchase', 'Purchase Orders', 'Goods Receipts', 'Purchase Returns', 'Supplier Invoices', 'Supplier Payments'] },
                { label: 'Sales', children: ['Fast Sales', 'Sales Orders', 'Goods Deliveries', 'Sales Returns', 'Customer Invoices', 'Customer Receipts'] },
                { label: 'Vehicle Service', children: ['Service Jobs', 'Service Invoices', 'Customer Receipts'] },
                { label: 'Vehicle Rental', children: ['Owner / Supplier Agreements', 'Customer Agreements', 'Customer Running Charts', 'Owner / Supplier Running Charts', 'Linked Running Charts', 'Owner / Supplier Payables', 'Customer Invoices', 'Settlements'] },
            ] },
            { section: 'Finance', items: [
                { label: 'Invoices', children: [] },
                { label: 'Payments', children: [] },
                { label: 'Vouchers', children: [] },
            ] },
            { section: 'Administration', items: [
                { label: 'Users & Access', children: [] },
                { label: 'Settings', children: [] },
            ] },
        ]);

        const allLabels = labels.flatMap((section) => section.items.flatMap((item) => [item.label, ...item.children]));
        expect(allLabels.some((label) => /^(create|edit|view|approve|print|\+)/i.test(label))).toBe(false);
    });
});
