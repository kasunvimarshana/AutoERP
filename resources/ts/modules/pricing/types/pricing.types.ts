export type PriceListStatus = 'active' | 'inactive' | 'draft' | 'expired';

export type PriceListType = 'sales' | 'purchase' | 'customer' | 'supplier' | 'service' | 'rental';

export type PricingModuleScope = 'sales' | 'purchase' | 'vehicle_service' | 'vehicle_rental';

export type PriceList = {
    code: string;
    currency: string;
    description: string;
    id: string;
    isCustomerSpecific: boolean;
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
    effectiveFrom: string;
    effectiveTo: string;
    id: string;
    itemCode: string;
    itemId: string;
    itemName: string;
    minQuantity: string;
    priceListId: string;
    uom: string;
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
    actionType: 'price_list' | 'discount' | 'tier' | 'override';
    actionValue: string;
    code: string;
    description: string;
    id: string;
    isExclusive: boolean;
    isStackable: boolean;
    name: string;
    priority: string;
    ruleType: 'price_resolve' | 'discount' | 'tier' | 'module_specific';
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

export type DiscountRule = {
    discountId: string;
    id: string;
    scope: string;
    status: PriceListStatus;
};

export type PricingTier = {
    active: boolean;
    id: string;
    itemName: string;
    maxQuantity: string;
    minQuantity: string;
    priceListId: string;
    tierName: string;
    unitPrice: string;
};

export type PriceResolveRequest = {
    currency: string;
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
    customerLinks: string;
    itemCoverage: string;
    modules: string;
    supplierLinks: string;
    transactionUsage: string;
};

export type PriceListFormInput = {
    code: string;
    currency: string;
    description: string;
    isActive: boolean;
    isCustomerSpecific: boolean;
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
    conditionsNote: string;
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
