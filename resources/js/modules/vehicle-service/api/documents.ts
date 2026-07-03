import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type { VehicleServiceDocument, VehicleServiceDocumentOptions } from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

export const getVehicleServiceDocumentOptions = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<VehicleServiceDocumentOptions>>(`${jobs}/${jobId}/documents/options`, { signal })
        .then((response) => response.data.data);

export const listVehicleServiceDocuments = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceDocument>>(`${jobs}/${jobId}/documents`, { signal })
        .then((response) => response.data.data);

export const createVehicleServiceDocument = (jobId: number, payload: FormData) =>
    apiClient.post<ApiResource<VehicleServiceDocument>>(`${jobs}/${jobId}/documents`, payload)
        .then((response) => response.data.data);

export async function downloadVehicleServiceDocument(jobId: number, document: VehicleServiceDocument): Promise<void> {
    const response = await apiClient.get<Blob>(`${jobs}/${jobId}/documents/${document.id}/download`, {
        responseType: 'blob',
    });
    const url = URL.createObjectURL(response.data);
    const link = window.document.createElement('a');
    link.href = url;
    link.download = document.original_filename;
    window.document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1_000);
}

export const deleteVehicleServiceDocument = (jobId: number, documentId: number, expectedVersion: number) =>
    apiClient.delete(`${jobs}/${jobId}/documents/${documentId}`, { data: { expected_version: expectedVersion } });
