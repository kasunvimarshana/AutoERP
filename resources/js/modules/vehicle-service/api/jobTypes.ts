/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    VehicleServiceJobTypePayload,
    VehicleServiceJobTypeResource,
} from '../vehicleServiceTypes';
import { vehicleServiceJobTypesEndpoint as jobTypes } from './endpoint';

export interface ListVehicleServiceJobTypesParams extends ListParams {
    is_active?: boolean;
}

export const listVehicleServiceJobTypes = (params?: ListVehicleServiceJobTypesParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleServiceJobTypeResource>>(jobTypes, { params, signal })
        .then((response) => response.data);

export const lookupVehicleServiceJobTypes = (signal?: AbortSignal) =>
    apiClient.get<{ data: VehicleServiceJobTypeResource[] }>(`${jobTypes}/lookup`, { signal })
        .then((response) => response.data.data);

export const getVehicleServiceJobType = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<VehicleServiceJobTypeResource>>(`${jobTypes}/${id}`, { signal })
        .then((response) => response.data.data);

export const createVehicleServiceJobType = (payload: VehicleServiceJobTypePayload) =>
    apiClient.post<ApiResource<VehicleServiceJobTypeResource>>(jobTypes, payload)
        .then((response) => response.data.data);

export const updateVehicleServiceJobType = (id: number, payload: VehicleServiceJobTypePayload) =>
    apiClient.put<ApiResource<VehicleServiceJobTypeResource>>(`${jobTypes}/${id}`, payload)
        .then((response) => response.data.data);

export const deleteVehicleServiceJobType = (id: number, expectedVersion: number) =>
    apiClient.delete(`${jobTypes}/${id}`, { data: { expected_version: expectedVersion } });
