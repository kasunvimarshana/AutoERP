import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import { compactObject, readableRelation } from '@/shared/utils/object';
import type {
    AllocationPayload,
    AdjustmentPayload,
    CostAdjustmentPayload,
    InventoryAvailability,
    InventoryRecord,
    OpeningStockImportPreview,
    ReservationPayload,
    StockBalance,
    StockCountPayload,
    TransferPayload,
} from './inventoryTypes';

export type {
    AllocationPayload,
    AdjustmentPayload,
    CostAdjustmentPayload,
    InventoryAvailability,
    InventoryRecord,
    OpeningStockImportPreview,
    ReservationPayload,
    StockBalance,
    StockCountPayload,
    TransferPayload,
} from './inventoryTypes';

export const listStockBalances = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<StockBalance>>(`${endpoints.inventory}/stock-balances`, { params, signal }).then((response) => response.data);

export const getAvailability = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiResource<InventoryAvailability>>(`${endpoints.inventory}/availability`, { params, signal }).then((response) => response.data.data);

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

export const listAdjustments = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/adjustments`, { params, signal }).then((response) => response.data);

export const createAdjustment = (payload: AdjustmentPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/adjustments`, payload).then((response) => response.data.data);

export const postAdjustment = (id: number) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/adjustments/${id}/post`, {}).then((response) => response.data.data);

export const downloadOpeningStockTemplate = () =>
    apiClient.get<Blob>(`${endpoints.inventory}/opening-stock-import/template`, { responseType: 'blob' })
        .then((response) => response.data);

export const previewOpeningStockImport = (payload: FormData) =>
    apiClient.post<ApiResource<OpeningStockImportPreview>>(`${endpoints.inventory}/opening-stock-import/preview`, payload)
        .then((response) => response.data.data);

export const createOpeningStockImport = (payload: FormData) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/opening-stock-import`, payload)
        .then((response) => response.data.data);

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

interface InventoryDimensionLookupFilters {
    itemId?: number | null;
    itemVariantId?: number | null;
    warehouseId?: number | null;
    warehouseLocationId?: number | null;
    batchId?: number | null;
}

export async function searchInventoryBatches(
    { search, page, perPage, signal }: LookupLoadParams,
    filters: InventoryDimensionLookupFilters = {},
): Promise<LookupResult<NamedResource>> {
    const result = await listBatches(compactObject({
        search,
        page,
        per_page: perPage,
        item_id: filters.itemId,
        item_variant_id: filters.itemVariantId,
        status: 'active',
    }), signal);

    return {
        data: result.data.map((row) => toNamedResource(row, 'batch_number', 'lot_number')),
        links: result.links,
        meta: result.meta,
    };
}

export async function searchInventorySerials(
    { search, page, perPage, signal }: LookupLoadParams,
    filters: InventoryDimensionLookupFilters = {},
): Promise<LookupResult<NamedResource>> {
    const result = await listSerials(compactObject({
        search,
        page,
        per_page: perPage,
        item_id: filters.itemId,
        item_variant_id: filters.itemVariantId,
        warehouse_id: filters.warehouseId,
        warehouse_location_id: filters.warehouseLocationId,
        batch_id: filters.batchId,
        status: 'available',
    }), signal);

    return {
        data: result.data.map((row) => toNamedResource(row, 'serial_number')),
        links: result.links,
        meta: result.meta,
    };
}

function toNamedResource(row: InventoryRecord, primaryKey: string, fallbackKey?: string): NamedResource {
    const code = String(row[primaryKey] ?? (fallbackKey ? row[fallbackKey] : '') ?? '');
    const item = readableRelation(row.item);
    const variant = readableRelation(row.variant);
    const context = [item, variant].filter((value) => value !== '-').join(' / ');

    return {
        id: Number(row.id),
        code,
        name: context || code,
    };
}
