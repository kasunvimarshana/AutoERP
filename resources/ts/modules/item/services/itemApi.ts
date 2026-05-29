import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    getItemById,
    itemAttributeValues,
    itemAttributes,
    itemAuditEntries,
    itemCategories,
    itemComboComponents,
    itemIdentifiers,
    itemInventorySummaries,
    itemPricingReferences,
    items,
    itemUnits,
    itemUsageSummaries,
    itemVariants,
} from '../mock/itemMock';
import type {
    Item,
    ItemAttribute,
    ItemAttributeValue,
    ItemAuditEntry,
    ItemCategory,
    ItemComboComponent,
    ItemFormInput,
    ItemIdentifier,
    ItemInventorySummary,
    ItemPricingReference,
    ItemStatus,
    ItemType,
    ItemUnit,
    ItemUsageSummary,
    ItemVariant,
} from '../types/item.types';

type BackendRecord = Record<string, unknown>;

const ITEM_API_MODE = import.meta.env.VITE_ITEM_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return ITEM_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (ITEM_API_MODE === 'real') {
            throw error;
        }

        if (error instanceof ApiError && !fallbackStatuses.includes(error.status)) {
            throw error;
        }

        return mockCall();
    }
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asBool(value: unknown, fallback = false) {
    return value === null || value === undefined ? fallback : Boolean(value);
}

function normalizeStatus(raw: BackendRecord): ItemStatus {
    if (raw.is_active === false) {
        return 'inactive';
    }

    const status = asString(raw.status, 'active').toLowerCase();
    return status === 'draft' || status === 'inactive' ? status : 'active';
}

function normalizeItemType(value: unknown): ItemType {
    const type = asString(value, 'inventory_product').toLowerCase().replaceAll('-', '_').replaceAll(' ', '_');
    const allowed: ItemType[] = ['combo', 'customer_supplied', 'external_service', 'fee_adjustment', 'inventory_product', 'labour', 'non_inventory', 'rental_charge', 'service'];

    if (type === 'stock' || type === 'stock_item' || type === 'product') {
        return 'inventory_product';
    }

    return allowed.includes(type as ItemType) ? (type as ItemType) : 'inventory_product';
}

function normalizeStockBehavior(raw: BackendRecord, itemType: ItemType): Item['stockBehavior'] {
    if (itemType === 'customer_supplied') {
        return 'reference_only';
    }

    return asBool(raw.is_stockable, itemType === 'inventory_product') ? 'stock_tracked' : 'no_stock_impact';
}

function normalizeItem(raw: BackendRecord): Item {
    const itemType = normalizeItemType(raw.type ?? raw.item_type ?? raw.item_type_code);

    return {
        baseUom: asString(raw.base_uom_name ?? raw.base_uom_code ?? raw.uom ?? raw.base_uom_id, 'Backend UOM'),
        brand: asString(raw.brand_name ?? raw.brand, ''),
        category: asString(raw.category_name ?? raw.category, 'Uncategorized'),
        code: asString(raw.sku ?? raw.code, 'ITM-MOCK'),
        description: asString(raw.description, ''),
        displayName: asString(raw.display_name ?? raw.name, 'Unnamed item'),
        id: asString(raw.id),
        itemType,
        name: asString(raw.name, 'Unnamed item'),
        status: normalizeStatus(raw),
        stockBehavior: normalizeStockBehavior(raw, itemType),
        updatedAt: asString(raw.updated_at ?? raw.updatedAt, 'Backend timestamp pending'),
    };
}

function toBackendItemPayload(input: ItemFormInput) {
    return {
        base_uom_id: Number(input.baseUomId) || 1,
        description: input.description || null,
        display_name: input.displayName || input.name,
        is_batch_tracked: input.trackBatch,
        is_chargeable: input.allowSales || input.allowServiceUsage || input.allowRentalUsage,
        is_purchasable: input.allowPurchase,
        is_rentable: input.allowRentalUsage,
        is_sellable: input.allowSales,
        is_serial_tracked: input.trackSerial,
        is_service: ['external_service', 'labour', 'service'].includes(input.itemType),
        is_stockable: input.stockable,
        name: input.name,
        sku: input.code,
        status: input.status,
        type: input.itemType,
    };
}

export const itemApi = {
    activateItem: (itemId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}/activate`, { method: 'PATCH' });
                return { ...response, data: normalizeItem(response.data) };
            },
            () => mockResponse({ ...getItemById(itemId), status: 'active' as ItemStatus }),
        ),
    createAttribute: (input: Partial<ItemAttribute>) =>
        withMockFallback(
            () => httpClient<ApiResponse<BackendRecord>>('/api/item/item-attributes', { body: { name: input.name, type: input.type }, method: 'POST' }),
            () => mockResponse({ id: 'mock-attribute', ...input }),
        ),
    createCategory: (input: Partial<ItemCategory>) =>
        withMockFallback(
            () => httpClient<ApiResponse<BackendRecord>>('/api/item/item-categories', { body: input, method: 'POST' }),
            () => mockResponse({ id: 'mock-category', ...input }),
        ),
    createItem: (input: ItemFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/item/items', {
                    body: toBackendItemPayload(input),
                    method: 'POST',
                });
                return { ...response, data: normalizeItem(response.data) };
            },
            () => mockResponse({ ...items[0], code: input.code, displayName: input.displayName || input.name, id: 'mock-item', itemType: input.itemType, name: input.name, status: input.status, stockBehavior: input.stockable ? 'stock_tracked' : 'no_stock_impact' }),
        ),
    createVariant: (itemId: string, input: Partial<ItemVariant>) =>
        withMockFallback(
            () => httpClient<ApiResponse<BackendRecord>>('/api/item/item-variants', { body: { item_id: itemId, ...input }, method: 'POST' }),
            () => mockResponse({ id: 'mock-variant', itemId, ...input }),
        ),
    deactivateItem: (itemId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}/deactivate`, { method: 'PATCH' });
                return { ...response, data: normalizeItem(response.data) };
            },
            () => mockResponse({ ...getItemById(itemId), status: 'inactive' as ItemStatus }),
        ),
    getInventorySummary: (itemId: string): Promise<ApiResponse<ItemInventorySummary>> =>
        mockResponse(itemInventorySummaries[itemId] ?? { availability: 'Backend stock availability preview', costPreview: 'Backend-owned cost', stockOnHand: 'Backend stock quantity', valuation: 'Backend valuation' }),
    getItem: (itemId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}`);
                return { ...response, data: normalizeItem(response.data) };
            },
            () => mockResponse(getItemById(itemId)),
        ),
    getItemActivity: (_itemId: string): Promise<ApiCollectionResponse<ItemAuditEntry>> => mockCollectionResponse(itemAuditEntries),
    getItemUnits: (itemId: string): Promise<ApiCollectionResponse<ItemUnit>> => mockCollectionResponse(itemUnits[itemId] ?? [{ id: 'unit-default', isBase: true, purpose: 'base', unit: getItemById(itemId).baseUom }]),
    getItemUsage: (itemId: string): Promise<ApiResponse<ItemUsageSummary>> =>
        mockResponse(itemUsageSummaries[itemId] ?? { inventoryUse: 'Backend usage pending', purchaseUse: 'Backend usage pending', rentalUse: 'Backend usage pending', salesUse: 'Backend usage pending', serviceUse: 'Backend usage pending' }),
    getPricingReferences: (itemId: string): Promise<ApiCollectionResponse<ItemPricingReference>> => mockCollectionResponse(itemPricingReferences[itemId] ?? []),
    listAttributeValues: (attributeId?: string): Promise<ApiCollectionResponse<ItemAttributeValue>> => mockCollectionResponse(attributeId ? itemAttributeValues.filter((value) => value.attributeId === attributeId) : itemAttributeValues),
    listAttributes: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-attributes');
                return { ...response, data: response.data.map((item) => ({ group: asString(item.group_name), id: asString(item.id), name: asString(item.name), type: asString(item.type, 'text') })) };
            },
            () => mockCollectionResponse(itemAttributes),
        ),
    listCategories: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-categories');
                return { ...response, data: response.data.map((item) => ({ code: asString(item.code), id: asString(item.id), name: asString(item.name), status: normalizeStatus(item) })) };
            },
            () => mockCollectionResponse(itemCategories),
        ),
    listComboComponents: (itemId: string): Promise<ApiCollectionResponse<ItemComboComponent>> => mockCollectionResponse(itemComboComponents[itemId] ?? []),
    listIdentifiers: (itemId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-identifiers', { query: { item_id: itemId } });
                return { ...response, data: response.data.map((item) => ({ id: asString(item.id), type: asString(item.type, 'barcode') as ItemIdentifier['type'], value: asString(item.value ?? item.identifier) })) };
            },
            () => mockCollectionResponse(itemIdentifiers[itemId] ?? []),
            [401, 403, 404, 419, 422],
        ),
    listItems: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/items');
                return { ...response, data: response.data.map(normalizeItem) };
            },
            () => mockCollectionResponse(items),
        ),
    listVariants: (itemId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-variants', { query: { item_id: itemId } });
                return { ...response, data: response.data.map((item) => ({ attributes: [], id: asString(item.id), isActive: asBool(item.is_active, true), name: asString(item.name), sku: asString(item.sku) })) };
            },
            () => mockCollectionResponse(itemVariants[itemId] ?? []),
            [401, 403, 404, 419, 422],
        ),
    removeComboComponent: (componentId: string) => mockResponse({ action: 'remove-combo-component', componentId }),
    removeIdentifier: (identifierId: string) =>
        withMockFallback(
            () => httpClient<ApiResponse<unknown>>(`/api/item/item-identifiers/${identifierId}`, { method: 'DELETE' }),
            () => mockResponse({ action: 'remove-identifier', identifierId }),
        ),
    removeItemUnit: (unitId: string) => mockResponse({ action: 'remove-item-unit', unitId }),
    updateAttribute: (attributeId: string, input: Partial<ItemAttribute>) =>
        withMockFallback(
            () => httpClient<ApiResponse<BackendRecord>>(`/api/item/item-attributes/${attributeId}`, { body: input, method: 'PUT' }),
            () => mockResponse({ id: attributeId, ...input }),
        ),
    updateCategory: (categoryId: string, input: Partial<ItemCategory>) =>
        withMockFallback(
            () => httpClient<ApiResponse<BackendRecord>>(`/api/item/item-categories/${categoryId}`, { body: input, method: 'PUT' }),
            () => mockResponse({ id: categoryId, ...input }),
        ),
    updateItem: (itemId: string, input: ItemFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}`, {
                    body: toBackendItemPayload(input),
                    method: 'PUT',
                });
                return { ...response, data: normalizeItem(response.data) };
            },
            () => mockResponse({ ...getItemById(itemId), code: input.code, displayName: input.displayName || input.name, itemType: input.itemType, name: input.name, status: input.status, stockBehavior: input.stockable ? 'stock_tracked' : 'no_stock_impact' }),
        ),
    updateVariant: (variantId: string, input: Partial<ItemVariant>) =>
        withMockFallback(
            () => httpClient<ApiResponse<BackendRecord>>(`/api/item/item-variants/${variantId}`, { body: input, method: 'PUT' }),
            () => mockResponse({ id: variantId, ...input }),
        ),
    upsertComboComponent: (itemId: string, input: Partial<ItemComboComponent>) => mockResponse({ id: input.id ?? 'mock-combo-component', itemId, ...input }),
    upsertIdentifier: (itemId: string, input: Partial<ItemIdentifier>) =>
        withMockFallback(
            () => httpClient<ApiResponse<BackendRecord>>('/api/item/item-identifiers', { body: { item_id: itemId, type: input.type, value: input.value }, method: 'POST' }),
            () => mockResponse({ id: input.id ?? 'mock-identifier', itemId, ...input }),
        ),
    upsertItemUnit: (itemId: string, input: Partial<ItemUnit>) => mockResponse({ id: input.id ?? 'mock-unit', itemId, ...input }),
};
