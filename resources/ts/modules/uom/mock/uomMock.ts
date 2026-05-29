import type { Unit } from '../types/uom.types';

export const units: Unit[] = [
    { category: 'Quantity', code: 'PCS', id: 'uom-001', name: 'Pieces', precision: 0, status: 'Active' },
    { category: 'Volume', code: 'LTR', id: 'uom-002', name: 'Litre', precision: 3, status: 'Active' },
    { category: 'Service', code: 'SRV', id: 'uom-003', name: 'Service', precision: 0, status: 'Active' },
];
