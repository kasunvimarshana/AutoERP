import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { PartyVehiclePayload, PartyVehicleRelationship } from '@/shared/types/partyVehicle';

export type VehicleOwnerType = 'customer' | 'supplier' | 'company';

function listParams(ownerType: VehicleOwnerType, params: ListParams): ListParams {
    const ownerId = ownerType === 'customer' ? params.customer_id : ownerType === 'supplier' ? params.supplier_id : undefined;
    const { customer_id: _customerId, supplier_id: _supplierId, ...rest } = params;

    return {
        ...rest,
        owner_type: ownerType,
        owner_id: ownerId,
    };
}

function createPayload(ownerType: VehicleOwnerType, payload: PartyVehiclePayload) {
    const ownerId = ownerType === 'customer' ? payload.customer_id : ownerType === 'supplier' ? payload.supplier_id : undefined;

    return {
        vehicle_id: payload.vehicle_id,
        owner_type: ownerType,
        owner_id: ownerId,
        ownership_type: payload.relationship_type ?? (ownerType === 'customer' ? 'customer_owned' : 'third_party'),
        started_at: payload.started_at,
        ended_at: payload.ended_at ?? null,
        is_current: payload.is_current ?? false,
        notes: payload.notes ?? null,
    };
}

export function listVehicleOwnerships(ownerType: VehicleOwnerType, params: ListParams, signal?: AbortSignal) {
    return apiClient
        .get<ApiCollection<PartyVehicleRelationship>>(endpoints.vehicleOwnerships, { params: listParams(ownerType, params), signal })
        .then((response) => response.data);
}

export function getVehicleOwnership(id: number, signal?: AbortSignal) {
    return apiClient
        .get<ApiResource<PartyVehicleRelationship>>(`${endpoints.vehicleOwnerships}/${id}`, { signal })
        .then((response) => response.data.data);
}

export function createVehicleOwnership(ownerType: VehicleOwnerType, payload: PartyVehiclePayload) {
    return apiClient
        .post<ApiResource<PartyVehicleRelationship>>(endpoints.vehicleOwnerships, createPayload(ownerType, payload))
        .then((response) => response.data.data);
}

export function updateVehicleOwnership(id: number, payload: Partial<PartyVehiclePayload>) {
    if (payload.expected_version === undefined) {
        throw new Error('Vehicle ownership update requires the current row version.');
    }

    return apiClient
        .patch<ApiResource<PartyVehicleRelationship>>(`${endpoints.vehicleOwnerships}/${id}`, {
            expected_version: payload.expected_version,
            notes: payload.notes ?? null,
        })
        .then((response) => response.data.data);
}

function mutate(id: number, action: 'set-current' | 'clear-current', expectedVersion: number) {
    return apiClient
        .post<ApiResource<PartyVehicleRelationship>>(`${endpoints.vehicleOwnerships}/${id}/${action}`, {
            expected_version: expectedVersion,
        })
        .then((response) => response.data.data);
}

export const setVehicleOwnershipCurrent = (id: number, expectedVersion: number) => mutate(id, 'set-current', expectedVersion);
export const clearVehicleOwnershipCurrent = (id: number, expectedVersion: number) => mutate(id, 'clear-current', expectedVersion);

export function endVehicleOwnership(id: number, expectedVersion: number) {
    return apiClient
        .delete<ApiResource<PartyVehicleRelationship>>(`${endpoints.vehicleOwnerships}/${id}`, {
            data: { expected_version: expectedVersion },
        })
        .then((response) => response.data.data);
}
