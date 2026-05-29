import type { UomAuditEntry, UomCategory, UomConversion, UomConversionPreview, UomItemUsage, UomUnit } from '../types/uom.types';

export const uomCategories: UomCategory[] = [
    { id: 'cat-count', name: 'Count', type: 'count' },
    { id: 'cat-volume', name: 'Volume', type: 'volume' },
    { id: 'cat-mass', name: 'Mass', type: 'mass' },
    { id: 'cat-duration', name: 'Duration', type: 'duration' },
    { id: 'cat-distance', name: 'Distance', type: 'distance' },
];

export const uomUnits: UomUnit[] = [
    { allowFractional: false, category: 'Count', code: 'PCS', id: 'uom-pcs', isBase: true, name: 'Pieces', precision: 0, status: 'active', symbol: 'pcs', type: 'count', updatedAt: '2026-05-24', usableForInventory: true, usableForPurchase: true, usableForRental: false, usableForSales: true, usableForService: true },
    { allowFractional: false, category: 'Count', code: 'BOX', id: 'uom-box', isBase: false, name: 'Box', precision: 0, status: 'active', symbol: 'box', type: 'count', updatedAt: '2026-05-24', usableForInventory: false, usableForPurchase: true, usableForRental: false, usableForSales: false, usableForService: false },
    { allowFractional: false, category: 'Count', code: 'PACK', id: 'uom-pack', isBase: false, name: 'Pack', precision: 0, status: 'active', symbol: 'pack', type: 'count', updatedAt: '2026-05-23', usableForInventory: false, usableForPurchase: true, usableForRental: false, usableForSales: true, usableForService: false },
    { allowFractional: true, category: 'Mass', code: 'KG', id: 'uom-kg', isBase: true, name: 'Kilogram', precision: 3, status: 'active', symbol: 'kg', type: 'mass', updatedAt: '2026-05-22', usableForInventory: true, usableForPurchase: true, usableForRental: false, usableForSales: true, usableForService: false },
    { allowFractional: true, category: 'Mass', code: 'G', id: 'uom-g', isBase: false, name: 'Gram', precision: 2, status: 'active', symbol: 'g', type: 'mass', updatedAt: '2026-05-22', usableForInventory: true, usableForPurchase: false, usableForRental: false, usableForSales: true, usableForService: false },
    { allowFractional: true, category: 'Volume', code: 'L', id: 'uom-l', isBase: true, name: 'Litre', precision: 3, status: 'active', symbol: 'L', type: 'volume', updatedAt: '2026-05-21', usableForInventory: true, usableForPurchase: true, usableForRental: false, usableForSales: true, usableForService: true },
    { allowFractional: true, category: 'Volume', code: 'ML', id: 'uom-ml', isBase: false, name: 'Millilitre', precision: 2, status: 'active', symbol: 'ml', type: 'volume', updatedAt: '2026-05-21', usableForInventory: true, usableForPurchase: false, usableForRental: false, usableForSales: true, usableForService: true },
    { allowFractional: true, category: 'Duration', code: 'HOUR', id: 'uom-hour', isBase: true, name: 'Hour', precision: 2, status: 'active', symbol: 'hr', type: 'duration', updatedAt: '2026-05-20', usableForInventory: false, usableForPurchase: false, usableForRental: true, usableForSales: true, usableForService: true },
    { allowFractional: true, category: 'Duration', code: 'DAY', id: 'uom-day', isBase: false, name: 'Day', precision: 2, status: 'active', symbol: 'day', type: 'duration', updatedAt: '2026-05-20', usableForInventory: false, usableForPurchase: false, usableForRental: true, usableForSales: true, usableForService: false },
    { allowFractional: true, category: 'Duration', code: 'MONTH', id: 'uom-month', isBase: false, name: 'Month', precision: 2, status: 'inactive', symbol: 'mo', type: 'duration', updatedAt: '2026-05-18', usableForInventory: false, usableForPurchase: false, usableForRental: true, usableForSales: true, usableForService: false },
    { allowFractional: true, category: 'Distance', code: 'KM', id: 'uom-km', isBase: true, name: 'Kilometre', precision: 2, status: 'active', symbol: 'km', type: 'distance', updatedAt: '2026-05-18', usableForInventory: false, usableForPurchase: false, usableForRental: true, usableForSales: false, usableForService: false },
];

export const uomConversions: UomConversion[] = [
    { category: 'Count', direction: 'bidirectional', factor: '1 BOX = 12 PCS', fromUnitCode: 'BOX', fromUnitId: 'uom-box', id: 'conv-box-pcs', isActive: true, isItemSpecific: false, toUnitCode: 'PCS', toUnitId: 'uom-pcs', updatedAt: '2026-05-24' },
    { category: 'Count', direction: 'bidirectional', factor: '1 PACK = 6 PCS', fromUnitCode: 'PACK', fromUnitId: 'uom-pack', id: 'conv-pack-pcs', isActive: true, isItemSpecific: false, toUnitCode: 'PCS', toUnitId: 'uom-pcs', updatedAt: '2026-05-23' },
    { category: 'Volume', direction: 'bidirectional', factor: '1 L = 1000 ML', fromUnitCode: 'L', fromUnitId: 'uom-l', id: 'conv-l-ml', isActive: true, isItemSpecific: false, toUnitCode: 'ML', toUnitId: 'uom-ml', updatedAt: '2026-05-21' },
    { category: 'Mass', direction: 'bidirectional', factor: '1 KG = 1000 G', fromUnitCode: 'KG', fromUnitId: 'uom-kg', id: 'conv-kg-g', isActive: true, isItemSpecific: false, toUnitCode: 'G', toUnitId: 'uom-g', updatedAt: '2026-05-22' },
    { category: 'Count', direction: 'one_way', factor: 'Backend item-specific conversion', fromUnitCode: 'BOX', fromUnitId: 'uom-box', id: 'conv-oil-box-pcs', isActive: true, isItemSpecific: true, itemName: 'Oil Filter', toUnitCode: 'PCS', toUnitId: 'uom-pcs', updatedAt: '2026-05-19' },
];

export const uomUsage: Record<string, UomItemUsage> = {
    'uom-pcs': { inventory: 'Used by stock-tracked items', items: 'Oil Filter, Spare Parts', pricing: 'Used by parts price lists', purchase: 'PO/GRN line quantity', rental: 'Not used for rental billing', sales: 'Invoiceable quantity', service: 'Spare part consumption' },
    'uom-hour': { inventory: 'No stock impact', items: 'Labour and rental charges', pricing: 'Hourly service/rental rates', purchase: 'Not typically purchased', rental: 'Hourly rental billing', sales: 'Invoiceable duration', service: 'Labour duration' },
};

export const uomActivity: UomAuditEntry[] = [
    { actor: 'System', description: 'UOM mock record prepared for UI preview.', id: 'uom-audit-001', time: 'Today 08:35' },
    { actor: 'Inventory', description: 'Conversion compatibility preview requested.', id: 'uom-audit-002', time: 'Today 09:05' },
];

export function getUomUnitById(id: string): UomUnit {
    return uomUnits.find((unit) => unit.id === id) ?? uomUnits[0];
}

export function getUomConversionById(id: string): UomConversion {
    return uomConversions.find((conversion) => conversion.id === id) ?? uomConversions[0];
}

export function mockUomPreview(input: UomConversionPreview['input']): UomConversionPreview {
    const fromUnit = getUomUnitById(input.fromUnitId);
    const toUnit = getUomUnitById(input.toUnitId);
    const conversion = uomConversions.find((item) => item.fromUnitId === input.fromUnitId && item.toUnitId === input.toUnitId);

    return {
        breakdown: [
            { label: 'Compatibility', value: fromUnit.category === toUnit.category ? 'Backend-compatible category' : 'Backend warning: category mismatch' },
            { label: 'Direction', value: conversion?.direction ?? 'Backend conversion lookup' },
        ],
        calculated: {
            convertedQuantity: 'Backend-owned conversion result',
            factor: conversion?.factor ?? 'Backend factor lookup',
            precision: `${toUnit.precision} decimal places`,
        },
        errors: [],
        input,
        warnings: fromUnit.category === toUnit.category ? [] : ['Units belong to different categories. Backend must validate before use.'],
    };
}
