import type { Item } from '../types/item.types';

export const items: Item[] = [
    { code: 'ITM-001', id: 'item-001', name: 'Oil Filter', status: 'Active', stockMode: 'Stock tracked', type: 'Stock Item', uom: 'pcs' },
    { code: 'ITM-002', id: 'item-002', name: 'Engine Tune Up', status: 'Active', stockMode: 'No stock impact', type: 'Service Item', uom: 'service' },
    { code: 'ITM-003', id: 'item-003', name: 'Premium Service Bundle', status: 'Draft', stockMode: 'Backend combo expansion', type: 'Combo', uom: 'bundle' },
];
