import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type { VehicleDashboard, VehicleListFilters, VehiclePayload, VehicleRecord, VehicleStatusPayload } from './types';

export const vehiclesApi = {
    getDashboard(tenantId: number): Promise<VehicleDashboard> {
        return apiClient.get<VehicleDashboard>('/vehicles-dashboard', { query: { tenant_id: tenantId } });
    },
    listVehicles(filters: VehicleListFilters): Promise<PaginatedResult<VehicleRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<VehicleRecord>>('/vehicles', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getVehicle(vehicleId: number, tenantId: number) {
        return apiClient
            .get<ApiResourceEnvelope<VehicleRecord> | VehicleRecord>(`/vehicles/${vehicleId}`, { query: { tenant_id: tenantId } })
            .then((payload) => unwrapResource(payload));
    },
    createVehicle(payload: VehiclePayload) {
        return apiClient.post<ApiResourceEnvelope<VehicleRecord> | VehicleRecord>('/vehicles', payload).then((result) => unwrapResource(result));
    },
    updateVehicle(vehicleId: number, payload: VehiclePayload) {
        return apiClient.put<ApiResourceEnvelope<VehicleRecord> | VehicleRecord>(`/vehicles/${vehicleId}`, payload).then((result) => unwrapResource(result));
    },
    deleteVehicle(vehicleId: number, tenantId: number) {
        return apiClient.delete<null>(`/vehicles/${vehicleId}`, { query: { tenant_id: tenantId } });
    },
    updateVehicleStatus(vehicleId: number, payload: VehicleStatusPayload) {
        return apiClient
            .patch<ApiResourceEnvelope<VehicleRecord> | VehicleRecord>(`/vehicles/${vehicleId}/status`, payload)
            .then((result) => unwrapResource(result));
    },
};
