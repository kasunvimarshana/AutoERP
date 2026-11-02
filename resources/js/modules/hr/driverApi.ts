import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import { requestLookup } from '@/shared/api/lookupRequest';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type {
    DriverDetail,
    DriverListParams,
    DriverPayload,
    DriverStatusPayload,
    DriverSummary,
} from './driverTypes';

export const listDrivers = (params: DriverListParams, signal?: AbortSignal) =>
    apiClient
        .get<ApiCollection<DriverSummary>>(endpoints.hrDrivers, { params, signal })
        .then((r) => r.data);

export const getDriver = (id: number, signal?: AbortSignal) =>
    apiClient
        .get<ApiResource<DriverDetail>>(`${endpoints.hrDrivers}/${id}`, { signal })
        .then((r) => r.data.data);

export const createDriver = (payload: DriverPayload) =>
    apiClient
        .post<ApiResource<DriverDetail>>(endpoints.hrDrivers, payload)
        .then((r) => r.data.data);

export const updateDriver = (id: number, payload: Partial<DriverPayload>) =>
    apiClient
        .put<ApiResource<DriverDetail>>(`${endpoints.hrDrivers}/${id}`, payload)
        .then((r) => r.data.data);

export const deleteDriver = (id: number) =>
    apiClient.delete(`${endpoints.hrDrivers}/${id}`);

export const changeDriverStatus = (id: number, payload: DriverStatusPayload) =>
    apiClient
        .patch<ApiResource<DriverDetail>>(`${endpoints.hrDrivers}/${id}/status`, payload)
        .then((r) => r.data.data);

export function searchDrivers(
    params: LookupLoadParams,
): Promise<LookupResult<DriverSummary>> {
    return requestLookup<DriverSummary>(`${endpoints.hrDrivers}/lookup`, params);
}
