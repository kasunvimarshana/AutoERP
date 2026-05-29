import type {
    CustomerPriceList,
    Discount,
    DiscountRule,
    PriceHistory,
    PriceList,
    PriceListItem,
    PriceResolveRequest,
    PriceResolveResult,
    PricingAuditEntry,
    PricingRule,
    PricingRuleCondition,
    PricingTier,
    PricingUsageSummary,
    SupplierPriceList,
} from '../types/pricing.types';

export const priceLists: PriceList[] = [
    {
        code: 'SPL-STD-2026',
        currency: 'LKR',
        description: 'Standard sales price list for retail and workshop counters.',
        id: 'price-list-sales-standard',
        isCustomerSpecific: false,
        isSupplierSpecific: false,
        moduleUsage: ['sales', 'vehicle_service'],
        name: 'Standard Sales Price List',
        priority: '100',
        status: 'active',
        type: 'sales',
        updatedAt: '2026-05-21',
        validFrom: '2026-01-01',
        validTo: '2026-12-31',
    },
    {
        code: 'PPL-SUP-2026',
        currency: 'USD',
        description: 'Supplier negotiated purchase prices for spare parts.',
        id: 'price-list-supplier-imports',
        isCustomerSpecific: false,
        isSupplierSpecific: true,
        moduleUsage: ['purchase'],
        name: 'Supplier Import Price List',
        priority: '80',
        status: 'active',
        type: 'supplier',
        updatedAt: '2026-05-16',
        validFrom: '2026-02-01',
        validTo: '2026-11-30',
    },
    {
        code: 'CPL-FLEET-A',
        currency: 'LKR',
        description: 'Fleet customer contract prices with backend-owned discount priority.',
        id: 'price-list-fleet-customer',
        isCustomerSpecific: true,
        isSupplierSpecific: false,
        moduleUsage: ['sales', 'vehicle_service', 'vehicle_rental'],
        name: 'Fleet Customer Contract Prices',
        priority: '140',
        status: 'active',
        type: 'customer',
        updatedAt: '2026-05-19',
        validFrom: '2026-04-01',
        validTo: '2026-09-30',
    },
    {
        code: 'VSR-LABOUR',
        currency: 'LKR',
        description: 'Service and labour item rates used by vehicle service invoices.',
        id: 'price-list-service-labour',
        isCustomerSpecific: false,
        isSupplierSpecific: false,
        moduleUsage: ['vehicle_service'],
        name: 'Workshop Service Rates',
        priority: '90',
        status: 'active',
        type: 'service',
        updatedAt: '2026-05-20',
        validFrom: '2026-01-01',
        validTo: '2026-12-31',
    },
    {
        code: 'RNT-2026',
        currency: 'LKR',
        description: 'Vehicle rental day, hour, month, and kilometer charge items.',
        id: 'price-list-rental-rates',
        isCustomerSpecific: false,
        isSupplierSpecific: false,
        moduleUsage: ['vehicle_rental'],
        name: 'Rental Charge Price List',
        priority: '70',
        status: 'draft',
        type: 'rental',
        updatedAt: '2026-05-22',
        validFrom: '2026-06-01',
        validTo: '2026-12-31',
    },
];

export const priceListItems: PriceListItem[] = [
    { active: true, effectiveFrom: '2026-01-01', effectiveTo: '2026-12-31', id: 'pli-001', itemCode: 'BRK-PAD-F', itemId: 'item-brake-pad', itemName: 'Front Brake Pad Set', minQuantity: '1', priceListId: 'price-list-sales-standard', uom: 'SET', unitPrice: 'Backend price 18,500.00' },
    { active: true, effectiveFrom: '2026-01-01', effectiveTo: '2026-12-31', id: 'pli-002', itemCode: 'ENG-OIL-10W40', itemId: 'item-engine-oil', itemName: 'Engine Oil 10W-40', minQuantity: '1', priceListId: 'price-list-sales-standard', uom: 'L', unitPrice: 'Backend price 3,750.00' },
    { active: true, effectiveFrom: '2026-04-01', effectiveTo: '2026-09-30', id: 'pli-003', itemCode: 'LAB-GEN', itemId: 'item-labour-general', itemName: 'General Technician Labour', minQuantity: '1', priceListId: 'price-list-fleet-customer', uom: 'HOUR', unitPrice: 'Backend contract rate' },
    { active: true, effectiveFrom: '2026-06-01', effectiveTo: '2026-12-31', id: 'pli-004', itemCode: 'RENT-DAY-SUV', itemId: 'item-rental-day-suv', itemName: 'SUV Rental Daily Charge', minQuantity: '1', priceListId: 'price-list-rental-rates', uom: 'DAY', unitPrice: 'Backend rental rate' },
];

export const customerPriceLists: CustomerPriceList[] = [
    { customerName: 'Apex Logistics Fleet', id: 'cpl-001', priceListId: 'price-list-fleet-customer', status: 'active' },
];

export const supplierPriceLists: SupplierPriceList[] = [
    { id: 'spl-001', priceListId: 'price-list-supplier-imports', status: 'active', supplierName: 'Global Spare Parts Exporters' },
];

export const pricingRules: PricingRule[] = [
    { actionType: 'discount', actionValue: 'Backend resolves contract discount', code: 'RULE-FLEET-DISC', description: 'Fleet customers receive backend-resolved service discounts.', id: 'rule-fleet-discount', isExclusive: false, isStackable: true, name: 'Fleet Customer Discount Rule', priority: '120', ruleType: 'discount', sourceType: 'vehicle_service', status: 'active', validFrom: '2026-04-01', validTo: '2026-12-31' },
    { actionType: 'tier', actionValue: 'Backend resolves quantity tier', code: 'RULE-BULK-PARTS', description: 'Bulk spare part purchase and sales tier pricing.', id: 'rule-bulk-parts', isExclusive: false, isStackable: true, name: 'Bulk Parts Tier Rule', priority: '90', ruleType: 'tier', sourceType: 'sales', status: 'active', validFrom: '2026-01-01', validTo: '2026-12-31' },
    { actionType: 'price_list', actionValue: 'Use rental charge price list', code: 'RULE-RENTAL-MONTH', description: 'Rental monthly charges prefer rental list when source is vehicle rental.', id: 'rule-rental-monthly', isExclusive: true, isStackable: false, name: 'Rental Monthly Rate Priority', priority: '110', ruleType: 'module_specific', sourceType: 'vehicle_rental', status: 'draft', validFrom: '2026-06-01', validTo: '2026-12-31' },
];

export const pricingRuleConditions: PricingRuleCondition[] = [
    { conditionType: 'party', field: 'customer.category', id: 'cond-001', operator: 'equals', ruleId: 'rule-fleet-discount', sequence: 1, value: 'Fleet' },
    { conditionType: 'quantity', field: 'quantity', id: 'cond-002', operator: 'greater_than_or_equal', ruleId: 'rule-bulk-parts', sequence: 1, value: '10' },
    { conditionType: 'source', field: 'module_source', id: 'cond-003', operator: 'equals', ruleId: 'rule-rental-monthly', sequence: 1, value: 'vehicle_rental' },
];

export const discounts: Discount[] = [
    { code: 'DISC-FLEET-10', discountType: 'percentage', discountValue: '10', id: 'discount-fleet-10', isExclusive: false, isStackable: true, name: 'Fleet Service Discount', priority: '100', status: 'active', validFrom: '2026-04-01', validTo: '2026-12-31' },
    { code: 'DISC-SEASON-FIXED', discountType: 'fixed', discountValue: 'Backend fixed value', id: 'discount-seasonal-fixed', isExclusive: true, isStackable: false, name: 'Seasonal Fixed Discount', priority: '70', status: 'inactive', validFrom: '2026-05-01', validTo: '2026-05-31' },
];

export const discountRules: DiscountRule[] = [
    { discountId: 'discount-fleet-10', id: 'discount-rule-001', scope: 'Vehicle service fleet customers', status: 'active' },
    { discountId: 'discount-seasonal-fixed', id: 'discount-rule-002', scope: 'Sales campaigns', status: 'inactive' },
];

export const pricingTiers: PricingTier[] = [
    { active: true, id: 'tier-001', itemName: 'Front Brake Pad Set', maxQuantity: '24', minQuantity: '10', priceListId: 'price-list-sales-standard', tierName: 'Bulk Parts Tier A', unitPrice: 'Backend tier price' },
    { active: true, id: 'tier-002', itemName: 'SUV Rental Daily Charge', maxQuantity: '30', minQuantity: '7', priceListId: 'price-list-rental-rates', tierName: 'Weekly Rental Tier', unitPrice: 'Backend rental tier' },
];

export const priceHistory: PriceHistory[] = [
    { actor: 'Pricing Manager', change: 'Updated item/UOM-specific price', effectiveDate: '2026-05-21', id: 'history-001', itemName: 'Front Brake Pad Set', newPrice: 'Backend stored new price', oldPrice: 'Backend stored old price', priceListName: 'Standard Sales Price List' },
    { actor: 'System', change: 'Imported supplier negotiated price', effectiveDate: '2026-05-16', id: 'history-002', itemName: 'Engine Oil 10W-40', newPrice: 'Backend imported price', oldPrice: 'Previous supplier price', priceListName: 'Supplier Import Price List' },
];

export const pricingActivity: PricingAuditEntry[] = [
    { actor: 'Pricing Manager', description: 'Activated Standard Sales Price List.', id: 'audit-001', time: '2026-05-21 09:20' },
    { actor: 'System', description: 'Recorded backend price history for supplier import.', id: 'audit-002', time: '2026-05-16 16:05' },
];

export const pricingUsage: PricingUsageSummary = {
    customerLinks: '1 active customer-specific price list',
    itemCoverage: '4 mocked items across product, labour, and rental charge types',
    modules: 'Sales, Purchase, Vehicle Service, Vehicle Rental',
    supplierLinks: '1 active supplier price list',
    transactionUsage: 'Backend usage summary pending',
};

export function getPriceListById(priceListId: string) {
    return priceLists.find((priceList) => priceList.id === priceListId) ?? priceLists[0];
}

export function getPricingRuleById(ruleId: string) {
    return pricingRules.find((rule) => rule.id === ruleId) ?? pricingRules[0];
}

export function mockResolvePrice(input: PriceResolveRequest): PriceResolveResult {
    return {
        appliedDiscount: 'Backend/mock discount rule: Fleet Service Discount',
        appliedRule: 'Backend/mock rule priority selected',
        breakdown: [
            { label: 'Resolver source', value: input.moduleSource },
            { label: 'Price priority', value: 'Backend-owned priority order' },
            { label: 'UOM normalization', value: 'Backend-owned' },
            { label: 'Discount calculation', value: 'Backend-owned' },
        ],
        errors: [],
        input,
        netUnitPrice: 'Backend/mock net unit price',
        resolvedUnitPrice: 'Backend/mock resolved unit price',
        selectedPriceList: input.priceListId ? getPriceListById(input.priceListId).name : 'Backend selected best price list',
        tierInfo: 'Backend tier evaluation placeholder',
        warnings: input.customerId || input.supplierId ? [] : ['Party-specific rules were not evaluated without a customer or supplier.'],
    };
}
