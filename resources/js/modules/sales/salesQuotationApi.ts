import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { SalesDocumentPayload, SalesOrder, SalesQuotation } from './salesTypes';

const base = `${endpoints.sales}/quotations`;

export async function listSalesQuotations(params: ListParams, signal?: AbortSignal) {
    return (await apiClient.get<ApiCollection<SalesQuotation>>(base, { params, signal })).data;
}

export async function getSalesQuotation(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesQuotation>>(`${base}/${id}`, { signal })).data.data;
}

export async function createSalesQuotation(payload: SalesDocumentPayload) {
    return (await apiClient.post<ApiResource<SalesQuotation>>(base, payload)).data.data;
}

export async function updateSalesQuotation(id: number, payload: SalesDocumentPayload) {
    return (await apiClient.put<ApiResource<SalesQuotation>>(`${base}/${id}`, payload)).data.data;
}

export async function deleteSalesQuotation(id: number) {
    await apiClient.delete(`${base}/${id}`);
}

export async function sendSalesQuotation(id: number) {
    return (await apiClient.patch<ApiResource<SalesQuotation>>(`${base}/${id}/send`)).data.data;
}

export async function acceptSalesQuotation(id: number) {
    return (await apiClient.patch<ApiResource<SalesQuotation>>(`${base}/${id}/accept`)).data.data;
}

export async function rejectSalesQuotation(id: number) {
    return (await apiClient.patch<ApiResource<SalesQuotation>>(`${base}/${id}/reject`)).data.data;
}

export async function convertSalesQuotation(
    id: number,
    payload: { sales_order_date: string; warehouse_id?: number },
) {
    return (
        await apiClient.post<ApiResource<SalesOrder>>(`${base}/${id}/convert-to-order`, payload)
    ).data.data;
}
