import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type { PurchaseInvoicePayload, PurchasePaymentPreparePayload } from '../purchaseTypes';

export async function previewPurchaseInvoice(payload: PurchaseInvoicePayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(
        `${endpoints.purchase}/invoices/preview`,
        payload,
    );
    return response.data.data;
}

export async function createPurchaseInvoice(payload: PurchaseInvoicePayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(
        `${endpoints.purchase}/invoices`,
        payload,
    );
    return response.data.data;
}

export async function preparePurchasePayment(payload: PurchasePaymentPreparePayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(
        `${endpoints.purchase}/payments/prepare`,
        payload,
    );
    return response.data.data;
}
