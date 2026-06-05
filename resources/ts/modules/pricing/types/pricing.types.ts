export type PriceListStatus = 'active' | 'inactive';

export type PriceListType = 'sales' | 'purchase' | 'customer' | 'supplier' | 'service' | 'rental' | 'generic';

export type PricingModuleScope = 'sales' | 'purchase' | 'vehicle_service' | 'vehicle_rental';

export type LookupOption = {
    code?: string;
    id: string;
    label: string;
    name: string;
};

export type PriceList = {
    code: string;
    currency: string;
    currencyId?: string;
    description: string;
    id: string;
    isCustomerSpecific: boolean;
    isDefault: boolean;
    isExclusive: boolean;
    isStackable: boolean;
    isSupplierSpecific: boolean;
    moduleUsage: PricingModuleScope[];
    name: string;
    priority: string;
    status: PriceListStatus;
    type: PriceListType;
    updatedAt: string;
    validFrom: string;
    validTo: string;
};

export type PriceListItem = {
    active: boolean;
    discountType: 'percentage' | 'fixed';
    discountValue: string;
    effectiveFrom: string;
    effectiveTo: string;
    id: string;
    itemCode: string;
    itemId: string;
    itemName: string;
    maxQuantity: string;
    minQuantity: string;
    priceListId: string;
    priority: string;
    uom: string;
    uomId: string;
    unitPrice: string;
};

export type PriceListItemInput = {
    discountType: 'percentage' | 'fixed';
    discountValue: string;
    effectiveFrom: string;
    effectiveTo: string;
    id?: string;
    isActive: boolean;
    itemId: string;
    maxQuantity: string;
    minQuantity: string;
    priceListId: string;
    priority: string;
    uomId: string;
    unitPrice: string;
};

export type CustomerPriceList = {
    customerName: string;
    id: string;
    priceListId: string;
    status: PriceListStatus;
};

export type SupplierPriceList = {
    id: string;
    priceListId: string;
    status: PriceListStatus;
    supplierName: string;
};

export type PricingRule = {
    actionType: 'price_list' | 'discount' | 'tier' | 'override' | 'adjust_price';
    actionValue: string;
    code: string;
    description: string;
    id: string;
    isExclusive: boolean;
    isStackable: boolean;
    name: string;
    priority: string;
    ruleType: 'price_resolve' | 'discount' | 'tier' | 'module_specific' | 'generic';
    sourceType: PricingModuleScope | 'all';
    status: PriceListStatus;
    validFrom: string;
    validTo: string;
};

export type PricingRuleCondition = {
    conditionType: string;
    field: string;
    id: string;
    operator: string;
    ruleId: string;
    sequence: number;
    value: string;
};

export type Discount = {
    code: string;
    discountType: 'percentage' | 'fixed';
    discountValue: string;
    id: string;
    isExclusive: boolean;
    isStackable: boolean;
    name: string;
    priority: string;
    status: PriceListStatus;
    validFrom: string;
    validTo: string;
};

export type DiscountInput = {
    code: string;
    discountType: 'percentage' | 'fixed';
    discountValue: string;
    id?: string;
    isActive: boolean;
    isExclusive: boolean;
    isStackable: boolean;
    name: string;
    priority: string;
    validFrom: string;
    validTo: string;
};

export type DiscountRule = {
    discountId: string;
    id: string;
    scope: string;
    status: PriceListStatus;
};

export type PricingTier = {
    active: boolean;
    adjustmentType: 'percentage' | 'fixed' | 'override' | '';
    adjustmentValue: string;
    id: string;
    maxQuantity: string;
    minQuantity: string;
    priceListItemId: string;
    pricingRuleId: string;
    sequence: string;
    tierName: string;
    unitPrice: string;
    uomId: string;
};

export type PricingTierInput = {
    adjustmentType: 'percentage' | 'fixed' | 'override' | '';
    adjustmentValue: string;
    id?: string;
    isActive: boolean;
    maxQuantity: string;
    minQuantity: string;
    priceListItemId: string;
    pricingRuleId: string;
    sequence: string;
    unitPrice: string;
    uomId: string;
};

export type PriceResolveRequest = {
    currencyId?: string;
    customerId?: string;
    date: string;
    itemId: string;
    moduleSource: PricingModuleScope;
    priceListId?: string;
    quantity: string;
    supplierId?: string;
    uomId: string;
};

export type PriceBreakdown = {
    label: string;
    value: string;
};

export type PriceResolveResult = {
    appliedDiscount: string;
    appliedRule: string;
    breakdown: PriceBreakdown[];
    errors: string[];
    input: PriceResolveRequest;
    netUnitPrice: string;
    resolvedUnitPrice: string;
    selectedPriceList: string;
    tierInfo: string;
    warnings: string[];
};

export type PriceHistory = {
    actor: string;
    change: string;
    effectiveDate: string;
    id: string;
    itemName: string;
    newPrice: string;
    oldPrice: string;
    priceListName: string;
};

export type PricingAuditEntry = {
    actor: string;
    description: string;
    id: string;
    time: string;
};

export type PricingUsageSummary = {
    counts: {
        conditions?: number;
        customerLinks?: number;
        discounts?: number;
        historyEntries?: number;
        priceListItems?: number;
        purchaseReferences?: number;
        rentalReferences?: number;
        salesReferences?: number;
        serviceReferences?: number;
        supplierLinks?: number;
        tiers?: number;
    };
};

export type PriceListFormInput = {
    code: string;
    currencyId: string;
    description: string;
    isActive: boolean;
    isCustomerSpecific: boolean;
    isDefault: boolean;
    isExclusive: boolean;
    isStackable: boolean;
    isSupplierSpecific: boolean;
    moduleUsage: PricingModuleScope[];
    name: string;
    priority: string;
    type: PriceListType;
    validFrom: string;
    validTo: string;
};

export type PricingRuleFormInput = {
    actionType: PricingRule['actionType'];
    actionValue: string;
    conditionField: string;
    conditionOperator: string;
    conditionValue: string;
    description: string;
    isActive: boolean;
    isExclusive: boolean;
    isStackable: boolean;
    name: string;
    priority: string;
    ruleCode: string;
    ruleType: PricingRule['ruleType'];
    sourceType: PricingRule['sourceType'];
    validFrom: string;
    validTo: string;
};
