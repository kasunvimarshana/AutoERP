export type UomUnitStatus = 'active' | 'inactive';

export type UomUnitType = 'UNIT' | 'MASS' | 'VOLUME' | 'LENGTH' | 'AREA' | 'TIME' | 'DISTANCE' | 'OTHER';

export type UomCategory = {
    id: string;
    name: string;
    type: UomUnitType;
};

export type UomUnit = {
    allowFractional: boolean;
    category: UomUnitType;
    code: string;
    description?: string;
    id: string;
    isActive: boolean;
    isBase: boolean;
    name: string;
    precision: number;
    status: UomUnitStatus;
    symbol: string;
    type: UomUnitType;
    updatedAt: string;
    usableForInventory: boolean;
    usableForPurchase: boolean;
    usableForRental: boolean;
    usableForSales: boolean;
    usableForService: boolean;
};

export type UomConversionDirection = 'bidirectional' | 'one_way';

export type UomConversion = {
    category: UomUnitType | 'OTHER';
    direction: UomConversionDirection;
    effectiveFrom?: string;
    effectiveTo?: string;
    factor: string;
    fromUnitCode: string;
    fromUnitId: string;
    id: string;
    isActive: boolean;
    isItemSpecific: boolean;
    itemId?: string;
    itemName?: string;
    notes?: string;
    toUnitCode: string;
    toUnitId: string;
    updatedAt: string;
};

export type UomConversionPreview = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: {
        convertedQuantity: string;
        factor: string;
        precision: string;
    };
    errors: string[];
    input: {
        fromUnitId: string;
        itemId?: string;
        quantity: string;
        toUnitId: string;
    };
    warnings: string[];
};

export type UomItemUsage = {
    inventory: string;
    items: string;
    pricing: string;
    purchase: string;
    rental: string;
    sales: string;
    service: string;
};

export type UomAuditEntry = {
    actor: string;
    description: string;
    id: string;
    time: string;
};

export type UomLookupOption = {
    id: string;
    label: string;
};

export type UomUnitFormInput = {
    allowFractional: boolean;
    code: string;
    description: string;
    isBase: boolean;
    name: string;
    precision: string;
    status: UomUnitStatus;
    symbol: string;
    type: UomUnitType;
    usableForInventory: boolean;
    usableForPurchase: boolean;
    usableForRental: boolean;
    usableForSales: boolean;
    usableForService: boolean;
};

export type UomConversionFormInput = {
    effectiveFrom?: string;
    effectiveTo?: string;
    factor: string;
    fromUnitId: string;
    isActive: boolean;
    isBidirectional: boolean;
    isItemSpecific: boolean;
    itemId?: string;
    notes?: string;
    quantity?: string;
    toUnitId: string;
};
