import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type {
    CycleCountCompletePayload,
    CycleCountListFilters,
    CycleCountPayload,
    CycleCountRecord,
    InventoryStockLevelRecord,
    InventoryStockMovementRecord,
    InventoryWarehouseFilters,
    ReleaseExpiredPayload,
    StockReservationListFilters,
    StockReservationPayload,
    StockReservationRecord,
    TransferOrderListFilters,
    TransferOrderPayload,
    TransferOrderReceivePayload,
    TransferOrderRecord,
    ValuationConfigListFilters,
    ValuationConfigPayload,
    ValuationConfigRecord,
} from './types';

export const inventoryApi = {
    listWarehouseStockLevels(warehouseId: number, tenantId: number, page = 1, perPage = 25): Promise<PaginatedResult<InventoryStockLevelRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<InventoryStockLevelRecord>>(`/inventory/warehouses/${warehouseId}/stock-levels`, { query: { tenant_id: tenantId, page, per_page: perPage } })
            .then((payload) => unwrapPaginated(payload));
    },
    listWarehouseStockMovements(warehouseId: number, filters: InventoryWarehouseFilters): Promise<PaginatedResult<InventoryStockMovementRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<InventoryStockMovementRecord>>(`/inventory/warehouses/${warehouseId}/movements`, { query: toQuery(filters) })
            .then((payload) => unwrapPaginated(payload));
    },
    listTransferOrders(filters: TransferOrderListFilters): Promise<PaginatedResult<TransferOrderRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<TransferOrderRecord>>('/inventory/transfer-orders', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getTransferOrder(transferOrderId: number, tenantId: number) {
        return apiClient
            .get<ApiResourceEnvelope<TransferOrderRecord> | TransferOrderRecord>(`/inventory/transfer-orders/${transferOrderId}`, { query: { tenant_id: tenantId } })
            .then((payload) => unwrapResource(payload));
    },
    createTransferOrder(payload: TransferOrderPayload) {
        return apiClient.post<ApiResourceEnvelope<TransferOrderRecord> | TransferOrderRecord>('/inventory/transfer-orders', payload).then((result) => unwrapResource(result));
    },
    approveTransferOrder(transferOrderId: number, tenantId: number) {
        return apiClient
            .post<ApiResourceEnvelope<TransferOrderRecord> | TransferOrderRecord>(`/inventory/transfer-orders/${transferOrderId}/approve`, { tenant_id: tenantId })
            .then((result) => unwrapResource(result));
    },
    receiveTransferOrder(transferOrderId: number, payload: TransferOrderReceivePayload) {
        return apiClient
            .post<ApiResourceEnvelope<TransferOrderRecord> | TransferOrderRecord>(`/inventory/transfer-orders/${transferOrderId}/receive`, payload)
            .then((result) => unwrapResource(result));
    },
    listCycleCounts(filters: CycleCountListFilters): Promise<PaginatedResult<CycleCountRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<CycleCountRecord>>('/inventory/cycle-counts', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    getCycleCount(cycleCountId: number, tenantId: number) {
        return apiClient
            .get<ApiResourceEnvelope<CycleCountRecord> | CycleCountRecord>(`/inventory/cycle-counts/${cycleCountId}`, { query: { tenant_id: tenantId } })
            .then((payload) => unwrapResource(payload));
    },
    createCycleCount(payload: CycleCountPayload) {
        return apiClient.post<ApiResourceEnvelope<CycleCountRecord> | CycleCountRecord>('/inventory/cycle-counts', payload).then((result) => unwrapResource(result));
    },
    startCycleCount(cycleCountId: number, tenantId: number) {
        return apiClient
            .post<ApiResourceEnvelope<CycleCountRecord> | CycleCountRecord>(`/inventory/cycle-counts/${cycleCountId}/start`, { tenant_id: tenantId })
            .then((result) => unwrapResource(result));
    },
    completeCycleCount(cycleCountId: number, payload: CycleCountCompletePayload) {
        return apiClient
            .post<ApiResourceEnvelope<CycleCountRecord> | CycleCountRecord>(`/inventory/cycle-counts/${cycleCountId}/complete`, payload)
            .then((result) => unwrapResource(result));
    },
    listStockReservations(filters: StockReservationListFilters): Promise<PaginatedResult<StockReservationRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<StockReservationRecord>>('/inventory/stock-reservations', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    createStockReservation(payload: StockReservationPayload) {
        return apiClient.post<ApiResourceEnvelope<StockReservationRecord> | StockReservationRecord>('/inventory/stock-reservations', payload).then((result) => unwrapResource(result));
    },
    deleteStockReservation(reservationId: number, tenantId: number) {
        return apiClient.delete(`/inventory/stock-reservations/${reservationId}`, { query: { tenant_id: tenantId } });
    },
    releaseExpiredReservations(payload: ReleaseExpiredPayload) {
        return apiClient.post<{ released_count: number }>('/inventory/stock-reservations/release-expired', payload);
    },
    listValuationConfigs(filters: ValuationConfigListFilters): Promise<PaginatedResult<ValuationConfigRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<ValuationConfigRecord>>('/inventory/valuation-configs', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    createValuationConfig(payload: ValuationConfigPayload) {
        return apiClient.post<ApiResourceEnvelope<ValuationConfigRecord> | ValuationConfigRecord>('/inventory/valuation-configs', payload).then((result) => unwrapResource(result));
    },
    updateValuationConfig(configId: number, payload: ValuationConfigPayload) {
        return apiClient.put<ApiResourceEnvelope<ValuationConfigRecord> | ValuationConfigRecord>(`/inventory/valuation-configs/${configId}`, payload).then((result) => unwrapResource(result));
    },
    deleteValuationConfig(configId: number, tenantId: number) {
        return apiClient.delete(`/inventory/valuation-configs/${configId}`, { query: { tenant_id: tenantId } });
    },
};
