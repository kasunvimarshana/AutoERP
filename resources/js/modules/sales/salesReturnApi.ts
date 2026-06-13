import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    SalesCreditNote,
    SalesCreditNoteAllocationPayload,
    SalesCreditNotePayload,
    SalesReturn,
    SalesReturnPayload,
} from './salesTypes';

const returnBase = `${endpoints.sales}/returns`;
const creditNoteBase = `${endpoints.sales}/credit-notes`;

export async function listSalesReturns(params: ListParams, signal?: AbortSignal) {
    return (await apiClient.get<ApiCollection<SalesReturn>>(returnBase, { params, signal })).data;
}

export async function getSalesReturn(id: number, signal?: AbortSignal) {
    return (
        await apiClient.get<ApiResource<SalesReturn>>(`${returnBase}/${id}`, { signal })
    ).data.data;
}

export async function createSalesReturn(payload: SalesReturnPayload) {
    return (await apiClient.post<ApiResource<SalesReturn>>(returnBase, payload)).data.data;
}

export async function approveSalesReturn(id: number) {
    return (
        await apiClient.patch<ApiResource<SalesReturn>>(`${returnBase}/${id}/approve`)
    ).data.data;
}

export async function postSalesReturn(id: number) {
    return (
        await apiClient.patch<ApiResource<Record<string, unknown>>>(`${returnBase}/${id}/post`)
    ).data.data;
}

export async function cancelSalesReturn(id: number) {
    return (
        await apiClient.patch<ApiResource<SalesReturn>>(`${returnBase}/${id}/cancel`)
    ).data.data;
}

export async function listSalesCreditNotes(params: ListParams, signal?: AbortSignal) {
    return (
        await apiClient.get<ApiCollection<SalesCreditNote>>(creditNoteBase, { params, signal })
    ).data;
}

export async function getSalesCreditNote(id: number, signal?: AbortSignal) {
    return (
        await apiClient.get<ApiResource<SalesCreditNote>>(`${creditNoteBase}/${id}`, { signal })
    ).data.data;
}

export async function createSalesCreditNote(payload: SalesCreditNotePayload) {
    return (
        await apiClient.post<ApiResource<SalesCreditNote>>(creditNoteBase, payload)
    ).data.data;
}

export async function approveSalesCreditNote(id: number) {
    return (
        await apiClient.patch<ApiResource<SalesCreditNote>>(`${creditNoteBase}/${id}/approve`)
    ).data.data;
}

export async function postSalesCreditNote(id: number) {
    return (
        await apiClient.patch<ApiResource<SalesCreditNote>>(`${creditNoteBase}/${id}/post`)
    ).data.data;
}

export async function allocateSalesCreditNote(
    id: number,
    payload: SalesCreditNoteAllocationPayload,
) {
    return (
        await apiClient.post<ApiResource<SalesCreditNote>>(
            `${creditNoteBase}/${id}/allocations`,
            payload,
        )
    ).data.data;
}
