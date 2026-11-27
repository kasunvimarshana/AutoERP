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
    UploadTemplateResponse,
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
    const response = await apiClient.get<unknown>(`${endpoints.invoiceLayouts}/${encodeURIComponent(idOrCode)}/preview`, {
        params: invoiceId ? { invoice_id: invoiceId } : undefined,
        signal,
        headers: { Accept: 'application/json, text/html' },
    });

    if (typeof response.data === 'string') {
        return response.data;
    }

    if (
        response.data != null &&
        typeof response.data === 'object' &&
        'data' in response.data &&
        (response.data as { data: { html?: unknown } }).data?.html != null
    ) {
        return String((response.data as { data: { html: unknown } }).data.html);
    }

    return String(response.data);
}

export async function uploadInvoiceBladeTemplate(file: File, signal?: AbortSignal): Promise<UploadTemplateResponse> {
    const formData = new FormData();
    formData.append('template_file', file);
    const response = await apiClient.post<ApiResource<UploadTemplateResponse>>(
        `${endpoints.invoiceLayouts}/upload-template`,
        formData,
        {
            signal,
            headers: { 'Content-Type': 'multipart/form-data' },
        },
    );
    return response.data.data;
}

export function getInvoiceBladeExportUrl(idOrCode: number | string): string {
    return `${endpoints.invoiceLayouts}/${encodeURIComponent(idOrCode)}/export`;
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
    expectedVersion?: number,
    signal?: AbortSignal,
): Promise<{ success: boolean; invoice_id: number; invoice_layout_id: number | null; row_version?: number; layout: InvoiceLayout | null }> {
    const response = await apiClient.patch<ApiResource<{
        success: boolean;
        invoice_id: number;
        invoice_layout_id: number | null;
        row_version?: number;
        layout: InvoiceLayout | null;
    }>>(`${endpoints.invoices}/${invoiceId}/layout`, {
        invoice_layout_id: layoutId,
        ...(expectedVersion !== undefined ? { expected_version: expectedVersion } : {}),
    }, { signal });
    return response.data.data;
}
