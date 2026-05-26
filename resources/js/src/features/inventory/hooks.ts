import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { inventoryApi } from './api';
import type {
    CycleCountCompletePayload,
    CycleCountListFilters,
    CycleCountPayload,
    InventoryWarehouseFilters,
    ReleaseExpiredPayload,
    StockReservationListFilters,
    StockReservationPayload,
    TransferOrderListFilters,
    TransferOrderPayload,
    TransferOrderReceivePayload,
    ValuationConfigListFilters,
    ValuationConfigPayload,
} from './types';

const inventoryKeys = {
    all: ['inventory'] as const,
    stockLevels: (warehouseId: number, tenantId: number, page: number, perPage: number) => [...inventoryKeys.all, 'stock-levels', warehouseId, tenantId, page, perPage] as const,
    stockMovements: (warehouseId: number, filters: InventoryWarehouseFilters) => [...inventoryKeys.all, 'stock-movements', warehouseId, filters] as const,
    transferOrders: (filters: TransferOrderListFilters) => [...inventoryKeys.all, 'transfer-orders', filters] as const,
    transferOrder: (transferOrderId: number, tenantId: number) => [...inventoryKeys.all, 'transfer-order', transferOrderId, tenantId] as const,
    cycleCounts: (filters: CycleCountListFilters) => [...inventoryKeys.all, 'cycle-counts', filters] as const,
    cycleCount: (cycleCountId: number, tenantId: number) => [...inventoryKeys.all, 'cycle-count', cycleCountId, tenantId] as const,
    reservations: (filters: StockReservationListFilters) => [...inventoryKeys.all, 'reservations', filters] as const,
    valuationConfigs: (filters: ValuationConfigListFilters) => [...inventoryKeys.all, 'valuation-configs', filters] as const,
};

export function useInventoryStockLevels(warehouseId: number, tenantId: number, page = 1, perPage = 25, enabled = true) {
    return useQuery({
        queryKey: inventoryKeys.stockLevels(warehouseId, tenantId, page, perPage),
        queryFn: () => inventoryApi.listWarehouseStockLevels(warehouseId, tenantId, page, perPage),
        enabled,
    });
}

export function useInventoryStockMovements(warehouseId: number, filters: InventoryWarehouseFilters, enabled = true) {
    return useQuery({
        queryKey: inventoryKeys.stockMovements(warehouseId, filters),
        queryFn: () => inventoryApi.listWarehouseStockMovements(warehouseId, filters),
        enabled,
    });
}

export function useTransferOrders(filters: TransferOrderListFilters) {
    return useQuery({
        queryKey: inventoryKeys.transferOrders(filters),
        queryFn: () => inventoryApi.listTransferOrders(filters),
    });
}

export function useTransferOrder(transferOrderId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: inventoryKeys.transferOrder(transferOrderId, tenantId),
        queryFn: () => inventoryApi.getTransferOrder(transferOrderId, tenantId),
        enabled,
    });
}

export function useCreateTransferOrder() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: TransferOrderPayload) => inventoryApi.createTransferOrder(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'transfer-orders'] });
        },
    });
}

export function useApproveTransferOrder(transferOrderId: number, tenantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => inventoryApi.approveTransferOrder(transferOrderId, tenantId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: inventoryKeys.transferOrder(transferOrderId, tenantId) });
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'transfer-orders'] });
        },
    });
}

export function useReceiveTransferOrder(transferOrderId: number, tenantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: TransferOrderReceivePayload) => inventoryApi.receiveTransferOrder(transferOrderId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: inventoryKeys.transferOrder(transferOrderId, tenantId) });
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'transfer-orders'] });
        },
    });
}

export function useCycleCounts(filters: CycleCountListFilters) {
    return useQuery({
        queryKey: inventoryKeys.cycleCounts(filters),
        queryFn: () => inventoryApi.listCycleCounts(filters),
    });
}

export function useCycleCount(cycleCountId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: inventoryKeys.cycleCount(cycleCountId, tenantId),
        queryFn: () => inventoryApi.getCycleCount(cycleCountId, tenantId),
        enabled,
    });
}

export function useCreateCycleCount() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CycleCountPayload) => inventoryApi.createCycleCount(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'cycle-counts'] });
        },
    });
}

export function useStartCycleCount(cycleCountId: number, tenantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => inventoryApi.startCycleCount(cycleCountId, tenantId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: inventoryKeys.cycleCount(cycleCountId, tenantId) });
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'cycle-counts'] });
        },
    });
}

export function useCompleteCycleCount(cycleCountId: number, tenantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: CycleCountCompletePayload) => inventoryApi.completeCycleCount(cycleCountId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: inventoryKeys.cycleCount(cycleCountId, tenantId) });
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'cycle-counts'] });
        },
    });
}

export function useStockReservations(filters: StockReservationListFilters) {
    return useQuery({
        queryKey: inventoryKeys.reservations(filters),
        queryFn: () => inventoryApi.listStockReservations(filters),
    });
}

export function useCreateStockReservation() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: StockReservationPayload) => inventoryApi.createStockReservation(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'reservations'] });
        },
    });
}

export function useDeleteStockReservation(tenantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (reservationId: number) => inventoryApi.deleteStockReservation(reservationId, tenantId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'reservations'] });
        },
    });
}

export function useReleaseExpiredReservations() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ReleaseExpiredPayload) => inventoryApi.releaseExpiredReservations(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'reservations'] });
        },
    });
}

export function useValuationConfigs(filters: ValuationConfigListFilters) {
    return useQuery({
        queryKey: inventoryKeys.valuationConfigs(filters),
        queryFn: () => inventoryApi.listValuationConfigs(filters),
    });
}

export function useCreateValuationConfig() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ValuationConfigPayload) => inventoryApi.createValuationConfig(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'valuation-configs'] });
        },
    });
}

export function useUpdateValuationConfig(configId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ValuationConfigPayload) => inventoryApi.updateValuationConfig(configId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'valuation-configs'] });
        },
    });
}

export function useDeleteValuationConfig(tenantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (configId: number) => inventoryApi.deleteValuationConfig(configId, tenantId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...inventoryKeys.all, 'valuation-configs'] });
        },
    });
}
