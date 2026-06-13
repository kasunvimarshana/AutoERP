import { apiClient } from '@/shared/api/apiClient';
import type { ApiResource } from '@/shared/types/api';
import type {
    PreparedVehicleServicePayment,
    VehicleServicePaymentCreated,
    VehicleServicePaymentPayload,
} from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

export const prepareVehicleServicePayment = (
    jobId: number,
    payload: VehicleServicePaymentPayload,
) =>
    apiClient.post<ApiResource<PreparedVehicleServicePayment>>(
        `${jobs}/${jobId}/payments/prepare`,
        payload,
    ).then((response) => response.data.data);

export const createVehicleServicePayment = (
    jobId: number,
    payload: VehicleServicePaymentPayload,
) =>
    apiClient.post<ApiResource<VehicleServicePaymentCreated>>(
        `${jobs}/${jobId}/payments`,
        payload,
    ).then((response) => response.data.data);
