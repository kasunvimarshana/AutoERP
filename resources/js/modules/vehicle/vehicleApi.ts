import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import { requestLookup } from '@/shared/api/lookupRequest';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type {
    Vehicle,
    VehicleAttribute,
    VehicleAttributePayload,
    VehicleCategory,
    VehicleCategoryPayload,
    VehicleDocument,
    VehicleDocumentPayload,
    VehicleMake,
    VehicleMakePayload,
    VehicleModel,
    VehicleModelPayload,
    VehicleOwnership,
    VehicleOwnershipPayload,
    VehiclePayload,
    VehicleStatusHistory,
    VehicleSummary,
    VehicleType,
    VehicleTypePayload,
    VehicleWithRelationsPayload,
} from './vehicleTypes';

export const listVehicles = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleSummary>>(endpoints.vehicles, { params, signal }).then((response) => response.data);

export const getVehicle = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<Vehicle>>(`${endpoints.vehicles}/${id}`, { signal }).then((response) => response.data.data);

export const createVehicle = (payload: VehiclePayload) =>
    apiClient.post<ApiResource<Vehicle>>(endpoints.vehicles, payload).then((response) => response.data.data);

export const createVehicleWithRelations = (payload: VehicleWithRelationsPayload) =>
    apiClient.post<ApiResource<Vehicle>>(`${endpoints.vehicles}/with-relations`, payload).then((response) => response.data.data);

export const updateVehicle = (id: number, payload: Partial<VehiclePayload>) =>
    apiClient.put<ApiResource<Vehicle>>(`${endpoints.vehicles}/${id}`, payload).then((response) => response.data.data);

export const deleteVehicle = (id: number) => apiClient.delete(`${endpoints.vehicles}/${id}`);

export const setVehicleActive = (id: number, active: boolean) =>
    apiClient.patch<ApiResource<Vehicle>>(`${endpoints.vehicles}/${id}/${active ? 'activate' : 'deactivate'}`).then((response) => response.data.data);

export const changeVehicleStatus = (id: number, status: string, reason?: string) =>
    apiClient.patch<ApiResource<Vehicle>>(`${endpoints.vehicles}/${id}/status`, { status, reason }).then((response) => response.data.data);

export function searchVehicles(params: LookupLoadParams, kind = 'active'): Promise<LookupResult<VehicleSummary>> {
    return requestLookup<VehicleSummary>(`${endpoints.vehicles}/lookup/${kind}`, params);
}

export function searchVehicleMakes(params: LookupLoadParams): Promise<LookupResult<VehicleMake>> {
    return requestLookup<VehicleMake>(`${endpoints.vehicleMakes}/lookup`, params);
}

export function searchVehicleModels(
    params: LookupLoadParams,
    vehicleMakeId?: number | null,
): Promise<LookupResult<VehicleModel>> {
    return requestLookup<VehicleModel>(`${endpoints.vehicleModels}/lookup`, params, {
        vehicle_make_id: vehicleMakeId ?? undefined,
    });
}

export function searchVehicleTypes(params: LookupLoadParams): Promise<LookupResult<VehicleType>> {
    return requestLookup<VehicleType>(`${endpoints.vehicleTypes}/lookup`, params);
}

export function searchVehicleCategories(params: LookupLoadParams): Promise<LookupResult<VehicleCategory>> {
    return requestLookup<VehicleCategory>(`${endpoints.vehicleCategories}/lookup`, params);
}

export const listVehicleMakes = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleMake>>(endpoints.vehicleMakes, { params, signal }).then((response) => response.data);
export const createVehicleMake = (payload: VehicleMakePayload) =>
    apiClient.post<ApiResource<VehicleMake>>(endpoints.vehicleMakes, payload).then((response) => response.data.data);
export const updateVehicleMake = (id: number, payload: VehicleMakePayload) =>
    apiClient.put<ApiResource<VehicleMake>>(`${endpoints.vehicleMakes}/${id}`, payload).then((response) => response.data.data);

export const listVehicleTypes = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleType>>(endpoints.vehicleTypes, { params, signal }).then((response) => response.data);
export const createVehicleType = (payload: VehicleTypePayload) =>
    apiClient.post<ApiResource<VehicleType>>(endpoints.vehicleTypes, payload).then((response) => response.data.data);
export const updateVehicleType = (id: number, payload: VehicleTypePayload) =>
    apiClient.put<ApiResource<VehicleType>>(`${endpoints.vehicleTypes}/${id}`, payload).then((response) => response.data.data);

export const listVehicleCategories = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleCategory>>(endpoints.vehicleCategories, { params, signal }).then((response) => response.data);
export const createVehicleCategory = (payload: VehicleCategoryPayload) =>
    apiClient.post<ApiResource<VehicleCategory>>(endpoints.vehicleCategories, payload).then((response) => response.data.data);
export const updateVehicleCategory = (id: number, payload: VehicleCategoryPayload) =>
    apiClient.put<ApiResource<VehicleCategory>>(`${endpoints.vehicleCategories}/${id}`, payload).then((response) => response.data.data);

export const listVehicleModels = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleModel>>(endpoints.vehicleModels, { params, signal }).then((response) => response.data);
export const createVehicleModel = (payload: VehicleModelPayload) =>
    apiClient.post<ApiResource<VehicleModel>>(endpoints.vehicleModels, payload).then((response) => response.data.data);
export const updateVehicleModel = (id: number, payload: VehicleModelPayload) =>
    apiClient.put<ApiResource<VehicleModel>>(`${endpoints.vehicleModels}/${id}`, payload).then((response) => response.data.data);

const relationPath = (vehicleId: number, relation: string) => `${endpoints.vehicles}/${vehicleId}/${relation}`;

export const listVehicleDocuments = (vehicleId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleDocument>>(relationPath(vehicleId, 'documents'), { params, signal }).then((response) => response.data);
export const createVehicleDocument = (vehicleId: number, payload: VehicleDocumentPayload) =>
    apiClient.post<ApiResource<VehicleDocument>>(relationPath(vehicleId, 'documents'), payload).then((response) => response.data.data);
export const updateVehicleDocument = (vehicleId: number, id: number, payload: VehicleDocumentPayload) =>
    apiClient.put<ApiResource<VehicleDocument>>(`${relationPath(vehicleId, 'documents')}/${id}`, payload).then((response) => response.data.data);
export const deleteVehicleDocument = (vehicleId: number, id: number) => apiClient.delete(`${relationPath(vehicleId, 'documents')}/${id}`);

export const listVehicleOwnerships = (vehicleId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleOwnership>>(relationPath(vehicleId, 'ownerships'), { params, signal }).then((response) => response.data);
export const createVehicleOwnership = (vehicleId: number, payload: VehicleOwnershipPayload) =>
    apiClient.post<ApiResource<VehicleOwnership>>(relationPath(vehicleId, 'ownerships'), payload).then((response) => response.data.data);
export const updateVehicleOwnership = (vehicleId: number, id: number, payload: VehicleOwnershipPayload) =>
    apiClient.put<ApiResource<VehicleOwnership>>(`${relationPath(vehicleId, 'ownerships')}/${id}`, payload).then((response) => response.data.data);
export const deleteVehicleOwnership = (vehicleId: number, id: number) => apiClient.delete(`${relationPath(vehicleId, 'ownerships')}/${id}`);

export const listVehicleAttributes = (vehicleId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleAttribute>>(relationPath(vehicleId, 'attributes'), { params, signal }).then((response) => response.data);
export const createVehicleAttribute = (vehicleId: number, payload: VehicleAttributePayload) =>
    apiClient.post<ApiResource<VehicleAttribute>>(relationPath(vehicleId, 'attributes'), payload).then((response) => response.data.data);
export const updateVehicleAttribute = (vehicleId: number, id: number, payload: VehicleAttributePayload) =>
    apiClient.put<ApiResource<VehicleAttribute>>(`${relationPath(vehicleId, 'attributes')}/${id}`, payload).then((response) => response.data.data);
export const deleteVehicleAttribute = (vehicleId: number, id: number) => apiClient.delete(`${relationPath(vehicleId, 'attributes')}/${id}`);

export const listVehicleStatusHistory = (vehicleId: number, params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleStatusHistory>>(relationPath(vehicleId, 'status-history'), { params, signal }).then((response) => response.data);
