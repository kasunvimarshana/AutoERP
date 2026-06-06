import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { Item, ItemPayload } from './types';

export async function listItems(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Item>>(endpoints.items, { params, signal });
    return response.data;
}

export async function getItem(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Item>>(`${endpoints.items}/${id}`, { signal });
    return response.data.data;
}

export async function createItem(payload: ItemPayload) {
    const response = await apiClient.post<ApiResource<Item>>(endpoints.items, payload);
    return response.data.data;
}

export async function updateItem(id: number, payload: Partial<ItemPayload>) {
    const response = await apiClient.patch<ApiResource<Item>>(`${endpoints.items}/${id}`, payload);
    return response.data.data;
}

export async function searchItems(search: string, signal?: AbortSignal, kind = 'active'): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<Item>>(`${endpoints.items}/lookup/${kind}`, {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data;
}

export async function listItemCategories(signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(`${endpoints.items}/categories/lookup`, { signal });
    return response.data.data;
}

export async function listItemBrands(signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(`${endpoints.items}/brands/lookup`, { signal });
    return response.data.data;
}
