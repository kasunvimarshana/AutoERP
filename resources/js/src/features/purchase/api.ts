import { ApiError, apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type {
    GrnListFilters,
    GrnPayload,
    GrnRecord,
    PurchaseInvoiceListFilters,
    PurchaseInvoicePayload,
    PurchaseInvoiceRecord,
    PurchaseOrderListFilters,
    PurchaseOrderPayload,
    PurchaseOrderRecord,
    PurchasePaymentPayload,
    PurchaseReturnListFilters,
    PurchaseReturnPayload,
    PurchaseReturnRecord,
} from './types';

async function workflowAction<T>(path: string) {
    try {
        return await apiClient.patch<ApiResourceEnvelope<T> | T>(path).then((result) => unwrapResource<T>(result));
    } catch (error) {
        if (error instanceof ApiError && (error.status === 404 || error.status === 405)) {
            return apiClient.post<ApiResourceEnvelope<T> | T>(path).then((result) => unwrapResource<T>(result));
        }

        throw error;
    }
}

export const purchaseApi = {
    listPurchaseOrders(filters: PurchaseOrderListFilters): Promise<PaginatedResult<PurchaseOrderRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<PurchaseOrderRecord>>('/purchase-orders', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated(payload));
    },
    getPurchaseOrder(purchaseOrderId: number) {
        return apiClient
            .get<ApiResourceEnvelope<PurchaseOrderRecord> | PurchaseOrderRecord>(`/purchase-orders/${purchaseOrderId}`)
            .then((payload) => unwrapResource(payload));
    },
    createPurchaseOrder(payload: PurchaseOrderPayload) {
        return apiClient
            .post<ApiResourceEnvelope<PurchaseOrderRecord> | PurchaseOrderRecord>('/purchase-orders', payload)
            .then((result) => unwrapResource(result));
    },
    updatePurchaseOrder(purchaseOrderId: number, payload: PurchaseOrderPayload) {
        return apiClient
            .put<ApiResourceEnvelope<PurchaseOrderRecord> | PurchaseOrderRecord>(`/purchase-orders/${purchaseOrderId}`, payload)
            .then((result) => unwrapResource(result));
    },
    deletePurchaseOrder(purchaseOrderId: number) {
        return apiClient.delete<null>(`/purchase-orders/${purchaseOrderId}`);
    },
    confirmPurchaseOrder(purchaseOrderId: number) {
        return workflowAction<PurchaseOrderRecord>(`/purchase-orders/${purchaseOrderId}/confirm`);
    },
    cancelPurchaseOrder(purchaseOrderId: number) {
        return workflowAction<PurchaseOrderRecord>(`/purchase-orders/${purchaseOrderId}/cancel`);
    },
    listGrns(filters: GrnListFilters): Promise<PaginatedResult<GrnRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<GrnRecord>>('/grns', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getGrn(grnId: number) {
        return apiClient.get<ApiResourceEnvelope<GrnRecord> | GrnRecord>(`/grns/${grnId}`).then((payload) => unwrapResource(payload));
    },
    createGrn(payload: GrnPayload) {
        return apiClient.post<ApiResourceEnvelope<GrnRecord> | GrnRecord>('/grns', payload).then((result) => unwrapResource(result));
    },
    updateGrn(grnId: number, payload: GrnPayload) {
        return apiClient.put<ApiResourceEnvelope<GrnRecord> | GrnRecord>(`/grns/${grnId}`, payload).then((result) => unwrapResource(result));
    },
    confirmGrn(grnId: number) {
        return workflowAction<GrnRecord>(`/grns/${grnId}/confirm`);
    },
    listPurchaseInvoices(filters: PurchaseInvoiceListFilters): Promise<PaginatedResult<PurchaseInvoiceRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<PurchaseInvoiceRecord>>('/invoices', { query: toQuery({ ...filters, direction: 'inbound' }) })
            .then((payload) => unwrapPaginated(payload));
    },
    getPurchaseInvoice(invoiceId: number) {
        return apiClient.get<ApiResourceEnvelope<PurchaseInvoiceRecord> | PurchaseInvoiceRecord>(`/invoices/${invoiceId}`).then((payload) => unwrapResource(payload));
    },
    createPurchaseInvoice(payload: PurchaseInvoicePayload) {
        return apiClient
            .post<ApiResourceEnvelope<PurchaseInvoiceRecord> | PurchaseInvoiceRecord>('/invoices', { ...payload, direction: 'inbound' })
            .then((result) => unwrapResource(result));
    },
    approvePurchaseInvoice(invoiceId: number) {
        return workflowAction<PurchaseInvoiceRecord>(`/invoices/${invoiceId}/approve`);
    },
    createPurchasePayment(payload: PurchasePaymentPayload) {
        return apiClient.post('/payments', { ...payload, direction: 'outbound' });
    },
    listPurchaseReturns(filters: PurchaseReturnListFilters): Promise<PaginatedResult<PurchaseReturnRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<PurchaseReturnRecord>>('/purchase-returns', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getPurchaseReturn(purchaseReturnId: number) {
        return apiClient
            .get<ApiResourceEnvelope<PurchaseReturnRecord> | PurchaseReturnRecord>(`/purchase-returns/${purchaseReturnId}`)
            .then((payload) => unwrapResource(payload));
    },
    createPurchaseReturn(payload: PurchaseReturnPayload) {
        return apiClient
            .post<ApiResourceEnvelope<PurchaseReturnRecord> | PurchaseReturnRecord>('/purchase-returns', payload)
            .then((result) => unwrapResource(result));
    },
    approvePurchaseReturn(purchaseReturnId: number) {
        return workflowAction<PurchaseReturnRecord>(`/purchase-returns/${purchaseReturnId}/approve`);
    },
};
