import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import { requestLookup } from '@/shared/api/lookupRequest';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type {
    Warehouse,
    WarehouseListResponse,
    WarehouseLocation,
    WarehouseLocationPayload,
    WarehouseLocationSummary,
    WarehousePayload,
    WarehouseSummary,
} from './warehouseTypes';

export async function listWarehouses(params: ListParams, signal?: AbortSignal): Promise<WarehouseListResponse<WarehouseSummary>> {
    const response = await apiClient.get<ApiCollection<WarehouseSummary>>(endpoints.warehouses, { params, signal });
    return response.data;
}

export async function getWarehouse(id: number, signal?: AbortSignal): Promise<Warehouse> {
    const response = await apiClient.get<ApiResource<Warehouse>>(`${endpoints.warehouses}/${id}`, { signal });
    return response.data.data;
}

export async function createWarehouse(payload: WarehousePayload): Promise<Warehouse> {
    const response = await apiClient.post<ApiResource<Warehouse>>(endpoints.warehouses, payload);
    return response.data.data;
}

export async function updateWarehouse(id: number, payload: Partial<WarehousePayload>): Promise<Warehouse> {
    const response = await apiClient.put<ApiResource<Warehouse>>(`${endpoints.warehouses}/${id}`, payload);
    return response.data.data;
}

export async function setWarehouseActive(id: number, active: boolean): Promise<Warehouse> {
    const response = await apiClient.patch<ApiResource<Warehouse>>(`${endpoints.warehouses}/${id}/${active ? 'activate' : 'deactivate'}`);
    return response.data.data;
}

export async function getDefaultWarehouse(signal?: AbortSignal): Promise<WarehouseSummary | null> {
    const response = await apiClient.get<ApiResource<WarehouseSummary | null>>(`${endpoints.warehouses}/default`, { signal });
    return response.data.data;
}

export function searchWarehouseOptions(params: LookupLoadParams): Promise<LookupResult<WarehouseSummary>> {
    return requestLookup<WarehouseSummary>(endpoints.warehouses, params, { is_active: true });
}

export async function listWarehouseLocations(params: ListParams, signal?: AbortSignal): Promise<WarehouseListResponse<WarehouseLocationSummary>> {
    const response = await apiClient.get<ApiCollection<WarehouseLocationSummary>>(endpoints.warehouseLocations, { params, signal });
    return response.data;
}

export async function getWarehouseLocation(id: number, signal?: AbortSignal): Promise<WarehouseLocation> {
    const response = await apiClient.get<ApiResource<WarehouseLocation>>(`${endpoints.warehouseLocations}/${id}`, { signal });
    return response.data.data;
}

export async function createWarehouseLocation(payload: WarehouseLocationPayload): Promise<WarehouseLocation> {
    const response = await apiClient.post<ApiResource<WarehouseLocation>>(endpoints.warehouseLocations, payload);
    return response.data.data;
}

export async function updateWarehouseLocation(id: number, payload: Partial<WarehouseLocationPayload>): Promise<WarehouseLocation> {
    const response = await apiClient.put<ApiResource<WarehouseLocation>>(`${endpoints.warehouseLocations}/${id}`, payload);
    return response.data.data;
}

export async function setWarehouseLocationActive(id: number, active: boolean): Promise<WarehouseLocation> {
    const response = await apiClient.patch<ApiResource<WarehouseLocation>>(`${endpoints.warehouseLocations}/${id}/${active ? 'activate' : 'deactivate'}`);
    return response.data.data;
}

export async function getDefaultWarehouseLocation(warehouseId: number, signal?: AbortSignal): Promise<WarehouseLocationSummary | null> {
    const response = await apiClient.get<ApiResource<WarehouseLocationSummary | null>>(`${endpoints.warehouseLocations}/default`, {
        params: { warehouse_id: warehouseId },
        signal,
    });
    return response.data.data;
}

export function searchWarehouseLocationOptions(
    params: LookupLoadParams,
    warehouseId?: number | null,
): Promise<LookupResult<WarehouseLocationSummary>> {
    return requestLookup<WarehouseLocationSummary>(endpoints.warehouseLocations, params, {
        warehouse_id: warehouseId ?? undefined,
        is_active: true,
    });
}
