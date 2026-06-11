import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type {
    SalesCreditNote,
    SalesCreditNotePayload,
    SalesDelivery,
    SalesDeliveryLine,
    SalesDeliveryPayload,
    SalesDocumentPayload,
    SalesInvoicePayload,
    SalesOrder,
    SalesPaymentPayload,
    SalesQuotation,
    SalesReturn,
    SalesReturnPayload,
} from './salesTypes';

export type * from './salesTypes';

const base = endpoints.sales;

export async function listSalesQuotations(params: ListParams, signal?: AbortSignal) {
    return (await apiClient.get<ApiCollection<SalesQuotation>>(`${base}/quotations`, { params, signal })).data;
}
export async function getSalesQuotation(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesQuotation>>(`${base}/quotations/${id}`, { signal })).data.data;
}
export async function createSalesQuotation(payload: SalesDocumentPayload) {
    return (await apiClient.post<ApiResource<SalesQuotation>>(`${base}/quotations`, payload)).data.data;
}
export async function updateSalesQuotation(id: number, payload: SalesDocumentPayload) {
    return (await apiClient.put<ApiResource<SalesQuotation>>(`${base}/quotations/${id}`, payload)).data.data;
}
export async function deleteSalesQuotation(id: number) {
    await apiClient.delete(`${base}/quotations/${id}`);
}
export async function sendSalesQuotation(id: number) {
    return (await apiClient.patch<ApiResource<SalesQuotation>>(`${base}/quotations/${id}/send`)).data.data;
}
export async function acceptSalesQuotation(id: number) {
    return (await apiClient.patch<ApiResource<SalesQuotation>>(`${base}/quotations/${id}/accept`)).data.data;
}
export async function rejectSalesQuotation(id: number) {
    return (await apiClient.patch<ApiResource<SalesQuotation>>(`${base}/quotations/${id}/reject`)).data.data;
}
export async function convertSalesQuotation(id: number, payload: { sales_order_date: string; warehouse_id?: number }) {
    return (await apiClient.post<ApiResource<SalesOrder>>(`${base}/quotations/${id}/convert-to-order`, payload)).data.data;
}

export async function listSalesOrders(params: ListParams, signal?: AbortSignal) {
    return (await apiClient.get<ApiCollection<SalesOrder>>(`${base}/orders`, { params, signal })).data;
}
export async function getSalesOrder(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesOrder>>(`${base}/orders/${id}`, { signal })).data.data;
}
export async function createSalesOrder(payload: SalesDocumentPayload) {
    return (await apiClient.post<ApiResource<SalesOrder>>(`${base}/orders`, payload)).data.data;
}
export async function updateSalesOrder(id: number, payload: SalesDocumentPayload) {
    return (await apiClient.put<ApiResource<SalesOrder>>(`${base}/orders/${id}`, payload)).data.data;
}
export async function deleteSalesOrder(id: number) {
    await apiClient.delete(`${base}/orders/${id}`);
}
export async function submitSalesOrder(id: number) {
    return (await apiClient.patch<ApiResource<SalesOrder>>(`${base}/orders/${id}/submit`)).data.data;
}
export async function approveSalesOrder(id: number) {
    return (await apiClient.patch<ApiResource<SalesOrder>>(`${base}/orders/${id}/approve`)).data.data;
}
export async function cancelSalesOrder(id: number) {
    return (await apiClient.patch<ApiResource<SalesOrder>>(`${base}/orders/${id}/cancel`)).data.data;
}
export async function closeSalesOrder(id: number) {
    return (await apiClient.patch<ApiResource<SalesOrder>>(`${base}/orders/${id}/close`)).data.data;
}
export async function getAllocatableSalesOrderLines(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesLineSummary[]>>(`${base}/orders/${id}/allocatable-lines`, { signal })).data.data;
}
export async function getDeliverableSalesOrderLines(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesLineSummary[]>>(`${base}/orders/${id}/deliverable-lines`, { signal })).data.data;
}
export async function getInvoiceableSalesOrderLines(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesLineSummary[]>>(`${base}/orders/${id}/invoiceable-lines`, { signal })).data.data;
}

export async function listSalesDeliveries(params: ListParams, signal?: AbortSignal) {
    return (await apiClient.get<ApiCollection<SalesDelivery>>(`${base}/deliveries`, { params, signal })).data;
}
export async function getSalesDelivery(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesDelivery>>(`${base}/deliveries/${id}`, { signal })).data.data;
}
export async function createSalesDelivery(payload: SalesDeliveryPayload) {
    return (await apiClient.post<ApiResource<SalesDelivery>>(`${base}/deliveries`, payload)).data.data;
}
export async function postSalesDelivery(id: number) {
    return (await apiClient.patch<ApiResource<SalesDelivery>>(`${base}/deliveries/${id}/post`)).data.data;
}
export async function reverseSalesDelivery(id: number) {
    return (await apiClient.patch<ApiResource<SalesDelivery>>(`${base}/deliveries/${id}/reverse`)).data.data;
}
export async function getReturnableSalesDeliveryLines(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<ReturnableSalesLine[]>>(`${base}/deliveries/${id}/returnable-lines`, { signal })).data.data;
}

export async function previewSalesInvoice(payload: SalesInvoicePayload) {
    return (await apiClient.post<ApiResource<Record<string, unknown>>>(`${base}/invoices/preview`, payload)).data.data;
}
export async function createSalesInvoice(payload: SalesInvoicePayload) {
    return (await apiClient.post<ApiResource<Record<string, unknown>>>(`${base}/invoices`, payload)).data.data;
}
export async function prepareSalesPayment(payload: SalesPaymentPayload) {
    return (await apiClient.post<ApiResource<Record<string, unknown>>>(`${base}/payments/prepare`, payload)).data.data;
}

export async function listSalesReturns(params: ListParams, signal?: AbortSignal) {
    return (await apiClient.get<ApiCollection<SalesReturn>>(`${base}/returns`, { params, signal })).data;
}
export async function getSalesReturn(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesReturn>>(`${base}/returns/${id}`, { signal })).data.data;
}
export async function createSalesReturn(payload: SalesReturnPayload) {
    return (await apiClient.post<ApiResource<SalesReturn>>(`${base}/returns`, payload)).data.data;
}
export async function approveSalesReturn(id: number) {
    return (await apiClient.patch<ApiResource<SalesReturn>>(`${base}/returns/${id}/approve`)).data.data;
}
export async function postSalesReturn(id: number) {
    return (await apiClient.patch<ApiResource<Record<string, unknown>>>(`${base}/returns/${id}/post`)).data.data;
}
export async function cancelSalesReturn(id: number) {
    return (await apiClient.patch<ApiResource<SalesReturn>>(`${base}/returns/${id}/cancel`)).data.data;
}

export async function listSalesCreditNotes(params: ListParams, signal?: AbortSignal) {
    return (await apiClient.get<ApiCollection<SalesCreditNote>>(`${base}/credit-notes`, { params, signal })).data;
}
export async function getSalesCreditNote(id: number, signal?: AbortSignal) {
    return (await apiClient.get<ApiResource<SalesCreditNote>>(`${base}/credit-notes/${id}`, { signal })).data.data;
}
export async function createSalesCreditNote(payload: SalesCreditNotePayload) {
    return (await apiClient.post<ApiResource<SalesCreditNote>>(`${base}/credit-notes`, payload)).data.data;
}

export interface SalesLineSummary {
    id: number;
    sales_order_line_id?: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    unit_price: string;
    remaining_quantity?: string;
    remaining_allocatable_quantity?: string;
    remaining_deliverable_quantity?: string;
    remaining_invoiceable_quantity?: string;
}

export interface ReturnableSalesLine {
    id: number;
    source_line_type: 'sales_delivery_line';
    source_line_id: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    returnable_quantity: string;
    unit_price: string;
}

export async function searchSalesOrders(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await listSalesOrders({ search, per_page: 20 }, signal);
    return response.data.map((order) => ({
        id: order.id,
        code: order.sales_order_number,
        name: `${order.sales_order_number ?? 'Sales order'}${order.customer?.name ? ` - ${order.customer.name}` : ''}`,
    }));
}

export async function searchSalesDeliveries(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await listSalesDeliveries({ search, per_page: 20 }, signal);
    return response.data.map((delivery) => ({
        id: delivery.id,
        code: delivery.delivery_number,
        name: `${delivery.delivery_number ?? 'Delivery'}${delivery.customer?.name ? ` - ${delivery.customer.name}` : ''}`,
    }));
}
