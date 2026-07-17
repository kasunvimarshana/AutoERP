import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { VehicleServiceCommissionDefault } from '../commissionTypes';
import type {
    VehicleServiceInspection,
    VehicleServiceInspectionPayload,
    VehicleServiceJob,
    VehicleServiceJobPayload,
    VehicleServiceStatusHistory,
} from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

export const listVehicleServiceJobs = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJob>>(jobs, { params, signal })
        .then((response) => response.data);

export const getVehicleServiceJob = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<VehicleServiceJob>>(`${jobs}/${id}`, { signal })
        .then((response) => response.data.data);

export const getVehicleServiceJobCreateDefaults = (signal?: AbortSignal) =>
    apiClient.get<ApiResource<VehicleServiceCommissionDefault>>(`${jobs}/create-defaults`, { signal })
        .then((response) => response.data.data);

export const createVehicleServiceJob = (payload: VehicleServiceJobPayload) =>
    apiClient.post<ApiResource<VehicleServiceJob>>(jobs, payload)
        .then((response) => response.data.data);

export const updateVehicleServiceJob = (id: number, payload: VehicleServiceJobPayload) =>
    apiClient.put<ApiResource<VehicleServiceJob>>(`${jobs}/${id}`, payload)
        .then((response) => response.data.data);

export const deleteVehicleServiceJob = (id: number, expectedVersion: number) =>
    apiClient.delete(`${jobs}/${id}`, { data: { expected_version: expectedVersion } });

export const inspectVehicleServiceJob = (id: number, payload: VehicleServiceInspectionPayload) =>
    apiClient.patch<ApiResource<VehicleServiceInspection>>(`${jobs}/${id}/inspect`, payload)
        .then((response) => response.data.data);

export const startVehicleServiceJob = (id: number, expectedVersion: number) =>
    apiClient.patch<ApiResource<VehicleServiceJob>>(`${jobs}/${id}/start`, { expected_version: expectedVersion })
        .then((response) => response.data.data);

export const completeVehicleServiceJob = (id: number, expectedVersion: number) =>
    apiClient.patch<ApiResource<VehicleServiceJob>>(`${jobs}/${id}/complete`, { expected_version: expectedVersion })
        .then((response) => response.data.data);

export const cancelVehicleServiceJob = (id: number, expectedVersion: number, reason?: string) =>
    apiClient.patch<ApiResource<VehicleServiceJob>>(`${jobs}/${id}/cancel`, { expected_version: expectedVersion, reason })
        .then((response) => response.data.data);

export const getVehicleServiceInspection = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<VehicleServiceInspection | null>>(`${jobs}/${jobId}/inspection`, { signal })
        .then((response) => response.data.data);

export const saveVehicleServiceInspection = (jobId: number, payload: VehicleServiceInspectionPayload) =>
    apiClient.put<ApiResource<VehicleServiceInspection>>(`${jobs}/${jobId}/inspection`, payload)
        .then((response) => response.data.data);

export const listVehicleServiceStatusHistory = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceStatusHistory>>(`${jobs}/${jobId}/status-history`, { signal })
        .then((response) => response.data.data);
