import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { Invoice } from '@/modules/invoice/invoiceApi';
import type { Payment } from '@/modules/payment/paymentApi';
import type { FastPurchaseOptionResource, PurchaseInvoicePayload, PurchasePaymentCreatePayload, PurchasePaymentPreview } from '../purchaseTypes';

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

export async function preparePurchasePayment(payload: PurchasePaymentCreatePayload) {
    const response = await apiClient.post<ApiResource<PurchasePaymentPreview>>(
        `${endpoints.purchase}/payments/prepare`,
        payload,
    );
    return response.data.data;
}

export async function createPurchasePayment(payload: PurchasePaymentCreatePayload) {
    const response = await apiClient.post<ApiResource<Payment>>(
        `${endpoints.purchase}/payments`,
        payload,
    );
    return response.data.data;
}

export async function getPurchasePaymentContext(signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<{
        payment_methods: FastPurchaseOptionResource[];
        payment_accounts: FastPurchaseOptionResource[];
    }>>(`${endpoints.purchase}/payments/context`, { signal });
    return response.data.data;
}

export async function listOutstandingSupplierInvoices(params: ListParams & { supplier_id?: number }, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Invoice>>(
        `${endpoints.purchase}/eligible/outstanding-supplier-invoices`,
        { params, signal },
    );
    return response.data;
}
