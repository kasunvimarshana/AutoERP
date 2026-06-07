import { apiClient } from './apiClient';
import { endpoints } from './endpoints';
import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

export async function listUoms(search = '', signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(`${endpoints.uoms}/lookup`, {
        params: { search, per_page: 50, page: 1 },
        signal,
    });
    return response.data.data;
}

export async function searchWarehouses(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(endpoints.warehouses, {
        params: { search, is_active: true, per_page: 20 },
        signal,
    });
    return response.data.data;
}

export async function searchWarehouseLocations(search: string, signal?: AbortSignal, warehouseId?: number | null): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(endpoints.warehouseLocations, {
        params: { search, warehouse_id: warehouseId ?? undefined, is_active: true, per_page: 20 },
        signal,
    });
    return response.data.data;
}

export async function searchCurrencies(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(endpoints.currencies, {
        params: { search, is_active: true, per_page: 20 },
        signal,
    });
    return response.data.data;
}
