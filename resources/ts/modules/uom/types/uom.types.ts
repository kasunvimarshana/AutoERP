export type UomUnitStatus = 'active' | 'inactive';

export type UomUnitType = 'count' | 'distance' | 'duration' | 'mass' | 'service' | 'volume';

export type UomCategory = {
    id: string;
    name: string;
    type: UomUnitType;
};

export type UomUnit = {
    allowFractional: boolean;
    category: string;
    code: string;
    description?: string;
    id: string;
    isBase: boolean;
    name: string;
    precision: number;
    status: UomUnitStatus;
    symbol: string;
    type: UomUnitType;
    updatedAt: string;
    usableForCharge: boolean;
    usableForConsumption: boolean;
    usableForInventory: boolean;
    usableForIssue: boolean;
    usableForReceipt: boolean;
};

export type UomConversionDirection = 'bidirectional' | 'one_way';

export type UomConversion = {
    category: string;
    direction: UomConversionDirection;
    factor: string;
    fromUnitCode: string;
    fromUnitId: string;
    id: string;
    isActive: boolean;
    isItemSpecific: boolean;
    itemName?: string;
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
    charge: string;
    consumption: string;
    issue: string;
    receipt: string;
};

export type UomAuditEntry = {
    actor: string;
    description: string;
    id: string;
    time: string;
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
    usableForCharge: boolean;
    usableForConsumption: boolean;
    usableForInventory: boolean;
    usableForIssue: boolean;
    usableForReceipt: boolean;
};

export type UomConversionFormInput = {
    category: string;
    effectiveFrom?: string;
    effectiveTo?: string;
    factor: string;
    fromUnitId: string;
    isActive: boolean;
    isBidirectional: boolean;
    isItemSpecific: boolean;
    itemId?: string;
    quantity?: string;
    toUnitId: string;
};
