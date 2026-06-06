import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    Item,
    ItemBrand,
    ItemBundle,
    ItemBundlePayload,
    ItemCategory,
    ItemCode,
    ItemCodePayload,
    ItemPayload,
    ItemPrice,
    ItemPricePayload,
    ItemSummary,
    ItemUnit,
    ItemUnitPayload,
    ItemUsageRule,
    ItemUsageRulePayload,
    ItemVariant,
    ItemVariantPayload,
    ItemWithRelationsPayload,
} from './itemTypes';

export async function listItems(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<ItemSummary>>(endpoints.items, { params, signal });
    return response.data;
}

export async function getItem(id: number, signal?: AbortSignal): Promise<Item> {
    const response = await apiClient.get<ApiResource<Item>>(`${endpoints.items}/${id}`, { signal });
    return response.data.data;
}

export async function createItem(payload: ItemPayload): Promise<Item> {
    const response = await apiClient.post<ApiResource<Item>>(endpoints.items, payload);
    return response.data.data;
}

export async function createItemWithRelations(payload: ItemWithRelationsPayload): Promise<Item> {
    const response = await apiClient.post<ApiResource<Item>>(`${endpoints.items}/with-relations`, payload);
    return response.data.data;
}

export async function updateItem(id: number, payload: Partial<ItemPayload>): Promise<Item> {
    const response = await apiClient.put<ApiResource<Item>>(`${endpoints.items}/${id}`, payload);
    return response.data.data;
}

export async function deleteItem(id: number): Promise<void> {
    await apiClient.delete(`${endpoints.items}/${id}`);
}

export async function setItemActive(id: number, active: boolean): Promise<Item> {
    const response = await apiClient.patch<ApiResource<Item>>(`${endpoints.items}/${id}/${active ? 'activate' : 'deactivate'}`);
    return response.data.data;
}

export async function searchItems(search: string, signal?: AbortSignal, kind = ''): Promise<ItemSummary[]> {
    const path = kind ? `${endpoints.items}/lookup/${kind}` : `${endpoints.items}/lookup`;
    const response = await apiClient.get<ApiCollection<ItemSummary>>(path, {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data;
}

export async function searchItemCategories(search: string, signal?: AbortSignal): Promise<ItemCategory[]> {
    const response = await apiClient.get<ApiCollection<ItemCategory>>(`${endpoints.itemCategories}/lookup`, {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data;
}

export async function searchItemBrands(search: string, signal?: AbortSignal): Promise<ItemBrand[]> {
    const response = await apiClient.get<ApiCollection<ItemBrand>>(`${endpoints.itemBrands}/lookup`, {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data;
}

const relationPath = (itemId: number, relation: string) => `${endpoints.items}/${itemId}/${relation}`;

export const listItemUnits = (itemId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<ItemUnit>>(relationPath(itemId, 'units'), { params, signal }).then((response) => response.data);
export const createItemUnit = (itemId: number, payload: ItemUnitPayload) =>
    apiClient.post<ApiResource<ItemUnit>>(relationPath(itemId, 'units'), payload).then((response) => response.data.data);
export const updateItemUnit = (itemId: number, id: number, payload: ItemUnitPayload) =>
    apiClient.put<ApiResource<ItemUnit>>(`${relationPath(itemId, 'units')}/${id}`, payload).then((response) => response.data.data);
export const deleteItemUnit = (itemId: number, id: number) => apiClient.delete(`${relationPath(itemId, 'units')}/${id}`);

export const listItemVariants = (itemId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<ItemVariant>>(relationPath(itemId, 'variants'), { params, signal }).then((response) => response.data);
export const createItemVariant = (itemId: number, payload: ItemVariantPayload) =>
    apiClient.post<ApiResource<ItemVariant>>(relationPath(itemId, 'variants'), payload).then((response) => response.data.data);
export const updateItemVariant = (itemId: number, id: number, payload: ItemVariantPayload) =>
    apiClient.put<ApiResource<ItemVariant>>(`${relationPath(itemId, 'variants')}/${id}`, payload).then((response) => response.data.data);
export const deleteItemVariant = (itemId: number, id: number) => apiClient.delete(`${relationPath(itemId, 'variants')}/${id}`);

export const listItemBundles = (itemId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<ItemBundle>>(relationPath(itemId, 'bundles'), { params, signal }).then((response) => response.data);
export const createItemBundle = (itemId: number, payload: ItemBundlePayload) =>
    apiClient.post<ApiResource<ItemBundle>>(relationPath(itemId, 'bundles'), payload).then((response) => response.data.data);
export const updateItemBundle = (itemId: number, id: number, payload: ItemBundlePayload) =>
    apiClient.put<ApiResource<ItemBundle>>(`${relationPath(itemId, 'bundles')}/${id}`, payload).then((response) => response.data.data);
export const deleteItemBundle = (itemId: number, id: number) => apiClient.delete(`${relationPath(itemId, 'bundles')}/${id}`);

export const listItemPrices = (itemId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<ItemPrice>>(relationPath(itemId, 'prices'), { params, signal }).then((response) => response.data);
export const createItemPrice = (itemId: number, payload: ItemPricePayload) =>
    apiClient.post<ApiResource<ItemPrice>>(relationPath(itemId, 'prices'), payload).then((response) => response.data.data);
export const updateItemPrice = (itemId: number, id: number, payload: ItemPricePayload) =>
    apiClient.put<ApiResource<ItemPrice>>(`${relationPath(itemId, 'prices')}/${id}`, payload).then((response) => response.data.data);
export const deleteItemPrice = (itemId: number, id: number) => apiClient.delete(`${relationPath(itemId, 'prices')}/${id}`);

export const listItemCodes = (itemId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<ItemCode>>(relationPath(itemId, 'codes'), { params, signal }).then((response) => response.data);
export const createItemCode = (itemId: number, payload: ItemCodePayload) =>
    apiClient.post<ApiResource<ItemCode>>(relationPath(itemId, 'codes'), payload).then((response) => response.data.data);
export const updateItemCode = (itemId: number, id: number, payload: ItemCodePayload) =>
    apiClient.put<ApiResource<ItemCode>>(`${relationPath(itemId, 'codes')}/${id}`, payload).then((response) => response.data.data);
export const deleteItemCode = (itemId: number, id: number) => apiClient.delete(`${relationPath(itemId, 'codes')}/${id}`);

export const listItemUsageRules = (itemId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<ItemUsageRule>>(relationPath(itemId, 'usage-rules'), { params, signal }).then((response) => response.data);
export const createItemUsageRule = (itemId: number, payload: ItemUsageRulePayload) =>
    apiClient.post<ApiResource<ItemUsageRule>>(relationPath(itemId, 'usage-rules'), payload).then((response) => response.data.data);
export const updateItemUsageRule = (itemId: number, id: number, payload: ItemUsageRulePayload) =>
    apiClient.put<ApiResource<ItemUsageRule>>(`${relationPath(itemId, 'usage-rules')}/${id}`, payload).then((response) => response.data.data);
export const deleteItemUsageRule = (itemId: number, id: number) => apiClient.delete(`${relationPath(itemId, 'usage-rules')}/${id}`);
