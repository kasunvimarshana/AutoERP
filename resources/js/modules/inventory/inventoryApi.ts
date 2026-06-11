import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface StockBalance extends Record<string, unknown> {
    id: number;
    item?: { id: number; name: string; code?: string };
    warehouse?: { id: number; name: string; code?: string };
    warehouse_location?: { id: number; name: string; code?: string };
    batch?: { id: number; batch_number?: string; lot_number?: string };
    quantity_on_hand?: string;
    quantity_reserved?: string;
    quantity_allocated?: string;
    quantity_available?: string;
    quantity_in_transit?: string;
    quantity_damaged?: string;
    quantity_quarantine?: string;
    quantity_expired?: string;
    quantity_scrapped?: string;
    total_value?: string;
}

export type InventoryRecord = Record<string, unknown> & { id: number; status?: string };

export interface ReservationPayload {
    reservation_date: string;
    item_id: number;
    warehouse_id: number;
    quantity_reserved: string;
    warehouse_location_id?: number;
    batch_id?: number;
    uom_id?: number;
    source_type?: string;
    source_id?: number;
    source_line_type?: string;
    source_line_id?: number;
    notes?: string;
}

export interface AllocationPayload {
    allocation_date: string;
    item_id: number;
    warehouse_id: number;
    quantity_allocated: string;
    reservation_id?: number;
    warehouse_location_id?: number;
    batch_id?: number;
    serial_number_id?: number;
    uom_id?: number;
    source_type?: string;
    source_id?: number;
    source_line_type?: string;
    source_line_id?: number;
    notes?: string;
}

export interface TransferPayload {
    transfer_date: string;
    from_warehouse_id: number;
    to_warehouse_id: number;
    from_warehouse_location_id?: number;
    to_warehouse_location_id?: number;
    reason?: string;
    notes?: string;
    lines: Array<{ item_id: number; quantity: string; unit_cost?: string; item_variant_id?: number; batch_id?: number; serial_number_id?: number; uom_id?: number }>;
}

export interface StockCountPayload {
    count_date: string;
    count_type?: 'stock_count' | 'cycle_count';
    warehouse_id: number;
    warehouse_location_id?: number;
    reason?: string;
    notes?: string;
    lines: Array<{ item_id: number; counted_quantity: string; system_quantity?: string; unit_cost?: string; item_variant_id?: number; batch_id?: number; serial_number_id?: number; uom_id?: number; notes?: string }>;
}

export interface CostAdjustmentPayload {
    adjustment_date: string;
    reason?: string;
    notes?: string;
    lines: Array<{ valuation_layer_id: number; adjustment_amount: string; reason?: string }>;
}

export const listStockBalances = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<StockBalance>>(`${endpoints.inventory}/stock-balances`, { params, signal }).then((response) => response.data);

export const getAvailability = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiResource<Record<string, unknown>>>(`${endpoints.inventory}/availability`, { params, signal }).then((response) => response.data.data);

export const listReservations = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/reservations`, { params, signal }).then((response) => response.data);

export const createReservation = (payload: ReservationPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/reservations`, payload).then((response) => response.data.data);

export const releaseReservation = (id: number, quantity?: string) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/reservations/${id}/release`, quantity ? { quantity } : {}).then((response) => response.data.data);

export const listAllocations = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/allocations`, { params, signal }).then((response) => response.data);

export const createAllocation = (payload: AllocationPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/allocations`, payload).then((response) => response.data.data);

export const issueAllocation = (id: number, quantity?: string) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/allocations/${id}/issue`, quantity ? { quantity } : {}).then((response) => response.data.data);

export const releaseAllocation = (id: number, quantity?: string) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/allocations/${id}/release`, quantity ? { quantity } : {}).then((response) => response.data.data);

export const listTransfers = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/transfers`, { params, signal }).then((response) => response.data);

export const createTransfer = (payload: TransferPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/transfers`, payload).then((response) => response.data.data);

export const dispatchTransfer = (id: number) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/transfers/${id}/post`, {}).then((response) => response.data.data);

export const receiveTransfer = (id: number) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/transfers/${id}/receive`, {}).then((response) => response.data.data);

export const cancelTransfer = (id: number) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/transfers/${id}/cancel`, {}).then((response) => response.data.data);

export const listValuationLayers = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/valuation-layers`, { params, signal }).then((response) => response.data);

export const listCostAdjustments = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/cost-adjustments`, { params, signal }).then((response) => response.data);

export const createCostAdjustment = (payload: CostAdjustmentPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/cost-adjustments`, payload).then((response) => response.data.data);

export const postCostAdjustment = (id: number) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/cost-adjustments/${id}/post`, {}).then((response) => response.data.data);

export const listStockCounts = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/stock-counts`, { params, signal }).then((response) => response.data);

export const createStockCount = (payload: StockCountPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/stock-counts`, payload).then((response) => response.data.data);

export const approveStockCount = (id: number) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/stock-counts/${id}/approve`, {}).then((response) => response.data.data);

export const postStockCount = (id: number) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/stock-counts/${id}/post`, {}).then((response) => response.data.data);

export const listBatches = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/batches`, { params, signal }).then((response) => response.data);

export const listSerials = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/serials`, { params, signal }).then((response) => response.data);

export const listStateChanges = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/state-changes`, { params, signal }).then((response) => response.data);
