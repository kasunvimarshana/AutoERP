export type ItemStatus = 'active' | 'draft' | 'inactive';

export type ItemType =
    | 'combo'
    | 'customer_supplied'
    | 'external_service'
    | 'fee_adjustment'
    | 'inventory_product'
    | 'labour'
    | 'non_inventory'
    | 'rental_charge'
    | 'service';

export type ItemCategory = {
    code: string;
    id: string;
    name: string;
    status: ItemStatus;
};

export type ItemBrand = {
    id: string;
    name: string;
};

export type Item = {
    baseUom: string;
    brand?: string;
    category: string;
    code: string;
    description?: string;
    displayName: string;
    id: string;
    itemType: ItemType;
    name: string;
    status: ItemStatus;
    stockBehavior: 'no_stock_impact' | 'reference_only' | 'stock_tracked';
    updatedAt: string;
};

export type ItemUnit = {
    id: string;
    isBase: boolean;
    purpose: 'base' | 'charge' | 'consumption' | 'issue' | 'receipt';
    unit: string;
};

export type ItemAttribute = {
    group?: string;
    id: string;
    name: string;
    type: string;
};

export type ItemAttributeValue = {
    attributeId: string;
    id: string;
    value: string;
};

export type ItemVariantAttribute = {
    attribute: string;
    value: string;
};

export type ItemVariant = {
    attributes: ItemVariantAttribute[];
    id: string;
    isActive: boolean;
    name: string;
    sku: string;
};

export type ItemComboComponent = {
    componentItemName: string;
    componentType: ItemType;
    id: string;
    quantity: string;
    stockImpact: string;
    uom: string;
};

export type ItemIdentifier = {
    id: string;
    type: 'barcode' | 'internal' | 'manufacturer' | 'qr';
    value: string;
};

export type ItemPricingReference = {
    currency: string;
    id: string;
    priceList: string;
    resolvedByBackend: string;
};

export type ItemInventorySummary = {
    availability: string;
    costPreview: string;
    stockOnHand: string;
    valuation: string;
};

export type ItemUsageSummary = {
    chargeUse: string;
    consumptionUse: string;
    inventoryUse: string;
    issueUse: string;
    receiptUse: string;
};

export type ItemAuditEntry = {
    actor: string;
    description: string;
    id: string;
    time: string;
};

export type ItemFormInput = {
    allowChargeUsage: boolean;
    allowConsumptionUsage: boolean;
    allowIssueUsage: boolean;
    allowReceiptUsage: boolean;
    baseUomId: string;
    brand: string;
    category: string;
    code: string;
    description: string;
    displayName: string;
    itemType: ItemType;
    name: string;
    status: ItemStatus;
    stockable: boolean;
    trackBatch: boolean;
    trackSerial: boolean;
};
