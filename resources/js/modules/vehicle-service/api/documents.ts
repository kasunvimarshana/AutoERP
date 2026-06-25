import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type { VehicleServiceDocument } from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

export const listVehicleServiceDocuments = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceDocument>>(`${jobs}/${jobId}/documents`, { signal })
        .then((response) => response.data.data);

export const createVehicleServiceDocument = (jobId: number, payload: FormData) =>
    apiClient.post<ApiResource<VehicleServiceDocument>>(`${jobs}/${jobId}/documents`, payload)
        .then((response) => response.data.data);

export const deleteVehicleServiceDocument = (jobId: number, documentId: number) =>
    apiClient.delete(`${jobs}/${jobId}/documents/${documentId}`);
