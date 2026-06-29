import { apiClient } from '@/shared/api/apiClient';
import type { ApiResource } from '@/shared/types/api';
import type { Payment } from '@/modules/payment/paymentApi';
import type {
    VehicleServicePaymentOptions,
    VehicleServicePaymentPayload,
} from '../vehicleServicePaymentTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

export const getVehicleServicePaymentOptions = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<VehicleServicePaymentOptions>>(`${jobs}/${jobId}/payments/options`, { signal })
        .then((response) => response.data.data);

export const previewVehicleServicePayment = (jobId: number, payload: VehicleServicePaymentPayload, signal?: AbortSignal) =>
    apiClient.post<ApiResource<Record<string, unknown>>>(`${jobs}/${jobId}/payments/prepare`, payload, { signal })
        .then((response) => response.data.data);

export const createVehicleServicePayment = (jobId: number, payload: VehicleServicePaymentPayload) =>
    apiClient.post<ApiResource<Payment>>(`${jobs}/${jobId}/payments`, payload)
        .then((response) => response.data.data);
