import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type { VehicleServiceJobLine, VehicleServiceLinePayload } from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint as jobs } from './endpoint';

interface VehicleServiceLineMutationResponse<T> extends ApiResource<T> {
    meta: {
        row_version: number;
        workforce_lines: VehicleServiceJobLine[];
    };
}

export interface VehicleServiceLineMutationResult<T> {
    line: T;
    rowVersion: number;
    workforceLines: VehicleServiceJobLine[];
}

export const listVehicleServiceLines = (jobId: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJobLine>>(`${jobs}/${jobId}/lines`, { signal })
        .then((response) => flattenVehicleServiceLines(response.data.data));

export function flattenVehicleServiceLines(lines: VehicleServiceJobLine[]): VehicleServiceJobLine[] {
    return lines.flatMap((line) => [line, ...flattenVehicleServiceLines(line.children ?? [])]);
}

export const createVehicleServiceLine = (jobId: number, payload: VehicleServiceLinePayload) =>
    apiClient.post<VehicleServiceLineMutationResponse<VehicleServiceJobLine>>(`${jobs}/${jobId}/lines`, payload)
        .then(toLineMutationResult);

export const updateVehicleServiceLine = (
    jobId: number,
    lineId: number,
    payload: VehicleServiceLinePayload,
) =>
    apiClient.put<VehicleServiceLineMutationResponse<VehicleServiceJobLine>>(`${jobs}/${jobId}/lines/${lineId}`, payload)
        .then(toLineMutationResult);

export const deleteVehicleServiceLine = (jobId: number, lineId: number, expectedVersion: number) =>
    apiClient.delete<VehicleServiceLineMutationResponse<null>>(`${jobs}/${jobId}/lines/${lineId}`, {
        data: { expected_version: expectedVersion },
    }).then(toLineMutationResult);

function toLineMutationResult<T>(response: { data: VehicleServiceLineMutationResponse<T> }): VehicleServiceLineMutationResult<T> {
    return {
        line: response.data.data,
        rowVersion: response.data.meta.row_version,
        workforceLines: response.data.meta.workforce_lines,
    };
}
