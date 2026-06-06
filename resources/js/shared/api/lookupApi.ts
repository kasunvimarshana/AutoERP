import { apiClient } from './apiClient';
import { endpoints } from './endpoints';
import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

async function lookup(url: string, search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(url, {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data;
}

export const lookupApi = {
    items: (search: string, signal?: AbortSignal) => lookup(`${endpoints.items}/lookup/active`, search, signal),
    stockableItems: (search: string, signal?: AbortSignal) => lookup(`${endpoints.items}/lookup/stockable`, search, signal),
    suppliers: (search: string, signal?: AbortSignal) => lookup(`${endpoints.suppliers}/lookup`, search, signal),
};
