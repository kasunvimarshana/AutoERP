import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { salesApi } from './api';
import type {
    SalesInvoiceListFilters,
    SalesOrderListFilters,
    SalesOrderPayload,
    SalesReturnListFilters,
    ShipmentListFilters,
} from './types';

const salesKeys = {
    all: ['sales'] as const,
    salesOrders: (filters: SalesOrderListFilters) => [...salesKeys.all, 'sales-orders', filters] as const,
    salesOrder: (salesOrderId: number) => [...salesKeys.all, 'sales-order', salesOrderId] as const,
    shipments: (filters: ShipmentListFilters) => [...salesKeys.all, 'shipments', filters] as const,
    shipment: (shipmentId: number) => [...salesKeys.all, 'shipment', shipmentId] as const,
    salesInvoices: (filters: SalesInvoiceListFilters) => [...salesKeys.all, 'sales-invoices', filters] as const,
    salesInvoice: (invoiceId: number) => [...salesKeys.all, 'sales-invoice', invoiceId] as const,
    salesReturns: (filters: SalesReturnListFilters) => [...salesKeys.all, 'sales-returns', filters] as const,
    salesReturn: (salesReturnId: number) => [...salesKeys.all, 'sales-return', salesReturnId] as const,
};

export function useSalesOrders(filters: SalesOrderListFilters) {
    return useQuery({ queryKey: salesKeys.salesOrders(filters), queryFn: () => salesApi.listSalesOrders(filters) });
}

export function useSalesOrder(salesOrderId: number, enabled = true) {
    return useQuery({ queryKey: salesKeys.salesOrder(salesOrderId), queryFn: () => salesApi.getSalesOrder(salesOrderId), enabled });
}

export function useCreateSalesOrder() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: SalesOrderPayload) => salesApi.createSalesOrder(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...salesKeys.all, 'sales-orders'] });
        },
    });
}

export function useConfirmSalesOrder(salesOrderId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => salesApi.confirmSalesOrder(salesOrderId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: salesKeys.salesOrder(salesOrderId) });
            void queryClient.invalidateQueries({ queryKey: [...salesKeys.all, 'sales-orders'] });
        },
    });
}

export function useCancelSalesOrder(salesOrderId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => salesApi.cancelSalesOrder(salesOrderId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: salesKeys.salesOrder(salesOrderId) });
            void queryClient.invalidateQueries({ queryKey: [...salesKeys.all, 'sales-orders'] });
        },
    });
}

export function useShipments(filters: ShipmentListFilters) {
    return useQuery({ queryKey: salesKeys.shipments(filters), queryFn: () => salesApi.listShipments(filters) });
}

export function useShipment(shipmentId: number, enabled = true) {
    return useQuery({ queryKey: salesKeys.shipment(shipmentId), queryFn: () => salesApi.getShipment(shipmentId), enabled });
}

export function useProcessShipment(shipmentId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => salesApi.processShipment(shipmentId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: salesKeys.shipment(shipmentId) });
            void queryClient.invalidateQueries({ queryKey: [...salesKeys.all, 'shipments'] });
        },
    });
}

export function useSalesInvoices(filters: SalesInvoiceListFilters) {
    return useQuery({ queryKey: salesKeys.salesInvoices(filters), queryFn: () => salesApi.listSalesInvoices(filters) });
}

export function useSalesInvoice(invoiceId: number, enabled = true) {
    return useQuery({ queryKey: salesKeys.salesInvoice(invoiceId), queryFn: () => salesApi.getSalesInvoice(invoiceId), enabled });
}

export function usePostSalesInvoice(invoiceId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => salesApi.postSalesInvoice(invoiceId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: salesKeys.salesInvoice(invoiceId) });
            void queryClient.invalidateQueries({ queryKey: [...salesKeys.all, 'sales-invoices'] });
        },
    });
}

export function useSalesReturns(filters: SalesReturnListFilters) {
    return useQuery({ queryKey: salesKeys.salesReturns(filters), queryFn: () => salesApi.listSalesReturns(filters) });
}

export function useSalesReturn(salesReturnId: number, enabled = true) {
    return useQuery({ queryKey: salesKeys.salesReturn(salesReturnId), queryFn: () => salesApi.getSalesReturn(salesReturnId), enabled });
}

export function useApproveSalesReturn(salesReturnId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => salesApi.approveSalesReturn(salesReturnId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: salesKeys.salesReturn(salesReturnId) });
            void queryClient.invalidateQueries({ queryKey: [...salesKeys.all, 'sales-returns'] });
        },
    });
}

export function useReceiveSalesReturn(salesReturnId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => salesApi.receiveSalesReturn(salesReturnId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: salesKeys.salesReturn(salesReturnId) });
            void queryClient.invalidateQueries({ queryKey: [...salesKeys.all, 'sales-returns'] });
        },
    });
}
