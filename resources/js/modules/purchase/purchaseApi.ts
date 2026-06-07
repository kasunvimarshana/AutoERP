import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type {
    GoodsReceipt,
    GoodsReceiptPayload,
    InventoryAdjustmentRequestPayload,
    PurchaseDebitNote,
    PurchaseDebitNotePayload,
    PurchaseInvoicePayload,
    PurchaseOrder,
    PurchaseOrderLine,
    PurchaseOrderPayload,
    PurchasePaymentPreparePayload,
    PurchaseReturn,
    PurchaseReturnPayload,
    ReturnableLine,
} from './purchaseTypes';

export type {
    GoodsReceipt,
    GoodsReceiptLine,
    GoodsReceiptPayload,
    GoodsReceiptStatus,
    InventoryAdjustmentRequestPayload,
    PurchaseDebitNote,
    PurchaseDebitNotePayload,
    PurchaseHeaderAdjustment,
    PurchaseInvoicePayload,
    PurchaseOrder,
    PurchaseOrderLine,
    PurchaseOrderPayload,
    PurchaseOrderStatus,
    PurchasePaymentPreparePayload,
    PurchaseReturn,
    PurchaseReturnLine,
    PurchaseReturnPayload,
    PurchaseReturnStatus,
    ReturnableLine,
    SourceSummary,
} from './purchaseTypes';

export async function listPurchaseOrders(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PurchaseOrder>>(`${endpoints.purchase}/orders`, { params, signal });
    return response.data;
}

export async function getPurchaseOrder(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}`, { signal });
    return response.data.data;
}

export async function createPurchaseOrder(payload: PurchaseOrderPayload) {
    const response = await apiClient.post<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders`, payload);
    return response.data.data;
}

export async function updatePurchaseOrder(id: number, payload: PurchaseOrderPayload) {
    const response = await apiClient.put<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}`, payload);
    return response.data.data;
}

export async function deletePurchaseOrder(id: number) {
    await apiClient.delete(`${endpoints.purchase}/orders/${id}`);
}

export async function approvePurchaseOrder(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/approve`);
    return response.data.data;
}

export async function cancelPurchaseOrder(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/cancel`);
    return response.data.data;
}

export async function closePurchaseOrder(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/close`);
    return response.data.data;
}

export async function submitPurchaseOrder(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/submit`);
    return response.data.data;
}

export async function getReceivablePurchaseOrderLines(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseOrderLine[]>>(`${endpoints.purchase}/orders/${id}/receivable-lines`, { signal });
    return response.data.data;
}

export async function getInvoiceablePurchaseOrderLines(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseOrderLine[]>>(`${endpoints.purchase}/orders/${id}/invoiceable-lines`, { signal });
    return response.data.data;
}

export async function listGoodsReceipts(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts`, { params, signal });
    return response.data;
}

export async function getGoodsReceipt(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}`, { signal });
    return response.data.data;
}

export async function createGoodsReceipt(payload: GoodsReceiptPayload) {
    const response = await apiClient.post<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts`, payload);
    return response.data.data;
}

export async function postGoodsReceipt(id: number) {
    const response = await apiClient.patch<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}/post`);
    return response.data.data;
}

export async function reverseGoodsReceipt(id: number) {
    const response = await apiClient.patch<ApiResource<GoodsReceipt>>(`${endpoints.purchase}/goods-receipts/${id}/reverse`);
    return response.data.data;
}

export async function getReturnableGoodsReceiptLines(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<ReturnableLine[]>>(`${endpoints.purchase}/goods-receipts/${id}/returnable-lines`, { signal });
    return response.data.data;
}

export async function listPurchaseReturns(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PurchaseReturn>>(`${endpoints.purchase}/returns`, { params, signal });
    return response.data;
}

export async function getPurchaseReturn(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns/${id}`, { signal });
    return response.data.data;
}

export async function createPurchaseReturn(payload: PurchaseReturnPayload) {
    const response = await apiClient.post<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns`, payload);
    return response.data.data;
}

export async function approvePurchaseReturn(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns/${id}/approve`);
    return response.data.data;
}

export async function postPurchaseReturn(id: number) {
    const response = await apiClient.patch<ApiResource<Record<string, unknown>>>(`${endpoints.purchase}/returns/${id}/post`);
    return response.data.data;
}

export async function cancelPurchaseReturn(id: number) {
    const response = await apiClient.patch<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/returns/${id}/cancel`);
    return response.data.data;
}

export async function createManualSupplierReturn(payload: PurchaseReturnPayload) {
    const response = await apiClient.post<ApiResource<PurchaseReturn>>(`${endpoints.purchase}/manual-supplier-returns`, payload);
    return response.data.data;
}

export async function previewPurchaseInvoice(payload: PurchaseInvoicePayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.purchase}/invoices/preview`, payload);
    return response.data.data;
}

export async function createPurchaseInvoice(payload: PurchaseInvoicePayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.purchase}/invoices`, payload);
    return response.data.data;
}

export async function preparePurchasePayment(payload: PurchasePaymentPreparePayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.purchase}/payments/prepare`, payload);
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

export async function createPurchaseInventoryAdjustmentRequest(payload: InventoryAdjustmentRequestPayload) {
    const response = await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoints.purchase}/inventory-adjustment-requests`, payload);
    return response.data.data;
}

export async function getSupplierItemMappings(supplierId: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Record<string, unknown>>>(`${endpoints.purchase}/suppliers/${supplierId}/item-mappings`, { params: { per_page: 50 }, signal });
    return response.data;
}

export async function searchPurchaseOrders(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await listPurchaseOrders({ search, per_page: 20 }, signal);
    return response.data.map((order) => ({
        id: order.id,
        code: order.purchase_order_number,
        name: `${order.purchase_order_number ?? 'Purchase order'}${order.supplier?.name ? ` - ${order.supplier.name}` : ''}`,
    }));
}

export async function searchGoodsReceipts(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await listGoodsReceipts({ search, per_page: 20 }, signal);
    return response.data.map((grn) => ({
        id: grn.id,
        code: grn.grn_number,
        name: `${grn.grn_number ?? 'Goods receipt'}${grn.supplier?.name ? ` - ${grn.supplier.name}` : ''}`,
    }));
}
