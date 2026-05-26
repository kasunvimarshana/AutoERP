import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type { VehicleJobCardListFilters, VehicleJobCardPayload, VehicleJobCardRecord } from './types';

export const jobCardsApi = {
    listVehicleJobCards(filters: VehicleJobCardListFilters): Promise<PaginatedResult<VehicleJobCardRecord>> {
        const { vehicle_id: vehicleId, ...query } = filters;

        return apiClient
            .get<ApiPaginatedEnvelope<VehicleJobCardRecord> | VehicleJobCardRecord[]>(`/vehicles/${vehicleId}/job-cards`, { query: toQuery(query) })
            .then((payload) => unwrapPaginated<VehicleJobCardRecord>(payload));
    },
    createVehicleJobCard(payload: VehicleJobCardPayload) {
        return apiClient
            .post<ApiResourceEnvelope<VehicleJobCardRecord> | VehicleJobCardRecord>('/vehicles/job-cards', payload)
            .then((result) => unwrapResource<VehicleJobCardRecord>(result));
    },
};
