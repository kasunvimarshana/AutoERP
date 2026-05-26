import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type {
    SalesInvoiceListFilters,
    SalesInvoiceRecord,
    SalesOrderListFilters,
    SalesOrderPayload,
    SalesOrderRecord,
    SalesReturnListFilters,
    SalesReturnRecord,
    ShipmentListFilters,
    ShipmentRecord,
} from './types';

export const salesApi = {
    listSalesOrders(filters: SalesOrderListFilters): Promise<PaginatedResult<SalesOrderRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<SalesOrderRecord>>('/sales-orders', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getSalesOrder(salesOrderId: number) {
        return apiClient.get<ApiResourceEnvelope<SalesOrderRecord> | SalesOrderRecord>(`/sales-orders/${salesOrderId}`).then((payload) => unwrapResource(payload));
    },
    createSalesOrder(payload: SalesOrderPayload) {
        return apiClient.post<ApiResourceEnvelope<SalesOrderRecord> | SalesOrderRecord>('/sales-orders', payload).then((result) => unwrapResource(result));
    },
    confirmSalesOrder(salesOrderId: number) {
        return apiClient.post<ApiResourceEnvelope<SalesOrderRecord> | SalesOrderRecord>(`/sales-orders/${salesOrderId}/confirm`).then((result) => unwrapResource(result));
    },
    cancelSalesOrder(salesOrderId: number) {
        return apiClient.post<ApiResourceEnvelope<SalesOrderRecord> | SalesOrderRecord>(`/sales-orders/${salesOrderId}/cancel`).then((result) => unwrapResource(result));
    },
    listShipments(filters: ShipmentListFilters): Promise<PaginatedResult<ShipmentRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<ShipmentRecord>>('/shipments', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getShipment(shipmentId: number) {
        return apiClient.get<ApiResourceEnvelope<ShipmentRecord> | ShipmentRecord>(`/shipments/${shipmentId}`).then((payload) => unwrapResource(payload));
    },
    processShipment(shipmentId: number) {
        return apiClient.post<ApiResourceEnvelope<ShipmentRecord> | ShipmentRecord>(`/shipments/${shipmentId}/process`).then((result) => unwrapResource(result));
    },
    listSalesInvoices(filters: SalesInvoiceListFilters): Promise<PaginatedResult<SalesInvoiceRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<SalesInvoiceRecord>>('/sales-invoices', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getSalesInvoice(invoiceId: number) {
        return apiClient.get<ApiResourceEnvelope<SalesInvoiceRecord> | SalesInvoiceRecord>(`/sales-invoices/${invoiceId}`).then((payload) => unwrapResource(payload));
    },
    postSalesInvoice(invoiceId: number) {
        return apiClient.post<ApiResourceEnvelope<SalesInvoiceRecord> | SalesInvoiceRecord>(`/sales-invoices/${invoiceId}/post`).then((result) => unwrapResource(result));
    },
    listSalesReturns(filters: SalesReturnListFilters): Promise<PaginatedResult<SalesReturnRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<SalesReturnRecord>>('/sales-returns', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getSalesReturn(salesReturnId: number) {
        return apiClient.get<ApiResourceEnvelope<SalesReturnRecord> | SalesReturnRecord>(`/sales-returns/${salesReturnId}`).then((payload) => unwrapResource(payload));
    },
    approveSalesReturn(salesReturnId: number) {
        return apiClient.post<ApiResourceEnvelope<SalesReturnRecord> | SalesReturnRecord>(`/sales-returns/${salesReturnId}/approve`).then((result) => unwrapResource(result));
    },
    receiveSalesReturn(salesReturnId: number) {
        return apiClient.post<ApiResourceEnvelope<SalesReturnRecord> | SalesReturnRecord>(`/sales-returns/${salesReturnId}/receive`).then((result) => unwrapResource(result));
    },
};
