import type {
    Item,
    ItemAttribute,
    ItemAttributeValue,
    ItemAuditEntry,
    ItemBrand,
    ItemCategory,
    ItemComboComponent,
    ItemIdentifier,
    ItemInventorySummary,
    ItemPricingReference,
    ItemUnit,
    ItemUsageSummary,
    ItemVariant,
} from '../types/item.types';

export const itemCategories: ItemCategory[] = [
    { code: 'CAT-PARTS', id: 'cat-parts', name: 'Spare Parts', status: 'active' },
    { code: 'CAT-SERVICE', id: 'cat-service', name: 'Services', status: 'active' },
    { code: 'CAT-RENTAL', id: 'cat-rental', name: 'Rental Charges', status: 'active' },
];

export const itemBrands: ItemBrand[] = [
    { id: 'brand-oem', name: 'OEM' },
    { id: 'brand-workshop', name: 'Workshop Internal' },
];

export const items: Item[] = [
    { baseUom: 'pcs', brand: 'OEM', category: 'Spare Parts', code: 'ITM-001', description: 'Stock tracked oil filter.', displayName: 'Oil Filter', id: 'item-001', itemType: 'inventory_product', name: 'Oil Filter', status: 'active', stockBehavior: 'stock_tracked', updatedAt: '2026-05-26' },
    { baseUom: 'service', brand: 'Workshop Internal', category: 'Services', code: 'ITM-002', description: 'Invoiceable service item with no stock impact.', displayName: 'Engine Tune Up', id: 'item-002', itemType: 'service', name: 'Engine Tune Up', status: 'active', stockBehavior: 'no_stock_impact', updatedAt: '2026-05-25' },
    { baseUom: 'hour', category: 'Services', code: 'ITM-003', description: 'Assignable labour item for Vehicle Service.', displayName: 'Senior Technician Labour', id: 'item-003', itemType: 'labour', name: 'Senior Technician Labour', status: 'active', stockBehavior: 'no_stock_impact', updatedAt: '2026-05-24' },
    { baseUom: 'bundle', category: 'Services', code: 'ITM-004', description: 'Backend expands combo components.', displayName: 'Premium Service Bundle', id: 'item-004', itemType: 'combo', name: 'Premium Service Bundle', status: 'draft', stockBehavior: 'no_stock_impact', updatedAt: '2026-05-23' },
    { baseUom: 'each', category: 'Services', code: 'ITM-005', description: 'External service reference.', displayName: 'Wheel Alignment External', id: 'item-005', itemType: 'external_service', name: 'Wheel Alignment External', status: 'active', stockBehavior: 'no_stock_impact', updatedAt: '2026-05-22' },
    { baseUom: 'day', category: 'Rental Charges', code: 'ITM-006', description: 'Rental billing line. No inventory impact.', displayName: 'Daily Rental Charge', id: 'item-006', itemType: 'rental_charge', name: 'Daily Rental Charge', status: 'active', stockBehavior: 'no_stock_impact', updatedAt: '2026-05-21' },
    { baseUom: 'ref', category: 'Services', code: 'ITM-007', description: 'Customer supplied reference item.', displayName: 'Customer Supplied Part', id: 'item-007', itemType: 'customer_supplied', name: 'Customer Supplied Part', status: 'inactive', stockBehavior: 'reference_only', updatedAt: '2026-05-20' },
];

export const itemUnits: Record<string, ItemUnit[]> = {
    'item-001': [
        { id: 'unit-001', isBase: true, purpose: 'base', unit: 'pcs' },
        { id: 'unit-002', isBase: false, purpose: 'receipt', unit: 'box' },
        { id: 'unit-003', isBase: false, purpose: 'issue', unit: 'pcs' },
    ],
    'item-004': [{ id: 'unit-004', isBase: true, purpose: 'base', unit: 'bundle' }],
};

export const itemAttributes: ItemAttribute[] = [
    { group: 'Physical', id: 'attr-001', name: 'Size', type: 'select' },
    { group: 'Vehicle Fitment', id: 'attr-002', name: 'Engine Type', type: 'select' },
];

export const itemAttributeValues: ItemAttributeValue[] = [
    { attributeId: 'attr-001', id: 'attr-val-001', value: 'Small' },
    { attributeId: 'attr-001', id: 'attr-val-002', value: 'Large' },
    { attributeId: 'attr-002', id: 'attr-val-003', value: 'Diesel' },
];

export const itemVariants: Record<string, ItemVariant[]> = {
    'item-001': [
        { attributes: [{ attribute: 'Size', value: 'Small' }], id: 'var-001', isActive: true, name: 'Oil Filter Small', sku: 'ITM-001-S' },
        { attributes: [{ attribute: 'Size', value: 'Large' }], id: 'var-002', isActive: true, name: 'Oil Filter Large', sku: 'ITM-001-L' },
    ],
};

export const itemComboComponents: Record<string, ItemComboComponent[]> = {
    'item-004': [
        { componentItemName: 'Engine Tune Up', componentType: 'service', id: 'combo-001', quantity: 'Backend-owned quantity preview', stockImpact: 'No stock impact', uom: 'service' },
        { componentItemName: 'Oil Filter', componentType: 'inventory_product', id: 'combo-002', quantity: 'Backend-owned quantity preview', stockImpact: 'Stock issue on posting', uom: 'pcs' },
        { componentItemName: 'Senior Technician Labour', componentType: 'labour', id: 'combo-003', quantity: 'Backend-owned labour allocation preview', stockImpact: 'No stock impact', uom: 'hour' },
    ],
};

export const itemIdentifiers: Record<string, ItemIdentifier[]> = {
    'item-001': [
        { id: 'ident-001', type: 'barcode', value: '8991002003001' },
        { id: 'ident-002', type: 'manufacturer', value: 'OEM-OF-100' },
    ],
};

export const itemPricingReferences: Record<string, ItemPricingReference[]> = {
    'item-001': [{ currency: 'LKR', id: 'price-ref-001', priceList: 'Retail Parts', resolvedByBackend: 'Backend price resolver preview pending' }],
    'item-002': [{ currency: 'LKR', id: 'price-ref-002', priceList: 'Workshop Services', resolvedByBackend: 'Backend price resolver preview pending' }],
};

export const itemInventorySummaries: Record<string, ItemInventorySummary> = {
    'item-001': { availability: 'Backend stock availability preview', costPreview: 'Backend-owned cost', stockOnHand: 'Backend stock quantity', valuation: 'Backend valuation' },
    'item-002': { availability: 'Not stock tracked', costPreview: 'No inventory cost calculation', stockOnHand: 'No stock impact', valuation: 'No inventory valuation' },
};

export const itemUsageSummaries: Record<string, ItemUsageSummary> = {
    'item-001': { chargeUse: 'Chargeable when selected by a source module', consumptionUse: 'Consumable stock item', inventoryUse: 'Stock movement capable', issueUse: 'Issue-capable', receiptUse: 'Receipt-capable' },
    'item-004': { chargeUse: 'Chargeable combo', consumptionUse: 'Backend expands components', inventoryUse: 'Backend expands components', issueUse: 'Component issue preview by backend', receiptUse: 'Not directly received' },
};

export const itemAuditEntries: ItemAuditEntry[] = [
    { actor: 'System', description: 'Item mock record prepared for UI preview.', id: 'audit-001', time: 'Today 08:55' },
    { actor: 'Inventory', description: 'Readonly inventory summary requested.', id: 'audit-002', time: 'Today 09:15' },
];

export function getItemById(id: string): Item {
    return items.find((item) => item.id === id) ?? items[0];
}
