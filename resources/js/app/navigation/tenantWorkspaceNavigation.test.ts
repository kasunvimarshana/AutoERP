import { describe, expect, it } from 'vitest';
import { tenantWorkspaceNavigationSections } from './tenantWorkspaceNavigation';

describe('tenant workspace Vehicle Rental navigation', () => {
    it('keeps lessee and lessor agreement workspaces without the duplicate generic entry', () => {
        const operations = tenantWorkspaceNavigationSections.find(
            (section) => section.id === 'operations',
        );
        const rental = operations?.items.find(
            (item) => item.id === 'vehicle-rental' && item.type === 'module',
        );

        expect(rental?.type).toBe('module');
        if (!rental || rental.type !== 'module') return;

        const childIds = rental.children.map((child) => child.id);
        expect(childIds).toContain('rental-lessee-agreements');
        expect(childIds).toContain('rental-lessor-agreements');
        expect(childIds).not.toContain('rental-agreements');
    });
});
