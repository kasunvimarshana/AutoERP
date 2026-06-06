import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    Vehicle,
    VehicleAttribute,
    VehicleAttributePayload,
    VehicleCategory,
    VehicleDocument,
    VehicleDocumentPayload,
    VehicleMake,
    VehicleModel,
    VehicleOwnership,
    VehicleOwnershipPayload,
    VehiclePayload,
    VehicleStatusHistory,
    VehicleSummary,
    VehicleType,
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

export async function searchVehicles(search: string, signal?: AbortSignal, kind = 'active'): Promise<VehicleSummary[]> {
    const response = await apiClient.get<ApiCollection<VehicleSummary>>(`${endpoints.vehicles}/lookup/${kind}`, {
        params: { search, per_page: 20 },
        signal,
    });
    return response.data.data;
}

export async function searchVehicleMakes(search: string, signal?: AbortSignal): Promise<VehicleMake[]> {
    const response = await apiClient.get<ApiCollection<VehicleMake>>(`${endpoints.vehicleMakes}/lookup`, { params: { search, per_page: 20 }, signal });
    return response.data.data;
}

export async function searchVehicleModels(search: string, vehicleMakeId?: number | null, signal?: AbortSignal): Promise<VehicleModel[]> {
    const response = await apiClient.get<ApiCollection<VehicleModel>>(`${endpoints.vehicleModels}/lookup`, {
        params: { search, vehicle_make_id: vehicleMakeId ?? undefined, per_page: 20 },
        signal,
    });
    return response.data.data;
}

export async function searchVehicleTypes(search: string, signal?: AbortSignal): Promise<VehicleType[]> {
    const response = await apiClient.get<ApiCollection<VehicleType>>(`${endpoints.vehicleTypes}/lookup`, { params: { search, per_page: 20 }, signal });
    return response.data.data;
}

export async function searchVehicleCategories(search: string, signal?: AbortSignal): Promise<VehicleCategory[]> {
    const response = await apiClient.get<ApiCollection<VehicleCategory>>(`${endpoints.vehicleCategories}/lookup`, { params: { search, per_page: 20 }, signal });
    return response.data.data;
}

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
