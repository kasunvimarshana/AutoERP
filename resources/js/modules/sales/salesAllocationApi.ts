import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { SalesAllocation, SalesAllocationPayload } from './salesTypes';

const base = `${endpoints.sales}/allocations`;

export async function listSalesAllocations(params: ListParams, signal?: AbortSignal) {
    return (await apiClient.get<ApiCollection<SalesAllocation>>(base, { params, signal })).data;
}

export async function getSalesAllocation(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesAllocation>>(`${base}/${id}`, { signal })).data.data;
}

export async function createSalesAllocation(payload: SalesAllocationPayload) {
    return (await apiClient.post<ApiResource<SalesAllocation>>(base, payload)).data.data;
}

export async function releaseSalesAllocation(id: number) {
    return (await apiClient.patch<ApiResource<SalesAllocation>>(`${base}/${id}/release`)).data.data;
}
