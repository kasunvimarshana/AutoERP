import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type { UomLookup } from '../../uom/types/uom.types';
import type { Item, ItemInput, ItemListItem, ItemPage, ItemStatus, ItemType } from '../types/item.types';

type UomRecord = { id: number; name: string; symbol?: string | null; uom_code: string };
type ItemRecord = {
    barcode?: string | null;
    base_uom: UomRecord;
    cost_price: string;
    created_at: string;
    description?: string | null;
    display_name?: string | null;
    id: number;
    is_service_item: boolean;
    is_stock_item: boolean;
    item_code: string;
    item_type?: ItemType | null;
    name: string;
    notes?: string | null;
    organization_unit_id?: number | null;
    purchase_uom?: UomRecord | null;
    reorder_level: string;
    reorder_quantity: string;
    row_version?: number;
    sales_price: string;
    sales_uom?: UomRecord | null;
    sku?: string | null;
    status: ItemStatus;
    tenant_id?: number;
    track_inventory: boolean;
    updated_at: string;
};

function uom(record: UomRecord): UomLookup {
    return { id: record.id, name: record.name, symbol: record.symbol ?? undefined, uomCode: record.uom_code };
}

function listItem(record: ItemRecord): ItemListItem {
    return {
        barcode: record.barcode ?? undefined,
        baseUom: uom(record.base_uom),
        costPrice: record.cost_price,
        createdAt: record.created_at,
        displayName: record.display_name ?? undefined,
        id: record.id,
        isServiceItem: record.is_service_item,
        isStockItem: record.is_stock_item,
        itemCode: record.item_code,
        itemType: record.item_type ?? undefined,
        name: record.name,
        organizationUnitId: record.organization_unit_id ?? undefined,
        reorderLevel: record.reorder_level,
        reorderQuantity: record.reorder_quantity,
        salesPrice: record.sales_price,
        sku: record.sku ?? undefined,
        status: record.status,
        trackInventory: record.track_inventory,
        updatedAt: record.updated_at,
    };
}

function detail(record: ItemRecord): Item {
    return {
        ...listItem(record),
        description: record.description ?? undefined,
        notes: record.notes ?? undefined,
        purchaseUom: record.purchase_uom ? uom(record.purchase_uom) : null,
        rowVersion: record.row_version ?? 1,
        salesUom: record.sales_uom ? uom(record.sales_uom) : null,
        tenantId: record.tenant_id ?? 0,
    };
}

function optional(value?: string) {
    return value?.trim() || null;
}

function payload(input: ItemInput) {
    return {
        barcode: optional(input.barcode),
        base_uom_id: input.baseUomId,
        cost_price: input.costPrice,
        description: optional(input.description),
        display_name: optional(input.displayName),
        is_service_item: input.isServiceItem,
        is_stock_item: input.isStockItem,
        item_code: input.itemCode.trim(),
        item_type: input.itemType || null,
        name: input.name.trim(),
        notes: optional(input.notes),
        purchase_uom_id: input.purchaseUomId,
        reorder_level: input.reorderLevel,
        reorder_quantity: input.reorderQuantity,
        sales_price: input.salesPrice,
        sales_uom_id: input.salesUomId,
        sku: optional(input.sku),
        status: input.status,
        track_inventory: input.trackInventory,
    };
}

export const itemApi = {
    async create(input: ItemInput): Promise<Item> {
        const response = await httpClient<ApiResponse<ItemRecord>>('/api/item/items', { body: payload(input), method: 'POST' });

        return detail(response.data);
    },
    async get(id: number): Promise<Item> {
        const response = await httpClient<ApiResponse<ItemRecord>>(`/api/item/items/${id}`);

        return detail(response.data);
    },
    async list(query: { page: number; perPage: number; search?: string; status?: ItemStatus }): Promise<ItemPage> {
        const response = await httpClient<ApiCollectionResponse<ItemRecord>>('/api/item/items', {
            query: { page: query.page, per_page: query.perPage, search: query.search, status: query.status },
        });

        return {
            items: response.data.map(listItem),
            meta: {
                currentPage: response.meta?.current_page ?? query.page,
                lastPage: response.meta?.last_page ?? 1,
                perPage: response.meta?.per_page ?? query.perPage,
                total: response.meta?.total ?? response.data.length,
            },
        };
    },
    async remove(id: number): Promise<void> {
        await httpClient<void>(`/api/item/items/${id}`, { method: 'DELETE' });
    },
    async update(id: number, input: ItemInput): Promise<Item> {
        const response = await httpClient<ApiResponse<ItemRecord>>(`/api/item/items/${id}`, { body: payload(input), method: 'PUT' });

        return detail(response.data);
    },
};
