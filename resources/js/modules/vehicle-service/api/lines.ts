import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type { VehicleServiceJobLine, VehicleServiceLinePayload } from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

export const listVehicleServiceLines = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJobLine>>(`${jobs}/${jobId}/lines`, { signal })
        .then((response) => response.data.data);

export const createVehicleServiceLine = (jobId: number, payload: VehicleServiceLinePayload) =>
    apiClient.post<ApiResource<VehicleServiceJobLine>>(`${jobs}/${jobId}/lines`, payload)
        .then((response) => response.data.data);

export const updateVehicleServiceLine = (
    jobId: number,
    lineId: number,
    payload: VehicleServiceLinePayload,
) =>
    apiClient.put<ApiResource<VehicleServiceJobLine>>(`${jobs}/${jobId}/lines/${lineId}`, payload)
        .then((response) => response.data.data);

export const deleteVehicleServiceLine = (jobId: number, lineId: number) =>
    apiClient.delete(`${jobs}/${jobId}/lines/${lineId}`);
