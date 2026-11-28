/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import { compactObject, readableRelation } from '@/shared/utils/object';
import type {
    AllocationPayload,
    AdjustmentPayload,
    BatchNumberResult,
    CostAdjustmentPayload,
    InventoryAvailability,
    InventoryBatchPayload,
    InventoryBatchPricePayload,
    InventoryBatchPrice,
    InventoryRecord,
    OpeningStockImportPreview,
    ReservationPayload,
    StockBalance,
    StockBalanceSummary,
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

export type StockBalanceCollection = ApiCollection<StockBalance> & { summary: StockBalanceSummary };

export interface StockCardFilters {
    item_id: number;
    warehouse_id: number;
    warehouse_location_id?: number;
    date_from: string;
    date_to: string;
    timezone: string;
}

export interface StockCardResult {
    item: { name: string; code: string; uom: string | null };
    warehouse: { name: string; code: string };
    location: { name: string; code: string } | null;
    date_from: string;
    date_to: string;
    timezone: string;
    snapshot_at: string;
    opening: string;
    total_in: string;
    total_out: string;
    closing: string;
    rows: Array<{
        posted_at: string;
        document_date: string | null;
        movement_number: string;
        description: string | null;
        type: string;
        document: { label: string; url: string | null } | null;
        quantity_in: string;
        quantity_out: string;
        balance: string;
    }>;
}

export const getStockCard = (filters: StockCardFilters, signal?: AbortSignal) =>
    apiClient.get<ApiResource<StockCardResult>>(`${endpoints.inventory}/stock-card`, { params: filters, signal }).then((response) => response.data.data);

export const downloadStockCard = (filters: StockCardFilters, format: 'csv' | 'xls' | 'pdf') =>
    apiClient.get<Blob>(`${endpoints.inventory}/stock-card/export`, { params: { ...filters, format }, responseType: 'blob' }).then((response) => response.data);

export const listStockBalances = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<StockBalanceCollection>(`${endpoints.inventory}/stock-balances`, { params, signal }).then((response) => response.data);

export const downloadStockBalancesCsv = (params: ListParams) =>
    apiClient.get<Blob>(`${endpoints.inventory}/stock-balances/export`, { params, responseType: 'blob' })
        .then((response) => response.data);

export const getAvailability = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiResource<InventoryAvailability>>(`${endpoints.inventory}/availability`, { params, signal }).then((response) => response.data.data);

export const listReservations = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/reservations`, { params, signal }).then((response) => response.data);

export const getReservation = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<InventoryRecord>>(`${endpoints.inventory}/reservations/${id}`, { signal }).then((response) => response.data.data);

export const createReservation = (payload: ReservationPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/reservations`, payload).then((response) => response.data.data);

export const deleteReservation = (id: number) =>
    apiClient.delete(`${endpoints.inventory}/reservations/${id}`).then((response) => response.data);

export const releaseReservation = (id: number, quantity?: string) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/reservations/${id}/release`, quantity ? { quantity } : {}).then((response) => response.data.data);

export const listAllocations = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/allocations`, { params, signal }).then((response) => response.data);

export const getAllocation = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<InventoryRecord>>(`${endpoints.inventory}/allocations/${id}`, { signal }).then((response) => response.data.data);

export const createAllocation = (payload: AllocationPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/allocations`, payload).then((response) => response.data.data);

export const deleteAllocation = (id: number) =>
    apiClient.delete(`${endpoints.inventory}/allocations/${id}`).then((response) => response.data);

export const issueAllocation = (id: number, quantity?: string) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/allocations/${id}/issue`, quantity ? { quantity } : {}).then((response) => response.data.data);

export const releaseAllocation = (id: number, quantity?: string) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/allocations/${id}/release`, quantity ? { quantity } : {}).then((response) => response.data.data);

export const listAdjustments = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/adjustments`, { params, signal }).then((response) => response.data);

export const getAdjustment = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<InventoryRecord>>(`${endpoints.inventory}/adjustments/${id}`, { signal }).then((response) => response.data.data);

export const createAdjustment = (payload: AdjustmentPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/adjustments`, payload).then((response) => response.data.data);

export const deleteAdjustment = (id: number) =>
    apiClient.delete(`${endpoints.inventory}/adjustments/${id}`).then((response) => response.data);

export const postAdjustment = (id: number, payload?: { action_date?: string; reason?: string }) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/adjustments/${id}/post`, payload ?? {}).then((response) => response.data.data);

export const reverseAdjustment = (id: number, payload?: { reversal_date?: string; reason?: string }) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/adjustments/${id}/reverse`, payload ?? {}).then((response) => response.data.data);

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

export const getTransfer = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<InventoryRecord>>(`${endpoints.inventory}/transfers/${id}`, { signal }).then((response) => response.data.data);

export const createTransfer = (payload: TransferPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/transfers`, payload).then((response) => response.data.data);

export const deleteTransfer = (id: number) =>
    apiClient.delete(`${endpoints.inventory}/transfers/${id}`).then((response) => response.data);

export const dispatchTransfer = (id: number, payload?: { action_date?: string; reason?: string }) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/transfers/${id}/post`, payload ?? {}).then((response) => response.data.data);

export const receiveTransfer = (id: number, payload?: { action_date?: string; reason?: string }) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/transfers/${id}/receive`, payload ?? {}).then((response) => response.data.data);

export const cancelTransfer = (id: number, payload?: { action_date?: string; reason?: string }) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/transfers/${id}/cancel`, payload ?? {}).then((response) => response.data.data);

export const reverseTransfer = (id: number, payload?: { reversal_date?: string; reason?: string }) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/transfers/${id}/reverse`, payload ?? {}).then((response) => response.data.data);

export const listValuationLayers = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/valuation-layers`, { params, signal }).then((response) => response.data);

export const listCostAdjustments = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/cost-adjustments`, { params, signal }).then((response) => response.data);

export const getCostAdjustment = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<InventoryRecord>>(`${endpoints.inventory}/cost-adjustments/${id}`, { signal }).then((response) => response.data.data);

export const createCostAdjustment = (payload: CostAdjustmentPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/cost-adjustments`, payload).then((response) => response.data.data);

export const deleteCostAdjustment = (id: number) =>
    apiClient.delete(`${endpoints.inventory}/cost-adjustments/${id}`).then((response) => response.data);

export const postCostAdjustment = (id: number, payload?: { action_date?: string; reason?: string }) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/cost-adjustments/${id}/post`, payload ?? {}).then((response) => response.data.data);

export const listStockCounts = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/stock-counts`, { params, signal }).then((response) => response.data);

export const getStockCount = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<InventoryRecord>>(`${endpoints.inventory}/stock-counts/${id}`, { signal }).then((response) => response.data.data);

export const createStockCount = (payload: StockCountPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/stock-counts`, payload).then((response) => response.data.data);

export const deleteStockCount = (id: number) =>
    apiClient.delete(`${endpoints.inventory}/stock-counts/${id}`).then((response) => response.data);

export const approveStockCount = (id: number, payload?: { action_date?: string; reason?: string }) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/stock-counts/${id}/approve`, payload ?? {}).then((response) => response.data.data);

export const postStockCount = (id: number, payload?: { action_date?: string; reason?: string }) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/stock-counts/${id}/post`, payload ?? {}).then((response) => response.data.data);

export const listBatches = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryRecord>>(`${endpoints.inventory}/batches`, { params, signal }).then((response) => response.data);

export const getBatch = (id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<InventoryRecord>>(`${endpoints.inventory}/batches/${id}`, { signal }).then((response) => response.data.data);

export const createInventoryBatch = (payload: InventoryBatchPayload) =>
    apiClient.post<ApiResource<InventoryRecord>>(`${endpoints.inventory}/batches`, payload).then((response) => response.data.data);

export const deleteBatch = (id: number) =>
    apiClient.delete(`${endpoints.inventory}/batches/${id}`).then((response) => response.data);

export const generateInventoryBatchNumber = () =>
    apiClient.post<ApiResource<BatchNumberResult>>(`${endpoints.inventory}/batches/generate-number`, {}).then((response) => response.data.data);

export const listBatchPrices = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<InventoryBatchPrice>>(`${endpoints.inventory}/batch-prices`, { params, signal }).then((response) => response.data);

export const createBatchPrice = (payload: InventoryBatchPricePayload) =>
    apiClient.post<ApiResource<InventoryBatchPrice>>(`${endpoints.inventory}/batch-prices`, payload).then((response) => response.data.data);

export const supersedeBatchPrice = (id: number, payload: InventoryBatchPricePayload) =>
    apiClient.post<ApiResource<InventoryBatchPrice>>(`${endpoints.inventory}/batch-prices/${id}/supersede`, payload).then((response) => response.data.data);

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
    const lot = primaryKey === 'batch_number' && typeof row.lot_number === 'string' && row.lot_number.trim()
        ? `Lot ${row.lot_number.trim()}`
        : null;
    const context = [item, variant, lot].filter((value) => value !== '-' && value !== null).join(' / ');

    return {
        id: Number(row.id),
        code,
        name: context || code,
    };
}
