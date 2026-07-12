import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    Invoice,
    InvoiceAdjustment,
    InvoiceBalanceResult,
    InvoiceSourcesResult,
} from './invoiceTypes';

export type {
    Invoice,
    InvoiceAdjustment,
    InvoiceBalanceResult,
    InvoiceSourcesResult,
} from './invoiceTypes';

export async function listInvoices(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Invoice>>(endpoints.invoices, { params, signal });
    return response.data;
}

export async function getInvoice(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Invoice>>(`${endpoints.invoices}/${id}`, { signal });
    return response.data.data;
}

export async function approveInvoice(id: number, expectedVersion: number) {
    const response = await apiClient.post<ApiResource<Invoice>>(`${endpoints.invoices}/${id}/approve`, {
        expected_version: expectedVersion,
    });
    return response.data.data;
}

export async function postInvoice(id: number, expectedVersion: number) {
    const response = await apiClient.post<ApiResource<Invoice>>(`${endpoints.invoices}/${id}/post`, {
        expected_version: expectedVersion,
    });
    return response.data.data;
}

export async function cancelInvoice(id: number, expectedVersion: number, reason?: string) {
    const response = await apiClient.post<ApiResource<Invoice>>(`${endpoints.invoices}/${id}/cancel`, {
        expected_version: expectedVersion,
        reason: reason?.trim() || undefined,
    });
    return response.data.data;
}

export async function getInvoiceBalance(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<InvoiceBalanceResult>>(`${endpoints.invoices}/${id}/balance`, { signal });
    return response.data.data;
}

export async function getInvoiceSources(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<InvoiceSourcesResult>>(`${endpoints.invoices}/${id}/sources`, { signal });
    return response.data.data;
}

export async function getInvoiceAdjustments(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<InvoiceAdjustment[]>>(`${endpoints.invoices}/${id}/adjustments`, { signal });
    return response.data.data;
}

export async function getInvoiceSignedPrintLink(id: number, signal?: AbortSignal) {
    const response = await apiClient.post<ApiResource<{ print_url: string; pdf_url: string }>>(
        `${endpoints.invoices}/${id}/signed-print`,
        null,
        { signal },
    );
    return response.data.data;
}
