import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type {
    VehicleServiceInvoiceCreated,
    VehicleServiceInvoicePayload,
    VehicleServiceInvoicePreview,
    VehicleServiceJobLine,
} from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

export const listBillableLines = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJobLine>>(`${jobs}/${jobId}/billable-lines`, { signal })
        .then((response) => response.data.data);

export const previewVehicleServiceInvoice = (
    jobId: number,
    payload: VehicleServiceInvoicePayload,
) =>
    apiClient.post<ApiResource<VehicleServiceInvoicePreview>>(
        `${jobs}/${jobId}/invoices/preview`,
        payload,
    ).then((response) => response.data.data);

export const createVehicleServiceInvoice = (
    jobId: number,
    payload: VehicleServiceInvoicePayload,
) =>
    apiClient.post<ApiResource<VehicleServiceInvoiceCreated>>(
        `${jobs}/${jobId}/invoices`,
        payload,
    ).then((response) => response.data.data);
