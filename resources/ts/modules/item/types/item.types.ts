export type ItemStatus = 'active' | 'draft' | 'inactive' | 'discontinued';

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

export type StockBehavior = 'no_stock_impact' | 'reference_only' | 'stock_tracked';

export type ItemCategory = {
    code: string;
    description?: string;
    id: string;
    isActive: boolean;
    name: string;
    status: ItemStatus;
};

export type ItemBrand = {
    code?: string;
    id: string;
    isActive: boolean;
    name: string;
};

export type ItemTypeOption = {
    code: string;
    id?: string;
    isChargeable: boolean;
    isRentable: boolean;
    isService: boolean;
    isStockable: boolean;
    label: string;
    value: ItemType;
};

export type UomOption = {
    id: string;
    isBase: boolean;
    label: string;
    name: string;
    symbol: string;
    type: string;
};

export type ItemLookupOption = {
    code?: string;
    id: string;
    label: string;
    name: string;
};

export type ComboComponentInput = {
    componentItemId: string;
    quantity: string;
    uomId: string;
};

export type Item = {
    allowChargeUsage: boolean;
    allowConsumptionUsage: boolean;
    allowIssueUsage: boolean;
    allowReceiptUsage: boolean;
    barcode?: string;
    baseUom: string;
    baseUomId?: string;
    brand?: string;
    brandId?: string;
    cogsAccountId?: string;
    category: string;
    categoryId?: string;
    code: string;
    defaultChargeUomId?: string;
    defaultConsumptionUomId?: string;
    defaultIssueUomId?: string;
    defaultReceiptUomId?: string;
    description?: string;
    displayName: string;
    expenseAccountId?: string;
    id: string;
    incomeAccountId?: string;
    inventoryAccountId?: string;
    isBatchTracked: boolean;
    isRentable: boolean;
    isSerialTracked: boolean;
    isService: boolean;
    isStockable: boolean;
    itemType: ItemType;
    itemTypeId?: string;
    name: string;
    status: ItemStatus;
    stockBehavior: StockBehavior;
    taxGroupId?: string;
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
    itemId?: string;
    name: string;
    sku: string;
};

export type ItemComboComponent = {
    comboItemId?: string;
    componentItemId?: string;
    componentItemName: string;
    componentType: ItemType;
    id: string;
    quantity: string;
    stockImpact: string;
    uom: string;
};

export type ItemIdentifier = {
    id: string;
    itemId?: string;
    type: 'barcode' | 'internal' | 'manufacturer' | 'qr' | 'rfid' | 'other';
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

export type ItemListQuery = {
    isStockable?: boolean;
    page?: number;
    perPage?: number;
    search?: string;
    status?: ItemStatus;
    type?: ItemType;
};

export type ItemFormInput = {
    allowChargeUsage: boolean;
    allowConsumptionUsage: boolean;
    allowIssueUsage: boolean;
    allowReceiptUsage: boolean;
    barcode: string;
    baseUomId: string;
    brandId: string;
    categoryId: string;
    cogsAccountId: string;
    code: string;
    comboItems: ComboComponentInput[];
    defaultChargeUomId: string;
    defaultConsumptionUomId: string;
    defaultIssueUomId: string;
    defaultReceiptUomId: string;
    description: string;
    displayName: string;
    expenseAccountId: string;
    incomeAccountId: string;
    inventoryAccountId: string;
    itemType: ItemType;
    itemTypeId: string;
    name: string;
    status: ItemStatus;
    stockable: boolean;
    taxGroupId: string;
    trackBatch: boolean;
    trackSerial: boolean;
};
