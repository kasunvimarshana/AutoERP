import { apiClient } from './apiClient';
import { endpoints } from './endpoints';
import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

export async function listUoms(search = '', signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(`${endpoints.uom}/units-of-measure`, {
        params: { search, per_page: 50, page: 1 },
        signal,
    });
    return response.data.data;
}

export async function searchWarehouses(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>('/api/warehouse/warehouses', {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data;
}
