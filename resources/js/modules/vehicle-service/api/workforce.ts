import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type {
    VehicleServiceEmployeeAssignment,
    VehicleServiceEmployeeAssignmentPayload,
    VehicleServiceJobLine,
} from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

export const listEmployeeAssignableLines = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJobLine>>(`${jobs}/${jobId}/employee-assignable-lines`, { signal })
        .then((response) => response.data.data);

export const createVehicleServiceEmployee = (
    jobId: number,
    lineId: number,
    payload: VehicleServiceEmployeeAssignmentPayload,
) =>
    apiClient.post<ApiResource<VehicleServiceEmployeeAssignment>>(
        `${jobs}/${jobId}/lines/${lineId}/employees`,
        payload,
    ).then((response) => response.data.data);

export const updateVehicleServiceEmployee = (
    jobId: number,
    lineId: number,
    assignmentId: number,
    payload: VehicleServiceEmployeeAssignmentPayload,
) =>
    apiClient.put<ApiResource<VehicleServiceEmployeeAssignment>>(
        `${jobs}/${jobId}/lines/${lineId}/employees/${assignmentId}`,
        payload,
    ).then((response) => response.data.data);

export const deleteVehicleServiceEmployee = (
    jobId: number,
    lineId: number,
    assignmentId: number,
    expectedVersion: number,
) => apiClient.delete(`${jobs}/${jobId}/lines/${lineId}/employees/${assignmentId}`, {
    data: { expected_version: expectedVersion },
});
