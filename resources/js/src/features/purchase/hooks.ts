import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { purchaseApi } from './api';
import type {
    GrnListFilters,
    GrnPayload,
    PurchaseInvoiceListFilters,
    PurchaseInvoicePayload,
    PurchaseOrderListFilters,
    PurchaseOrderPayload,
    PurchasePaymentPayload,
    PurchaseReturnListFilters,
    PurchaseReturnPayload,
} from './types';

const purchaseKeys = {
    all: ['purchase'] as const,
    purchaseOrders: (filters: PurchaseOrderListFilters) => [...purchaseKeys.all, 'purchase-orders', filters] as const,
    purchaseOrder: (purchaseOrderId: number) => [...purchaseKeys.all, 'purchase-order', purchaseOrderId] as const,
    grns: (filters: GrnListFilters) => [...purchaseKeys.all, 'grns', filters] as const,
    grn: (grnId: number) => [...purchaseKeys.all, 'grn', grnId] as const,
    purchaseInvoices: (filters: PurchaseInvoiceListFilters) => [...purchaseKeys.all, 'purchase-invoices', filters] as const,
    purchaseInvoice: (invoiceId: number) => [...purchaseKeys.all, 'purchase-invoice', invoiceId] as const,
    purchaseReturns: (filters: PurchaseReturnListFilters) => [...purchaseKeys.all, 'purchase-returns', filters] as const,
    purchaseReturn: (purchaseReturnId: number) => [...purchaseKeys.all, 'purchase-return', purchaseReturnId] as const,
};

export function usePurchaseOrders(filters: PurchaseOrderListFilters) {
    return useQuery({ queryKey: purchaseKeys.purchaseOrders(filters), queryFn: () => purchaseApi.listPurchaseOrders(filters) });
}

export function usePurchaseOrder(purchaseOrderId: number, enabled = true) {
    return useQuery({ queryKey: purchaseKeys.purchaseOrder(purchaseOrderId), queryFn: () => purchaseApi.getPurchaseOrder(purchaseOrderId), enabled });
}

export function useCreatePurchaseOrder() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: PurchaseOrderPayload) => purchaseApi.createPurchaseOrder(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'purchase-orders'] });
        },
    });
}

export function useUpdatePurchaseOrder(purchaseOrderId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: PurchaseOrderPayload) => purchaseApi.updatePurchaseOrder(purchaseOrderId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: purchaseKeys.purchaseOrder(purchaseOrderId) });
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'purchase-orders'] });
        },
    });
}

export function useConfirmPurchaseOrder(purchaseOrderId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => purchaseApi.confirmPurchaseOrder(purchaseOrderId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: purchaseKeys.purchaseOrder(purchaseOrderId) });
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'purchase-orders'] });
        },
    });
}

export function useCancelPurchaseOrder(purchaseOrderId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => purchaseApi.cancelPurchaseOrder(purchaseOrderId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: purchaseKeys.purchaseOrder(purchaseOrderId) });
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'purchase-orders'] });
        },
    });
}

export function useGrns(filters: GrnListFilters) {
    return useQuery({ queryKey: purchaseKeys.grns(filters), queryFn: () => purchaseApi.listGrns(filters) });
}

export function useGrn(grnId: number, enabled = true) {
    return useQuery({ queryKey: purchaseKeys.grn(grnId), queryFn: () => purchaseApi.getGrn(grnId), enabled });
}

export function useCreateGrn() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: GrnPayload) => purchaseApi.createGrn(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'grns'] });
        },
    });
}

export function useUpdateGrn(grnId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: GrnPayload) => purchaseApi.updateGrn(grnId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: purchaseKeys.grn(grnId) });
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'grns'] });
        },
    });
}

export function usePostGrn(grnId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => purchaseApi.confirmGrn(grnId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: purchaseKeys.grn(grnId) });
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'grns'] });
        },
    });
}

export function useCreatePurchaseInvoice() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: PurchaseInvoicePayload) => purchaseApi.createPurchaseInvoice(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'purchase-invoices'] });
        },
    });
}

export function useCreatePurchasePayment() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: PurchasePaymentPayload) => purchaseApi.createPurchasePayment(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'purchase-invoices'] });
        },
    });
}

export function usePurchaseInvoices(filters: PurchaseInvoiceListFilters) {
    return useQuery({ queryKey: purchaseKeys.purchaseInvoices(filters), queryFn: () => purchaseApi.listPurchaseInvoices(filters) });
}

export function usePurchaseInvoice(invoiceId: number, enabled = true) {
    return useQuery({ queryKey: purchaseKeys.purchaseInvoice(invoiceId), queryFn: () => purchaseApi.getPurchaseInvoice(invoiceId), enabled });
}

export function useApprovePurchaseInvoice(invoiceId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => purchaseApi.approvePurchaseInvoice(invoiceId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: purchaseKeys.purchaseInvoice(invoiceId) });
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'purchase-invoices'] });
        },
    });
}

export function usePurchaseReturns(filters: PurchaseReturnListFilters) {
    return useQuery({ queryKey: purchaseKeys.purchaseReturns(filters), queryFn: () => purchaseApi.listPurchaseReturns(filters) });
}

export function usePurchaseReturn(purchaseReturnId: number, enabled = true) {
    return useQuery({ queryKey: purchaseKeys.purchaseReturn(purchaseReturnId), queryFn: () => purchaseApi.getPurchaseReturn(purchaseReturnId), enabled });
}

export function useCreatePurchaseReturn() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: PurchaseReturnPayload) => purchaseApi.createPurchaseReturn(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'purchase-returns'] });
        },
    });
}

export function usePostPurchaseReturn(purchaseReturnId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: () => purchaseApi.approvePurchaseReturn(purchaseReturnId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: purchaseKeys.purchaseReturn(purchaseReturnId) });
            void queryClient.invalidateQueries({ queryKey: [...purchaseKeys.all, 'purchase-returns'] });
        },
    });
}
