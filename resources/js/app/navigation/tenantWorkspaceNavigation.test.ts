import { describe, expect, it } from 'vitest';
import { tenantNavigationSections } from './navigationConfig';
import type { NavigationSection } from './navigationTypes';
import { tenantWorkspaceNavigationSections } from './tenantWorkspaceNavigation';

const OPERATIONS_SECTION_ID = 'operations';
const VEHICLE_RENTAL_ITEM_ID = 'vehicle-rental';
const RENTAL_CUSTODY_ITEM_ID = 'rental-custody';
const RENTAL_BILLING_ITEM_ID = 'rental-billing';
const REDUNDANT_RENTAL_CHILD_IDS = [
    'rental-agreements',
    'owner-payables',
    'rental-invoices',
    'rental-settlements',
] as const;

function vehicleRentalChildren(sections: NavigationSection[]) {
    const operations = sections.find(
        (section) => section.id === OPERATIONS_SECTION_ID,
    );
    const rental = operations?.items.find(
        (item) => item.id === VEHICLE_RENTAL_ITEM_ID && item.type === 'module',
    );

    expect(rental?.type).toBe('module');
    if (!rental || rental.type !== 'module') return [];

    return rental.children;
}

describe('tenant workspace Vehicle Rental navigation', () => {
    it('defines the canonical Rental navigation directly in the base configuration', () => {
        const baseChildren = vehicleRentalChildren(tenantNavigationSections);
        const workspaceChildren = vehicleRentalChildren(
            tenantWorkspaceNavigationSections,
        );
        const childIds = baseChildren.map((child) => child.id);
        const custody = baseChildren.find(
            (child) => child.id === RENTAL_CUSTODY_ITEM_ID,
        );
        const billing = baseChildren.find(
            (child) => child.id === RENTAL_BILLING_ITEM_ID,
        );

        expect(childIds).toContain('rental-lessee-agreements');
        expect(childIds).toContain('rental-lessor-agreements');
        expect(childIds).not.toEqual(
            expect.arrayContaining([...REDUNDANT_RENTAL_CHILD_IDS]),
        );
        expect(custody?.label).toBe('Handover & Return Queue');
        expect(billing?.label).toBe('Billing & Settlement');
        expect(workspaceChildren).toEqual(baseChildren);
    });
});
