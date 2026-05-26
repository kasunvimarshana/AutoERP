import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { warehousesApi } from './api';
import type { WarehouseListFilters, WarehouseLocationListFilters, WarehouseLocationPayload, WarehousePayload, WarehouseStockMovementFilters } from './types';

const warehouseKeys = {
    all: ['warehouses'] as const,
    lists: () => [...warehouseKeys.all, 'list'] as const,
    list: (filters: WarehouseListFilters) => [...warehouseKeys.lists(), filters] as const,
    details: () => [...warehouseKeys.all, 'detail'] as const,
    detail: (warehouseId: number) => [...warehouseKeys.details(), warehouseId] as const,
    locations: (warehouseId: number, filters: WarehouseLocationListFilters) => [...warehouseKeys.all, warehouseId, 'locations', filters] as const,
    stockLevels: (warehouseId: number, tenantId: number, page: number, perPage: number) =>
        [...warehouseKeys.all, warehouseId, 'stock-levels', tenantId, page, perPage] as const,
    stockMovements: (warehouseId: number, filters: WarehouseStockMovementFilters) => [...warehouseKeys.all, warehouseId, 'stock-movements', filters] as const,
};

export function useWarehouses(filters: WarehouseListFilters) {
    return useQuery({
        queryKey: warehouseKeys.list(filters),
        queryFn: () => warehousesApi.listWarehouses(filters),
    });
}

export function useWarehouse(warehouseId: number, enabled = true) {
    return useQuery({
        queryKey: warehouseKeys.detail(warehouseId),
        queryFn: () => warehousesApi.getWarehouse(warehouseId),
        enabled,
    });
}

export function useCreateWarehouse() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: WarehousePayload) => warehousesApi.createWarehouse(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: warehouseKeys.lists() });
        },
    });
}

export function useUpdateWarehouse(warehouseId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: WarehousePayload) => warehousesApi.updateWarehouse(warehouseId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: warehouseKeys.detail(warehouseId) });
            void queryClient.invalidateQueries({ queryKey: warehouseKeys.lists() });
        },
    });
}

export function useDeleteWarehouse() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (warehouseId: number) => warehousesApi.deleteWarehouse(warehouseId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: warehouseKeys.lists() });
        },
    });
}

export function useWarehouseLocations(warehouseId: number, filters: WarehouseLocationListFilters, enabled = true) {
    return useQuery({
        queryKey: warehouseKeys.locations(warehouseId, filters),
        queryFn: () => warehousesApi.listWarehouseLocations(warehouseId, filters),
        enabled,
    });
}

export function useCreateWarehouseLocation(warehouseId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: WarehouseLocationPayload) => warehousesApi.createWarehouseLocation(warehouseId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: warehouseKeys.detail(warehouseId) });
            void queryClient.invalidateQueries({ queryKey: [...warehouseKeys.all, warehouseId, 'locations'] });
        },
    });
}

export function useUpdateWarehouseLocation(warehouseId: number, locationId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: WarehouseLocationPayload) => warehousesApi.updateWarehouseLocation(warehouseId, locationId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...warehouseKeys.all, warehouseId, 'locations'] });
        },
    });
}

export function useDeleteWarehouseLocation(warehouseId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (locationId: number) => warehousesApi.deleteWarehouseLocation(warehouseId, locationId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: [...warehouseKeys.all, warehouseId, 'locations'] });
        },
    });
}

export function useWarehouseStockLevels(warehouseId: number, tenantId: number, page = 1, perPage = 25, enabled = true) {
    return useQuery({
        queryKey: warehouseKeys.stockLevels(warehouseId, tenantId, page, perPage),
        queryFn: () => warehousesApi.listWarehouseStockLevels(warehouseId, tenantId, page, perPage),
        enabled,
    });
}

export function useWarehouseStockMovements(warehouseId: number, filters: WarehouseStockMovementFilters, enabled = true) {
    return useQuery({
        queryKey: warehouseKeys.stockMovements(warehouseId, filters),
        queryFn: () => warehousesApi.listWarehouseStockMovements(warehouseId, filters),
        enabled,
    });
}
