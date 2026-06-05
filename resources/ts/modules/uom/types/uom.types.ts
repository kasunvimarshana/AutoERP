export type UomStatus = 'active' | 'inactive';

export type UomLookup = {
    id: number;
    name: string;
    symbol?: string;
    uomCode: string;
};

export type UomListItem = UomLookup & {
    createdAt: string;
    decimalPrecision: number;
    isBase: boolean;
    organizationUnitId?: number;
    status: UomStatus;
    updatedAt: string;
};

export type Uom = UomListItem & {
    notes?: string;
    rowVersion: number;
    tenantId: number;
};

export type UomInput = {
    decimalPrecision: number;
    isBase: boolean;
    name: string;
    notes?: string;
    status: UomStatus;
    symbol?: string;
    uomCode: string;
};

export type UomPage = {
    items: UomListItem[];
    meta: {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
    };
};
