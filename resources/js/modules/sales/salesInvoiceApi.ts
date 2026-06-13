import type { Invoice } from '@/modules/invoice/invoiceApi';
import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type {
    SalesInvoicePayload,
    SalesInvoicePreview,
    SalesPaymentPayload,
} from './salesTypes';

export async function previewSalesInvoice(payload: SalesInvoicePayload) {
    return (
        await apiClient.post<ApiResource<SalesInvoicePreview>>(
            `${endpoints.sales}/invoices/preview`,
            payload,
        )
    ).data.data;
}

export async function createSalesInvoice(payload: SalesInvoicePayload) {
    return (
        await apiClient.post<ApiResource<Invoice>>(`${endpoints.sales}/invoices`, payload)
    ).data.data;
}

export async function prepareSalesPayment(payload: SalesPaymentPayload) {
    return (
        await apiClient.post<ApiResource<Record<string, unknown>>>(
            `${endpoints.sales}/payments/prepare`,
            payload,
        )
    ).data.data;
}
