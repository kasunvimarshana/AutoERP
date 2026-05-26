import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { jobCardsApi } from './api';
import type { VehicleJobCardListFilters, VehicleJobCardPayload } from './types';

const jobCardKeys = {
    all: ['job-cards'] as const,
    vehicleLists: () => [...jobCardKeys.all, 'vehicle-list'] as const,
    vehicleList: (filters: VehicleJobCardListFilters) => [...jobCardKeys.vehicleLists(), filters] as const,
};

export function useVehicleJobCards(filters: VehicleJobCardListFilters, enabled = true) {
    return useQuery({
        queryKey: jobCardKeys.vehicleList(filters),
        queryFn: () => jobCardsApi.listVehicleJobCards(filters),
        enabled,
    });
}

export function useCreateVehicleJobCard() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: VehicleJobCardPayload) => jobCardsApi.createVehicleJobCard(payload),
        onSuccess: (jobCard) => {
            void queryClient.invalidateQueries({ queryKey: jobCardKeys.vehicleLists() });
            void queryClient.invalidateQueries({ queryKey: ['vehicles'] });
            void queryClient.invalidateQueries({ queryKey: ['vehicles', 'detail', jobCard.vehicle_id, jobCard.tenant_id] });
        },
    });
}

export { jobCardKeys };
