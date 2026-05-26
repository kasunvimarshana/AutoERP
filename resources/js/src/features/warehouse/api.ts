import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type {
    WarehouseListFilters,
    WarehouseLocationListFilters,
    WarehouseLocationPayload,
    WarehouseLocationRecord,
    WarehousePayload,
    WarehouseRecord,
    WarehouseStockLevelRecord,
    WarehouseStockMovementFilters,
    WarehouseStockMovementRecord,
} from './types';

export const warehousesApi = {
    listWarehouses(filters: WarehouseListFilters): Promise<PaginatedResult<WarehouseRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<WarehouseRecord>>('/warehouses', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getWarehouse(warehouseId: number) {
        return apiClient.get<ApiResourceEnvelope<WarehouseRecord> | WarehouseRecord>(`/warehouses/${warehouseId}`).then((payload) => unwrapResource(payload));
    },
    createWarehouse(payload: WarehousePayload) {
        return apiClient.post<ApiResourceEnvelope<WarehouseRecord> | WarehouseRecord>('/warehouses', payload).then((result) => unwrapResource(result));
    },
    updateWarehouse(warehouseId: number, payload: WarehousePayload) {
        return apiClient.put<ApiResourceEnvelope<WarehouseRecord> | WarehouseRecord>(`/warehouses/${warehouseId}`, payload).then((result) => unwrapResource(result));
    },
    deleteWarehouse(warehouseId: number) {
        return apiClient.delete<{ message: string }>(`/warehouses/${warehouseId}`);
    },
    listWarehouseLocations(warehouseId: number, filters: WarehouseLocationListFilters): Promise<PaginatedResult<WarehouseLocationRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<WarehouseLocationRecord>>(`/warehouses/${warehouseId}/locations`, { query: toQuery(filters) })
            .then((payload) => unwrapPaginated(payload));
    },
    createWarehouseLocation(warehouseId: number, payload: WarehouseLocationPayload) {
        return apiClient
            .post<ApiResourceEnvelope<WarehouseLocationRecord> | WarehouseLocationRecord>(`/warehouses/${warehouseId}/locations`, payload)
            .then((result) => unwrapResource(result));
    },
    updateWarehouseLocation(warehouseId: number, locationId: number, payload: WarehouseLocationPayload) {
        return apiClient
            .put<ApiResourceEnvelope<WarehouseLocationRecord> | WarehouseLocationRecord>(`/warehouses/${warehouseId}/locations/${locationId}`, payload)
            .then((result) => unwrapResource(result));
    },
    deleteWarehouseLocation(warehouseId: number, locationId: number) {
        return apiClient.delete<{ message: string }>(`/warehouses/${warehouseId}/locations/${locationId}`);
    },
    listWarehouseStockLevels(warehouseId: number, tenantId: number, page = 1, perPage = 25): Promise<PaginatedResult<WarehouseStockLevelRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<WarehouseStockLevelRecord>>(`/warehouses/${warehouseId}/stock-levels`, { query: { tenant_id: tenantId, page, per_page: perPage } })
            .then((payload) => unwrapPaginated(payload));
    },
    listWarehouseStockMovements(warehouseId: number, filters: WarehouseStockMovementFilters): Promise<PaginatedResult<WarehouseStockMovementRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<WarehouseStockMovementRecord>>(`/warehouses/${warehouseId}/stock-movements`, { query: toQuery(filters) })
            .then((payload) => unwrapPaginated(payload));
    },
};
