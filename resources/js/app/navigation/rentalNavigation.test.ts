import { describe, expect, it } from 'vitest';
import { rentalNavigationItem } from './rentalNavigation';

describe('Vehicle Rental navigation', () => {
    it('follows the agreement-first operator workflow', () => {
        expect(rentalNavigationItem.children?.map(item => item.label)).toEqual([
            'Owner Agreements',
            'Customer Agreements',
            'Vehicle Use Register',
            'Running Chart Register',
        ]);
    });
});
