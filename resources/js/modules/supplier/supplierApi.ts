import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { Supplier, SupplierPayload } from './types';

export async function listSuppliers(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Supplier>>(endpoints.suppliers, { params, signal });
    return response.data;
}

export async function getSupplier(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Supplier>>(`${endpoints.suppliers}/${id}`, { signal });
    return response.data.data;
}

export async function createSupplier(payload: SupplierPayload) {
    const response = await apiClient.post<ApiResource<Supplier>>(endpoints.suppliers, payload);
    return response.data.data;
}

export async function updateSupplier(id: number, payload: Partial<SupplierPayload>) {
    const response = await apiClient.patch<ApiResource<Supplier>>(`${endpoints.suppliers}/${id}`, payload);
    return response.data.data;
}

export async function searchSuppliers(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<Supplier>>(`${endpoints.suppliers}/lookup`, {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data;
}

export async function listSupplierCategories(signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await apiClient.get<ApiCollection<NamedResource>>(`${endpoints.suppliers}/categories/lookup`, { signal });
    return response.data.data;
}
