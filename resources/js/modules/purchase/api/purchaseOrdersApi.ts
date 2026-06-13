import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { PurchaseOrder, PurchaseOrderLine, PurchaseOrderPayload } from '../purchaseTypes';

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

export async function getSupplierItemMappings(supplierId: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Record<string, unknown>>>(
        `${endpoints.purchase}/suppliers/${supplierId}/item-mappings`,
        { params: { per_page: 50 }, signal },
    );
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
