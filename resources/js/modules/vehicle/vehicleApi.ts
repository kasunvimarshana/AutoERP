import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import { createLocallyFilteredLookupLoader, createQueryCachedLookupLoader, prefetchLocallyFilteredLookupDataset } from '@/shared/api/lookupCache';
import { requestLookup } from '@/shared/api/lookupRequest';
import type { NamedResource } from '@/shared/types/common';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type { PaginationMeta } from '@/shared/types/pagination';
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

const VEHICLE_MAKES_LOOKUP_KEY = 'lookup:vehicle-makes';
const VEHICLE_MODELS_LOOKUP_KEY = 'lookup:vehicle-models';
const VEHICLE_TYPES_LOOKUP_KEY = 'lookup:vehicle-types';

const loadVehicleMakes = (params: LookupLoadParams) => requestLookup<VehicleMake>(`${endpoints.vehicleMakes}/lookup`, params);
const loadVehicleModels = (params: LookupLoadParams) => requestLookup<VehicleModel>(`${endpoints.vehicleModels}/lookup`, params);
const loadVehicleTypes = (params: LookupLoadParams) => requestLookup<VehicleType>(`${endpoints.vehicleTypes}/lookup`, params);

export const listVehicles = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<VehicleSummary>>(endpoints.vehicles, { params, signal }).then((response) => response.data);

export const getVehicle = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<Vehicle>>(`${endpoints.vehicles}/${id}`, { signal }).then((response) => response.data.data);

export const createVehicle = (payload: VehiclePayload) =>
    apiClient.post<ApiResource<Vehicle>>(endpoints.vehicles, payload).then((response) => response.data.data);

export const createVehicleWithRelations = (payload: VehicleWithRelationsPayload) =>
    apiClient.post<ApiResource<Vehicle>>(
        `${endpoints.vehicles}/with-relations`,
        hasDocumentFiles(payload.documents) ? vehicleWithRelationsFormData(payload) : payload
    ).then((response) => response.data.data);

export const updateVehicle = (id: number, payload: Partial<VehiclePayload>) =>
    apiClient.put<ApiResource<Vehicle>>(`${endpoints.vehicles}/${id}`, payload).then((response) => response.data.data);

export const deleteVehicle = (id: number) => apiClient.delete(`${endpoints.vehicles}/${id}`);

export const setVehicleActive = (id: number, active: boolean) =>
    apiClient.patch<ApiResource<Vehicle>>(`${endpoints.vehicles}/${id}/${active ? 'activate' : 'deactivate'}`).then((response) => response.data.data);

export const changeVehicleStatus = (id: number, status: string, reason?: string) =>
    apiClient.patch<ApiResource<Vehicle>>(`${endpoints.vehicles}/${id}/status`, { status, reason }).then((response) => response.data.data);

export function searchVehicles(params: LookupLoadParams, kind = 'active'): Promise<LookupResult<VehicleSummary>> {
    const loader = createQueryCachedLookupLoader<VehicleSummary>({
        key: `lookup:vehicles:${kind}`,
        load: (lookupParams) => requestLookup<VehicleSummary>(`${endpoints.vehicles}/lookup/${kind}`, lookupParams),
    });

    return loader(params);
}

export function searchVehicleMakes(params: LookupLoadParams): Promise<LookupResult<VehicleMake>> {
    const loader = createLocallyFilteredLookupLoader<VehicleMake>({
        key: VEHICLE_MAKES_LOOKUP_KEY,
        load: loadVehicleMakes,
    });

    return loader(params);
}

export function preloadVehicleMakes(signal: AbortSignal): Promise<VehicleMake[]> {
    return prefetchLocallyFilteredLookupDataset<VehicleMake>({
        key: VEHICLE_MAKES_LOOKUP_KEY,
        load: loadVehicleMakes,
        signal,
    });
}

export function searchVehicleModels(
    params: LookupLoadParams,
    vehicleMakeId?: number | null,
): Promise<LookupResult<VehicleModel>> {
    return prefetchLocallyFilteredLookupDataset<VehicleModel>({
        key: VEHICLE_MODELS_LOOKUP_KEY,
        load: loadVehicleModels,
        signal: params.signal,
    }).then((models) => paginateLocalVehicleModels(models, params, vehicleMakeId));
}

export function preloadVehicleModels(signal: AbortSignal): Promise<VehicleModel[]> {
    return prefetchLocallyFilteredLookupDataset<VehicleModel>({
        key: VEHICLE_MODELS_LOOKUP_KEY,
        load: loadVehicleModels,
        signal,
    });
}

export function searchVehicleTypes(params: LookupLoadParams): Promise<LookupResult<VehicleType>> {
    const loader = createLocallyFilteredLookupLoader<VehicleType>({
        key: VEHICLE_TYPES_LOOKUP_KEY,
        load: loadVehicleTypes,
    });

    return loader(params);
}

export function preloadVehicleTypes(signal: AbortSignal): Promise<VehicleType[]> {
    return prefetchLocallyFilteredLookupDataset<VehicleType>({
        key: VEHICLE_TYPES_LOOKUP_KEY,
        load: loadVehicleTypes,
        signal,
    });
}

export function searchVehicleCategories(params: LookupLoadParams): Promise<LookupResult<VehicleCategory>> {
    const loader = createLocallyFilteredLookupLoader<VehicleCategory>({
        key: 'lookup:vehicle-categories',
        load: (lookupParams) => requestLookup<VehicleCategory>(`${endpoints.vehicleCategories}/lookup`, lookupParams),
    });

    return loader(params);
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
    apiClient.post<ApiResource<VehicleDocument>>(
        relationPath(vehicleId, 'documents'),
        payload.file ? documentFormData(payload) : payload
    ).then((response) => response.data.data);
export const updateVehicleDocument = (vehicleId: number, id: number, payload: VehicleDocumentPayload) =>
    payload.file
        ? apiClient.post<ApiResource<VehicleDocument>>(`${relationPath(vehicleId, 'documents')}/${id}`, documentFormData(payload, 'PUT')).then((response) => response.data.data)
        : apiClient.put<ApiResource<VehicleDocument>>(`${relationPath(vehicleId, 'documents')}/${id}`, payload).then((response) => response.data.data);
export const deleteVehicleDocument = (vehicleId: number, id: number) => apiClient.delete(`${relationPath(vehicleId, 'documents')}/${id}`);
export const previewVehicleDocumentUrl = (vehicleId: number, id: number) => `${relationPath(vehicleId, 'documents')}/${id}/preview`;
export const downloadVehicleDocumentUrl = (vehicleId: number, id: number) => `${relationPath(vehicleId, 'documents')}/${id}/download`;
export const fetchVehicleDocumentFile = (vehicleId: number, id: number, mode: 'preview' | 'download') =>
    apiClient.get<Blob>(mode === 'preview' ? previewVehicleDocumentUrl(vehicleId, id) : downloadVehicleDocumentUrl(vehicleId, id), { responseType: 'blob' })
        .then((response) => response.data);

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


function hasDocumentFiles(documents: VehicleDocumentPayload[]): boolean {
    return documents.some((document) => Boolean(document.file));
}

function vehicleWithRelationsFormData(payload: VehicleWithRelationsPayload): FormData {
    const formData = new FormData();
    appendObject(formData, 'vehicle', payload.vehicle);
    payload.documents.forEach((document, index) => appendObject(formData, `documents[${index}]`, document));
    payload.ownerships.forEach((ownership, index) => appendObject(formData, `ownerships[${index}]`, ownership));
    payload.attributes.forEach((attribute, index) => appendObject(formData, `attributes[${index}]`, attribute));

    return formData;
}

function documentFormData(payload: VehicleDocumentPayload, method?: 'PUT'): FormData {
    const formData = new FormData();
    if (method) formData.append('_method', method);
    appendObject(formData, '', payload);

    return formData;
}

function appendObject(formData: FormData, prefix: string, value: object) {
    Object.entries(value as Record<string, unknown>).forEach(([key, entry]) => {
        const field = prefix ? `${prefix}[${key}]` : key;
        appendValue(formData, field, entry);
    });
}

function appendValue(formData: FormData, field: string, entry: unknown) {
    if (entry === undefined) return;
    if (entry instanceof File) {
        formData.append(field, entry);
        return;
    }
    if (entry === null) {
        formData.append(field, '');
        return;
    }
    if (Array.isArray(entry)) {
        entry.forEach((item, index) => appendValue(formData, `${field}[${index}]`, item));
        return;
    }
    if (typeof entry === 'object' && !(entry instanceof Blob)) {
        Object.entries(entry as Record<string, unknown>).forEach(([key, value]) => appendValue(formData, `${field}[${key}]`, value));
        return;
    }

    formData.append(field, String(entry));
}

function paginateLocalVehicleModels(
    models: VehicleModel[],
    params: LookupLoadParams,
    vehicleMakeId?: number | null,
): LookupResult<VehicleModel> {
    const filtered = filterNamedResources(models, params.search).filter((model) => {
        if (vehicleMakeId == null) return true;
        return Number(model.make?.id) === Number(vehicleMakeId);
    });
    const currentPage = Math.max(params.page, 1);
    const start = (currentPage - 1) * params.perPage;
    const data = filtered.slice(start, start + params.perPage);
    const from = data.length > 0 ? start + 1 : null;
    const meta: PaginationMeta = {
        current_page: currentPage,
        from,
        last_page: Math.max(1, Math.ceil(filtered.length / params.perPage)),
        per_page: params.perPage,
        to: from === null ? null : from + data.length - 1,
        total: filtered.length,
    };

    return { data, meta, links: undefined };
}

function filterNamedResources<T extends NamedResource>(options: T[], search: string): T[] {
    const term = search.trim().toLowerCase();
    if (term === '') return options;

    return options.filter((option) => [
        option.code,
        option.name,
        option.symbol,
    ].some((value) => typeof value === 'string' && value.toLowerCase().includes(term)));
}
