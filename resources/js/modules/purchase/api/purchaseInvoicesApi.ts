import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { Invoice } from '@/modules/invoice/invoiceApi';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type { Payment } from '@/modules/payment/paymentApi';
import type { FastPurchaseOptionResource, PurchaseInvoicePayload, PurchasePaymentCreatePayload, PurchasePaymentPreview } from '../purchaseTypes';
import type { PurchasePaymentMethodOption } from '../components/PurchasePaymentMethodsEditor';

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
        payment_methods: PurchasePaymentMethodOption[];
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

export async function searchOutstandingSupplierInvoices({
    search,
    page,
    perPage,
    signal,
    supplierId,
}: LookupLoadParams & { supplierId?: number | null }): Promise<LookupResult<NamedResource>> {
    const response = await listOutstandingSupplierInvoices({
        search,
        page,
        supplier_id: supplierId ?? undefined,
        per_page: perPage,
    }, signal);

    return {
        data: response.data.map((invoice) => ({
            id: invoice.id,
            code: invoice.invoice_number,
            name: `${invoice.invoice_number ?? 'Invoice'}${invoice.party?.name ? ` - ${invoice.party.name}` : ''}`,
        })),
        links: response.links,
        meta: response.meta,
    };
}
