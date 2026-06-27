import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    CreatePartyVehiclePayload,
    PartyVehicleRelationship,
    SupersedePartyVehiclePayload,
    VehicleOwnerType,
} from '@/shared/types/partyVehicle';

export interface VehicleOwnershipListParams extends ListParams {
    owner_type?: VehicleOwnerType;
    owner_id?: number;
    vehicle_id?: number;
    is_current?: boolean;
    status?: 'active' | 'ended';
    sort?: 'started_at' | 'ended_at' | 'created_at';
    direction?: 'asc' | 'desc';
}

const ownershipPath = (id?: number) => id === undefined
    ? endpoints.vehicleOwnerships
    : `${endpoints.vehicleOwnerships}/${id}`;

export const listVehicleOwnerships = (params: VehicleOwnershipListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<PartyVehicleRelationship>>(ownershipPath(), { params, signal })
        .then((response) => response.data);

export const getVehicleOwnership = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<PartyVehicleRelationship>>(ownershipPath(id), { signal })
        .then((response) => response.data.data);

export const createVehicleOwnership = (payload: CreatePartyVehiclePayload) =>
    apiClient.post<ApiResource<PartyVehicleRelationship>>(ownershipPath(), payload)
        .then((response) => response.data.data);

export const supersedeVehicleOwnership = (id: number, payload: SupersedePartyVehiclePayload) =>
    apiClient.post<ApiResource<PartyVehicleRelationship>>(`${ownershipPath(id)}/supersede`, payload)
        .then((response) => response.data.data);

export const setVehicleOwnershipCurrent = (id: number, expectedVersion: number) =>
    apiClient.post<ApiResource<PartyVehicleRelationship>>(`${ownershipPath(id)}/set-current`, {
        expected_version: expectedVersion,
    }).then((response) => response.data.data);

export const clearVehicleOwnershipCurrent = (id: number, expectedVersion: number) =>
    apiClient.post<ApiResource<PartyVehicleRelationship>>(`${ownershipPath(id)}/clear-current`, {
        expected_version: expectedVersion,
    }).then((response) => response.data.data);

export const endVehicleOwnership = (id: number, expectedVersion: number, endedAt: string) =>
    apiClient.post<ApiResource<PartyVehicleRelationship>>(`${ownershipPath(id)}/end`, {
        expected_version: expectedVersion,
        ended_at: endedAt,
    }).then((response) => response.data.data);
