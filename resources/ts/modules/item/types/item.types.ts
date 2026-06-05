import type { UomLookup } from '../../uom/types/uom.types';

export type ItemStatus = 'active' | 'inactive';
export type ItemType = 'inventory' | 'service' | 'non_inventory';

export type ItemListItem = {
    barcode?: string;
    baseUom: UomLookup;
    costPrice: string;
    createdAt: string;
    displayName?: string;
    id: number;
    isServiceItem: boolean;
    isStockItem: boolean;
    itemCode: string;
    itemType?: ItemType;
    name: string;
    organizationUnitId?: number;
    reorderLevel: string;
    reorderQuantity: string;
    salesPrice: string;
    sku?: string;
    status: ItemStatus;
    trackInventory: boolean;
    updatedAt: string;
};

export type Item = ItemListItem & {
    description?: string;
    notes?: string;
    purchaseUom: UomLookup | null;
    rowVersion: number;
    salesUom: UomLookup | null;
    tenantId: number;
};

export type ItemInput = {
    barcode?: string;
    baseUomId?: number;
    costPrice: string;
    description?: string;
    displayName?: string;
    isServiceItem: boolean;
    isStockItem: boolean;
    itemCode: string;
    itemType?: ItemType;
    name: string;
    notes?: string;
    purchaseUomId?: number;
    reorderLevel: string;
    reorderQuantity: string;
    salesPrice: string;
    salesUomId?: number;
    sku?: string;
    status: ItemStatus;
    trackInventory: boolean;
};

export type ItemPage = {
    items: ItemListItem[];
    meta: {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
    };
};
