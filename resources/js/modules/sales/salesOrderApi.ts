import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type {
    SalesAdjustmentCatalogueEntry,
    SalesDocumentPayload,
    SalesItemContext,
    SalesLineSummary,
    SalesOrder,
} from './salesTypes';

const base = `${endpoints.sales}/orders`;

export async function listSalesOrders(params: ListParams, signal?: AbortSignal) {
    return (await apiClient.get<ApiCollection<SalesOrder>>(base, { params, signal })).data;
}

export async function getSalesOrder(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesOrder>>(`${base}/${id}`, { signal })).data.data;
}

export async function createSalesOrder(payload: SalesDocumentPayload) {
    return (await apiClient.post<ApiResource<SalesOrder>>(base, payload)).data.data;
}

export async function updateSalesOrder(id: number, payload: SalesDocumentPayload) {
    return (await apiClient.put<ApiResource<SalesOrder>>(`${base}/${id}`, payload)).data.data;
}

export async function deleteSalesOrder(id: number) {
    await apiClient.delete(`${base}/${id}`);
}

export async function submitSalesOrder(id: number) {
    return (await apiClient.patch<ApiResource<SalesOrder>>(`${base}/${id}/submit`)).data.data;
}

export async function approveSalesOrder(id: number) {
    return (await apiClient.patch<ApiResource<SalesOrder>>(`${base}/${id}/approve`)).data.data;
}

export async function cancelSalesOrder(id: number) {
    return (await apiClient.patch<ApiResource<SalesOrder>>(`${base}/${id}/cancel`)).data.data;
}

export async function closeSalesOrder(id: number) {
    return (await apiClient.patch<ApiResource<SalesOrder>>(`${base}/${id}/close`)).data.data;
}

export async function getAllocatableSalesOrderLines(id: number, signal?: AbortSignal) {
    return (
        await apiClient.get<ApiResource<SalesLineSummary[]>>(`${base}/${id}/allocatable-lines`, {
            signal,
        })
    ).data.data;
}

export async function getDeliverableSalesOrderLines(id: number, signal?: AbortSignal) {
    return (
        await apiClient.get<ApiResource<SalesLineSummary[]>>(`${base}/${id}/deliverable-lines`, {
            signal,
        })
    ).data.data;
}

export async function getInvoiceableSalesOrderLines(id: number, signal?: AbortSignal) {
    return (
        await apiClient.get<ApiResource<SalesLineSummary[]>>(`${base}/${id}/invoiceable-lines`, {
            signal,
        })
    ).data.data;
}

export async function getSalesItemContext(itemId: number, params: {
    item_variant_id?: number;
    currency_id?: number;
    warehouse_id?: number;
    uom_id?: number;
    sales_date?: string;
} = {}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<SalesItemContext>>(`${endpoints.sales}/items/${itemId}/sales-context`, { params, signal });
    return response.data.data;
}

export async function getSalesAdjustmentCatalogue(signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<SalesAdjustmentCatalogueEntry[]>>(`${endpoints.sales}/adjustments/catalogue`, { signal });
    return response.data.data;
}

export async function searchSalesOrders({
    search,
    page,
    perPage,
    signal,
}: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    const response = await listSalesOrders({ search, page, per_page: perPage }, signal);

    return {
        data: response.data.map((order) => ({
            id: order.id,
            code: order.sales_order_number,
            name: `${order.sales_order_number ?? 'Sales order'}${
                order.customer?.name ? ` - ${order.customer.name}` : ''
            }`,
        })),
        links: response.links,
        meta: response.meta,
    };
}
