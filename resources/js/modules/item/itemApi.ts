import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import { createLocallyFilteredLookupLoader, createQueryCachedLookupLoader } from '@/shared/api/lookupCache';
import { requestLookup } from '@/shared/api/lookupRequest';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
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
    ItemBrandPayload,
    ItemCategoryPayload,
    ItemSummary,
    ItemUnit,
    ItemUnitPayload,
    ItemUsageRule,
    ItemUsageRulePayload,
    ItemUsageModule,
    ItemVariant,
    ItemVariantPayload,
    ItemWithRelationsPayload,
    BaseUomChangePayload,
    BaseUomConversionPreview,
    BaseUomRevision,
    BaseUomUsageAudit,
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

export function searchItems(params: LookupLoadParams, kind = ''): Promise<LookupResult<ItemSummary>> {
    const path = kind ? `${endpoints.items}/lookup/${kind}` : `${endpoints.items}/lookup`;
    const loader = createQueryCachedLookupLoader<ItemSummary>({
        key: `lookup:items:${kind || 'active'}`,
        load: (lookupParams) => requestLookup<ItemSummary>(path, lookupParams),
    });

    return loader(params);
}

export function searchItemCategories(params: LookupLoadParams): Promise<LookupResult<ItemCategory>> {
    const loader = createLocallyFilteredLookupLoader<ItemCategory>({
        key: 'lookup:item-categories',
        load: (lookupParams) => requestLookup<ItemCategory>(`${endpoints.itemCategories}/lookup`, lookupParams),
    });

    return loader(params);
}

export function searchItemBrands(params: LookupLoadParams): Promise<LookupResult<ItemBrand>> {
    const loader = createLocallyFilteredLookupLoader<ItemBrand>({
        key: 'lookup:item-brands',
        load: (lookupParams) => requestLookup<ItemBrand>(`${endpoints.itemBrands}/lookup`, lookupParams),
    });

    return loader(params);
}

export async function listItemCategories(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<ItemCategory>>(endpoints.itemCategories, { params, signal });
    return response.data;
}

export async function getItemCategory(id: number, signal?: AbortSignal): Promise<ItemCategory> {
    const response = await apiClient.get<ApiResource<ItemCategory>>(`${endpoints.itemCategories}/${id}`, { signal });
    return response.data.data;
}

export async function createItemCategory(payload: ItemCategoryPayload): Promise<ItemCategory> {
    const response = await apiClient.post<ApiResource<ItemCategory>>(endpoints.itemCategories, payload);
    return response.data.data;
}

export async function updateItemCategory(id: number, payload: ItemCategoryPayload): Promise<ItemCategory> {
    const response = await apiClient.put<ApiResource<ItemCategory>>(`${endpoints.itemCategories}/${id}`, payload);
    return response.data.data;
}

export async function deleteItemCategory(id: number): Promise<void> {
    await apiClient.delete(`${endpoints.itemCategories}/${id}`);
}

export async function listItemBrands(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<ItemBrand>>(endpoints.itemBrands, { params, signal });
    return response.data;
}

export async function getItemBrand(id: number, signal?: AbortSignal): Promise<ItemBrand> {
    const response = await apiClient.get<ApiResource<ItemBrand>>(`${endpoints.itemBrands}/${id}`, { signal });
    return response.data.data;
}

export async function createItemBrand(payload: ItemBrandPayload): Promise<ItemBrand> {
    const response = await apiClient.post<ApiResource<ItemBrand>>(endpoints.itemBrands, payload);
    return response.data.data;
}

export async function updateItemBrand(id: number, payload: ItemBrandPayload): Promise<ItemBrand> {
    const response = await apiClient.put<ApiResource<ItemBrand>>(`${endpoints.itemBrands}/${id}`, payload);
    return response.data.data;
}

export async function deleteItemBrand(id: number): Promise<void> {
    await apiClient.delete(`${endpoints.itemBrands}/${id}`);
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
export const supersedeItemPrice = (itemId: number, id: number, payload: ItemPricePayload) =>
    apiClient.post<ApiResource<ItemPrice>>(`${relationPath(itemId, 'prices')}/${id}/supersede`, payload).then((response) => response.data.data);

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

export async function listItemUsageModules(signal?: AbortSignal): Promise<ItemUsageModule[]> {
    const response = await apiClient.get<ApiResource<ItemUsageModule[]>>(`${endpoints.items}/usage-modules`, { signal });
    return response.data.data;
}

export async function getBaseUomUsageAudit(itemId: number, signal?: AbortSignal): Promise<BaseUomUsageAudit> {
    const response = await apiClient.get<ApiResource<BaseUomUsageAudit>>(`${endpoints.items}/${itemId}/base-uom/usage-audit`, { signal });
    return response.data.data;
}

export async function previewBaseUomChange(itemId: number, payload: BaseUomChangePayload): Promise<BaseUomConversionPreview> {
    const response = await apiClient.post<ApiResource<BaseUomConversionPreview>>(`${endpoints.items}/${itemId}/base-uom/preview-change`, payload);
    return response.data.data;
}

export async function applyBaseUomChange(itemId: number, payload: BaseUomChangePayload): Promise<BaseUomRevision> {
    const response = await apiClient.post<ApiResource<BaseUomRevision>>(`${endpoints.items}/${itemId}/base-uom/apply-change`, payload);
    return response.data.data;
}

export async function listBaseUomRevisions(itemId: number, signal?: AbortSignal): Promise<BaseUomRevision[]> {
    const response = await apiClient.get<ApiCollection<BaseUomRevision>>(`${endpoints.items}/${itemId}/base-uom/revisions`, {
        params: { per_page: 100 },
        signal,
    });
    return response.data.data;
}
