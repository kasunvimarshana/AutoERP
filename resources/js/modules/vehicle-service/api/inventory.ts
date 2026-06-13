import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type {
    VehicleServiceInventoryIssuePayload,
    VehicleServiceInventoryMovement,
    VehicleServiceJobLine,
} from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

export const listInventoryIssueLines = (
    jobId: number,
    params: { warehouse_id?: number; warehouse_location_id?: number } = {},
    signal?: AbortSignal,
) =>
    apiClient.get<ApiCollection<VehicleServiceJobLine>>(
        `${jobs}/${jobId}/inventory-issue-lines`,
        { params, signal },
    ).then((response) => response.data.data);

export const issueVehicleServiceInventory = (
    jobId: number,
    payload: VehicleServiceInventoryIssuePayload,
) =>
    apiClient.post<ApiResource<VehicleServiceInventoryMovement[]>>(
        `${jobs}/${jobId}/issue-inventory`,
        payload,
    ).then((response) => response.data.data);
