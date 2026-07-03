import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    ManualPurchaseReturnPayload,
    PurchaseActionPayload,
    PurchaseDebitNote,
    PurchaseDebitNoteAllocationPayload,
    PurchaseDebitNotePayload,
    PurchaseReturn,
    PurchasePostingResult,
    ReferencedPurchaseReturnPayload,
} from '../purchaseTypes';

export async function listPurchaseReturns(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PurchaseReturn>>(`${endpoints.purchase}/returns`, { params, signal });
    return response.data;
}

export async function getPurchaseReturn(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns/${id}`, { signal });
    return response.data.data;
}

export async function createPurchaseReturn(payload: ReferencedPurchaseReturnPayload) {
    const response = await apiClient.post<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns`, payload);
    return response.data.data;
}

export async function approvePurchaseReturn(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns/${id}/approve`, payload);
    return response.data.data;
}

export async function postPurchaseReturn(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<PurchasePostingResult>>(`${endpoints.purchase}/returns/${id}/post`, payload);
    return response.data.data;
}

export async function cancelPurchaseReturn(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns/${id}/cancel`, payload);
    return response.data.data;
}

export async function createManualSupplierReturn(payload: ManualPurchaseReturnPayload) {
    const response = await apiClient.post<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/manual-supplier-returns`, payload);
    return response.data.data;
}

export async function listPurchaseDebitNotes(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PurchaseDebitNote>>(`${endpoints.purchase}/debit-notes`, { params, signal });
    return response.data;
}

export async function getPurchaseDebitNote(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseDebitNote>>(`${endpoints.purchase}/debit-notes/${id}`, { signal });
    return response.data.data;
}

export async function createPurchaseDebitNote(payload: PurchaseDebitNotePayload) {
    const response = await apiClient.post<ApiResource<PurchaseDebitNote>>(`${endpoints.purchase}/debit-notes`, payload);
    return response.data.data;
}

export async function approvePurchaseDebitNote(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<PurchaseDebitNote>>(
        `${endpoints.purchase}/debit-notes/${id}/approve`,
        payload,
    );
    return response.data.data;
}

export async function postPurchaseDebitNote(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<PurchaseDebitNote>>(
        `${endpoints.purchase}/debit-notes/${id}/post`,
        payload,
    );
    return response.data.data;
}

export async function allocatePurchaseDebitNote(
    id: number,
    payload: PurchaseDebitNoteAllocationPayload,
) {
    const response = await apiClient.post<ApiResource<PurchaseDebitNote>>(
        `${endpoints.purchase}/debit-notes/${id}/allocations`,
        payload,
    );
    return response.data.data;
}
