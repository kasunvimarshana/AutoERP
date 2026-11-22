/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type {
    CreateInvoiceLayoutPayload,
    InvoiceLayout,
    InvoiceLayoutFilterParams,
    SetDefaultInvoiceLayoutPayload,
    PrinterAssignmentsResponse,
    UpdateInvoiceLayoutPayload,
} from './invoiceLayoutTypes';

export async function listInvoiceLayouts(params?: InvoiceLayoutFilterParams, signal?: AbortSignal): Promise<InvoiceLayout[]> {
    const response = await apiClient.get<ApiResource<InvoiceLayout[]>>(endpoints.invoiceLayouts, {
        params,
        signal,
    });
    return response.data.data;
}

export async function getInvoiceLayout(id: number, signal?: AbortSignal): Promise<InvoiceLayout> {
    const response = await apiClient.get<ApiResource<InvoiceLayout>>(`${endpoints.invoiceLayouts}/${id}`, { signal });
    return response.data.data;
}

export async function createInvoiceLayout(payload: CreateInvoiceLayoutPayload): Promise<InvoiceLayout> {
    const response = await apiClient.post<ApiResource<InvoiceLayout>>(endpoints.invoiceLayouts, payload);
    return response.data.data;
}

export async function updateInvoiceLayout(id: number, payload: UpdateInvoiceLayoutPayload): Promise<InvoiceLayout> {
    const response = await apiClient.put<ApiResource<InvoiceLayout>>(`${endpoints.invoiceLayouts}/${id}`, payload);
    return response.data.data;
}

export async function deleteInvoiceLayout(id: number): Promise<{ message: string }> {
    const response = await apiClient.delete<{ message: string }>(`${endpoints.invoiceLayouts}/${id}`);
    return response.data;
}

export async function previewInvoiceLayout(
    idOrCode: number | string,
    invoiceId?: number,
    signal?: AbortSignal,
): Promise<string> {
    const response = await apiClient.get<string>(`${endpoints.invoiceLayouts}/${idOrCode}/preview`, {
        params: invoiceId ? { invoice_id: invoiceId } : undefined,
        signal,
        headers: { Accept: 'text/html' },
        responseType: 'text',
    });
    return response.data;
}

export async function setDefaultInvoiceLayout(payload: SetDefaultInvoiceLayoutPayload): Promise<{
    success: boolean;
    scope: string;
    code: string;
    printer?: string;
    organization_unit_id: number | null;
}> {
    const response = await apiClient.post<ApiResource<{
        success: boolean;
        scope: string;
        code: string;
        printer?: string;
        organization_unit_id: number | null;
    }>>(`${endpoints.invoiceLayouts}/set-default`, payload);
    return response.data.data;
}

export async function listPrinterAssignments(organizationUnitId?: number, signal?: AbortSignal): Promise<Record<string, string>> {
    const response = await apiClient.get<ApiResource<PrinterAssignmentsResponse>>(`${endpoints.invoiceLayouts}/printer-assignments`, {
        params: organizationUnitId ? { organization_unit_id: organizationUnitId } : undefined,
        signal,
    });
    return response.data.data.assignments;
}

export async function deletePrinterAssignment(printer: string, organizationUnitId?: number): Promise<{ success: boolean; printer: string }> {
    const response = await apiClient.delete<ApiResource<{ success: boolean; printer: string }>>(
        `${endpoints.invoiceLayouts}/printer-assignments/${encodeURIComponent(printer)}`,
        {
            data: organizationUnitId ? { organization_unit_id: organizationUnitId } : undefined,
        },
    );
    return response.data.data;
}

export async function assignInvoiceLayout(
    invoiceId: number,
    layoutId: number | null,
    signal?: AbortSignal,
): Promise<{ success: boolean; invoice_id: number; invoice_layout_id: number | null; layout: InvoiceLayout | null }> {
    const response = await apiClient.patch<ApiResource<{
        success: boolean;
        invoice_id: number;
        invoice_layout_id: number | null;
        layout: InvoiceLayout | null;
    }>>(`${endpoints.invoices}/${invoiceId}/layout`, {
        invoice_layout_id: layoutId,
    }, { signal });
    return response.data.data;
}
