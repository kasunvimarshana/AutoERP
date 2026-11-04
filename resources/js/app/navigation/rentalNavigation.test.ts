import { describe, expect, it } from 'vitest';
import { rentalNavigationItem } from './rentalNavigation';

describe('Vehicle Rental navigation', () => {
    it('follows the agreement-first operator workflow', () => {
        expect(rentalNavigationItem.children?.map(item => item.label)).toEqual([
            'Operations Dashboard',
            'Reservations',
            'Customer Agreements',
            'Owner Agreements',
            'Vehicle Use Register',
            'Running Chart Register',
            'Fleet Expenses',
            'Vehicle Finance',
            'Incident Evidence',
            'Vehicle Availability',
            'Reports & Matrix Sheet',
        ]);
    });
});
