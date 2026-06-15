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
            roles: [],
            permissions: [],
            enabledModules: null,
        });

        expect(sections.flatMap((section) => section.items).map((item) => item.id)).toEqual(['dashboard']);
    });

    it('uses enabled tenant modules and permission prefixes without treating navigation as authorization', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: 10,
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

    it('keeps legacy tenant navigation visible when no permission catalogue is assigned', () => {
        const sections = filterNavigation(navigationSections, {
            tenantId: 10,
            roles: [],
            permissions: [],
            enabledModules: null,
        });
        const itemIds = sections.flatMap((section) => section.items).map((item) => item.id);

        expect(itemIds).toContain('purchase');
        expect(itemIds).toContain('vehicle-rental');
        expect(itemIds).toContain('users');
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
});
