import type { PriceRule } from '../types/pricing.types';

export const priceRules: PriceRule[] = [
    { id: 'price-001', name: 'Default Workshop Price List', priority: '100', scope: 'Vehicle Service', status: 'Active' },
    { id: 'price-002', name: 'Fleet Customer Discount Rule', priority: '80', scope: 'Sales / Service', status: 'Active' },
    { id: 'price-003', name: 'Rental Long-Term Rate Rule', priority: '60', scope: 'Vehicle Rental', status: 'Draft' },
];
