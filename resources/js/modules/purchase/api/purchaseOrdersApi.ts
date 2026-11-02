import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type {
    PurchaseAdjustmentCatalogueEntry,
    PurchaseActionPayload,
    PurchaseItemContext,
    PurchaseOrder,
    PurchaseOrderCreateContext,
    PurchaseOrderLine,
    PurchaseOrderPayload,
    PurchaseSupplierContext,
} from '../purchaseTypes';

export async function listPurchaseOrders(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PurchaseOrder>>(`${endpoints.purchase}/orders`, { params, signal });
    return response.data;
}

export async function listReceivablePurchaseOrders(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PurchaseOrder>>(
        `${endpoints.purchase}/eligible/receivable-purchase-orders`,
        { params, signal },
    );
    return response.data;
}

export async function listInvoiceablePurchaseOrders(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<PurchaseOrder>>(
        `${endpoints.purchase}/eligible/invoiceable-purchase-orders`,
        { params, signal },
    );
    return response.data;
}

export async function getPurchaseOrder(id: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}`, { signal });
    return response.data.data;
}

export async function getPurchaseOrderCreateContext(signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseOrderCreateContext>>(`${endpoints.purchase}/orders/create-context`, { signal });
    return response.data.data;
}

export async function getPurchaseSupplierContext(supplierId: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseSupplierContext>>(`${endpoints.purchase}/suppliers/${supplierId}/purchase-context`, { signal });
    return response.data.data;
}

export async function getPurchaseItemContext(itemId: number, params: {
    supplier_id?: number;
    item_variant_id?: number;
    currency_id?: number;
    warehouse_id?: number;
    uom_id?: number;
    purchase_date?: string;
} = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseItemContext>>(`${endpoints.purchase}/items/${itemId}/purchase-context`, { params, signal });
    return response.data.data;
}

export async function getPurchaseAdjustmentCatalogue(signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<PurchaseAdjustmentCatalogueEntry[]>>(`${endpoints.purchase}/adjustments/catalogue`, { signal });
    return response.data.data;
}

export async function getPurchaseWarehouseLocations(warehouseId: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<NamedResource[]>>(`${endpoints.purchase}/warehouses/${warehouseId}/locations`, { params: { per_page: 50 }, signal });
    return response.data.data;
}

export async function createPurchaseOrder(payload: PurchaseOrderPayload) {
    const response = await apiClient.post<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders`, payload);
    return response.data.data;
}

export async function updatePurchaseOrder(id: number, payload: PurchaseOrderPayload & PurchaseActionPayload) {
    const response = await apiClient.put<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}`, payload);
    return response.data.data;
}

export async function deletePurchaseOrder(id: number, payload: PurchaseActionPayload) {
    await apiClient.delete(`${endpoints.purchase}/orders/${id}`, { data: payload });
}

export async function approvePurchaseOrder(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/approve`, payload);
    return response.data.data;
}

export async function cancelPurchaseOrder(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/cancel`, payload);
    return response.data.data;
}

export async function closePurchaseOrder(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/close`, payload);
    return response.data.data;
}

export async function submitPurchaseOrder(id: number, payload: PurchaseActionPayload) {
    const response = await apiClient.patch<ApiResource<PurchaseOrder>>(`${endpoints.purchase}/orders/${id}/submit`, payload);
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

export async function searchPurchaseOrders({
    search,
    page,
    perPage,
    signal,
}: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    const response = await listPurchaseOrders({ search, page, per_page: perPage }, signal);
    return {
        data: response.data.map((order) => ({
            id: order.id,
            code: order.purchase_order_number,
            name: `${order.purchase_order_number ?? 'Purchase order'}${order.supplier?.name ? ` - ${order.supplier.name}` : ''}`,
        })),
        links: response.links,
        meta: response.meta,
    };
}

export async function searchReceivablePurchaseOrders({
    search,
    page,
    perPage,
    signal,
}: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    const response = await listReceivablePurchaseOrders({ search, page, per_page: perPage }, signal);
    return purchaseOrderLookupResult(response);
}

export async function searchInvoiceablePurchaseOrders({
    search,
    page,
    perPage,
    signal,
}: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    const response = await listInvoiceablePurchaseOrders({ search, page, per_page: perPage }, signal);
    return purchaseOrderLookupResult(response);
}

function purchaseOrderLookupResult(response: ApiCollection<PurchaseOrder>): LookupResult<NamedResource> {
    return {
        data: response.data.map((order) => ({
            id: order.id,
            code: order.purchase_order_number,
            name: `${order.purchase_order_number ?? 'Purchase order'}${order.supplier?.name ? ` - ${order.supplier.name}` : ''}`,
        })),
        links: response.links,
        meta: response.meta,
    };
}
