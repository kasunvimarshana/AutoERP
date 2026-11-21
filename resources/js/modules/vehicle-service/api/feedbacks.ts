import { apiClient } from '@/shared/api/apiClient';
import type { ApiResource } from '@/shared/types/api';
import type {
    VehicleServiceFeedbackFilterParams,
    VehicleServiceFeedbackListResult,
    VehicleServiceFeedbackSummary,
    VehicleServiceJobFeedback,
    VehicleServiceJobFeedbackPayload,
} from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

export const listVehicleServiceFeedbacks = (
    jobId: number,
    params?: VehicleServiceFeedbackFilterParams,
    signal?: AbortSignal,
): Promise<VehicleServiceFeedbackListResult> => {
    const searchParams = new URLSearchParams();
    if (params?.feedback_type) searchParams.set('feedback_type', params.feedback_type);
    if (params?.rating) searchParams.set('rating', String(params.rating));
    if (params?.customer_visible_only) searchParams.set('customer_visible_only', '1');
    if (params?.search) searchParams.set('search', params.search);

    const query = searchParams.toString();
    const url = query ? `${jobs}/${jobId}/feedbacks?${query}` : `${jobs}/${jobId}/feedbacks`;

    return apiClient.get<VehicleServiceFeedbackListResult>(url, { signal })
        .then((response) => response.data);
};

export const getVehicleServiceFeedbackSummary = (
    jobId: number,
    signal?: AbortSignal,
): Promise<VehicleServiceFeedbackSummary> =>
    apiClient.get<ApiResource<VehicleServiceFeedbackSummary>>(`${jobs}/${jobId}/feedbacks/summary`, { signal })
        .then((response) => response.data.data);

export const createVehicleServiceFeedback = (
    jobId: number,
    payload: VehicleServiceJobFeedbackPayload,
): Promise<VehicleServiceJobFeedback> =>
    apiClient.post<ApiResource<VehicleServiceJobFeedback>>(`${jobs}/${jobId}/feedbacks`, payload)
        .then((response) => response.data.data);

export const updateVehicleServiceFeedback = (
    jobId: number,
    feedbackId: number,
    payload: VehicleServiceJobFeedbackPayload,
): Promise<VehicleServiceJobFeedback> =>
    apiClient.put<ApiResource<VehicleServiceJobFeedback>>(`${jobs}/${jobId}/feedbacks/${feedbackId}`, payload)
        .then((response) => response.data.data);

export const deleteVehicleServiceFeedback = (
    jobId: number,
    feedbackId: number,
    expectedVersion?: number,
): Promise<void> =>
    apiClient.delete(`${jobs}/${jobId}/feedbacks/${feedbackId}`, {
        data: expectedVersion !== undefined ? { expected_version: expectedVersion } : undefined,
    }).then(() => undefined);
