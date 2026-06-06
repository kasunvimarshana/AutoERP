import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface Invoice extends Record<string, unknown> {
    id: number;
    invoice_number?: string;
    invoice_date?: string;
    invoice_type?: string;
    direction?: string;
    status?: string;
    party?: { id: number; name: string };
    grand_total?: string;
    balance_due?: string;
    balance?: Record<string, unknown>;
    lines?: Record<string, unknown>[];
}

export async function listInvoices(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Invoice>>(endpoints.invoices, { params, signal });
    return response.data;
}

export async function getInvoice(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Invoice>>(`${endpoints.invoices}/${id}`, { signal });
    return response.data.data;
}

export async function getInvoiceBalance(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>>>(`${endpoints.invoices}/${id}/balance`, { signal });
    return response.data.data;
}

export async function getInvoiceSources(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<{ sources: Record<string, unknown>[]; source_lines: Record<string, unknown>[] }>>(`${endpoints.invoices}/${id}/sources`, { signal });
    return response.data.data;
}

export async function getInvoiceAdjustments(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<Record<string, unknown>[]>>(`${endpoints.invoices}/${id}/adjustments`, { signal });
    return response.data.data;
}
