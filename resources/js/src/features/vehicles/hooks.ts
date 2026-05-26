import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { vehiclesApi } from './api';
import type { VehicleListFilters, VehiclePayload, VehicleStatusPayload } from './types';

const vehicleKeys = {
    all: ['vehicles'] as const,
    dashboard: (tenantId: number) => [...vehicleKeys.all, 'dashboard', tenantId] as const,
    lists: () => [...vehicleKeys.all, 'list'] as const,
    list: (filters: VehicleListFilters) => [...vehicleKeys.lists(), filters] as const,
    details: () => [...vehicleKeys.all, 'detail'] as const,
    detail: (vehicleId: number, tenantId: number) => [...vehicleKeys.details(), vehicleId, tenantId] as const,
};

export function useVehicleDashboard(tenantId: number) {
    return useQuery({
        queryKey: vehicleKeys.dashboard(tenantId),
        queryFn: () => vehiclesApi.getDashboard(tenantId),
    });
}

export function useVehicles(filters: VehicleListFilters) {
    return useQuery({
        queryKey: vehicleKeys.list(filters),
        queryFn: () => vehiclesApi.listVehicles(filters),
    });
}

export function useVehicle(vehicleId: number, tenantId: number, enabled = true) {
    return useQuery({
        queryKey: vehicleKeys.detail(vehicleId, tenantId),
        queryFn: () => vehiclesApi.getVehicle(vehicleId, tenantId),
        enabled,
    });
}

export function useCreateVehicle() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: VehiclePayload) => vehiclesApi.createVehicle(payload),
        onSuccess: (vehicle) => {
            void queryClient.invalidateQueries({ queryKey: vehicleKeys.lists() });
            void queryClient.invalidateQueries({ queryKey: vehicleKeys.dashboard(vehicle.tenant_id) });
        },
    });
}

export function useUpdateVehicle(vehicleId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: VehiclePayload) => vehiclesApi.updateVehicle(vehicleId, payload),
        onSuccess: (vehicle) => {
            void queryClient.invalidateQueries({ queryKey: vehicleKeys.detail(vehicleId, vehicle.tenant_id) });
            void queryClient.invalidateQueries({ queryKey: vehicleKeys.lists() });
            void queryClient.invalidateQueries({ queryKey: vehicleKeys.dashboard(vehicle.tenant_id) });
        },
    });
}

export function useDeleteVehicle(tenantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (vehicleId: number) => vehiclesApi.deleteVehicle(vehicleId, tenantId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: vehicleKeys.lists() });
            void queryClient.invalidateQueries({ queryKey: vehicleKeys.dashboard(tenantId) });
        },
    });
}

export function useUpdateVehicleStatus(vehicleId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: VehicleStatusPayload) => vehiclesApi.updateVehicleStatus(vehicleId, payload),
        onSuccess: (vehicle) => {
            void queryClient.invalidateQueries({ queryKey: vehicleKeys.detail(vehicleId, vehicle.tenant_id) });
            void queryClient.invalidateQueries({ queryKey: vehicleKeys.lists() });
            void queryClient.invalidateQueries({ queryKey: vehicleKeys.dashboard(vehicle.tenant_id) });
        },
    });
}
