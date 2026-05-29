import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    adjustments,
    availabilityPreview,
    batches,
    costLayers,
    cycleCounts,
    getAdjustmentById,
    getMovementById,
    getTransferById,
    inventoryDashboardMetrics,
    pickingTasks,
    putAwayTasks,
    receiptInspections,
    reservations,
    serials,
    stockLevels,
    stockMovements,
    traceability,
    transfers,
    valuations,
} from '../mock/inventoryMock';
import type {
    CostLayer,
    CycleCount,
    InventoryAuditEntry,
    InventoryBatch,
    InventorySerial,
    InventoryValuation,
    PickingTask,
    PutAwayTask,
    ReceiptInspection,
    StockAdjustment,
    StockAvailabilityPreviewRequest,
    StockAvailabilityPreviewResult,
    StockLevel,
    StockMovement,
    StockReservation,
    StockTransfer,
} from '../types/inventory.types';

type BackendRecord = Record<string, unknown>;
const INVENTORY_API_MODE = import.meta.env.VITE_INVENTORY_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return INVENTORY_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419, 422]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }
    try {
        return await realCall();
    } catch (error) {
        if (INVENTORY_API_MODE === 'real') {
            throw error;
        }
        if (error instanceof ApiError && !fallbackStatuses.includes(error.status)) {
            throw error;
        }
        return mockCall();
    }
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function normalizeStockLevel(raw: BackendRecord): StockLevel {
    const meta = raw.metadata && typeof raw.metadata === 'object' ? raw.metadata as BackendRecord : {};
    return {
        available: asString(raw.available_quantity ?? meta.available_quantity, 'Backend provided'),
        batchOrSerial: asString(meta.batch_or_serial ?? raw.batch_number ?? raw.serial_number, 'None'),
        id: asString(raw.id),
        itemCode: asString(meta.item_code ?? raw.item_code, 'Backend item'),
        itemName: asString(meta.item_name ?? raw.item_name ?? raw.item_id, 'Backend item'),
        location: asString(meta.location_name ?? raw.location_id, 'Backend location'),
        onHand: asString(raw.on_hand_quantity ?? raw.quantity_on_hand ?? raw.quantity, 'Backend provided'),
        reserved: asString(raw.reserved_quantity ?? meta.reserved_quantity, 'Backend provided'),
        status: asString(raw.status ?? meta.status, 'active'),
        uom: asString(meta.uom_code ?? raw.uom_id, 'Backend UOM'),
        updatedAt: asString(raw.updated_at, 'Backend timestamp'),
        warehouse: asString(meta.warehouse_name ?? raw.warehouse_id, 'Backend warehouse'),
    };
}

function normalizeMovement(raw: BackendRecord): StockMovement {
    const meta = raw.metadata && typeof raw.metadata === 'object' ? raw.metadata as BackendRecord : {};
    return {
        batchOrSerial: asString(meta.batch_or_serial ?? raw.batch_id ?? raw.serial_id),
        costEffect: asString(meta.cost_effect, 'Backend valuation'),
        id: asString(raw.id),
        itemName: asString(meta.item_name ?? raw.item_id, 'Backend item'),
        location: asString(meta.location_name ?? raw.location_id, 'Backend location'),
        movementDate: asString(raw.movement_date ?? raw.created_at, 'Backend date'),
        movementNumber: asString(raw.movement_number ?? raw.reference, `MOV-${asString(raw.id)}`),
        movementType: asString(raw.movement_type ?? raw.type, 'adjustment_in') as StockMovement['movementType'],
        quantity: asString(raw.quantity, 'Backend quantity'),
        quantityEffect: asString(meta.quantity_effect, 'Backend effect'),
        sourceModule: asString(raw.source_module ?? meta.source_module, 'inventory'),
        sourceReference: asString(raw.source_reference ?? raw.reference, 'Backend source'),
        status: asString(raw.status, 'posted'),
        uom: asString(meta.uom_code ?? raw.uom_id, 'Backend UOM'),
        warehouse: asString(meta.warehouse_name ?? raw.warehouse_id, 'Backend warehouse'),
    };
}

export const inventoryApi = {
    listDashboardMetrics: () => mockCollectionResponse(inventoryDashboardMetrics),

    listStockLevels: (): Promise<ApiCollectionResponse<StockLevel>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-levels');
            return { ...response, data: response.data.map(normalizeStockLevel) };
        },
        () => mockCollectionResponse(stockLevels),
    ),

    getStockLevel: (id: string): Promise<ApiResponse<StockLevel>> => mockResponse(stockLevels.find((row) => row.id === id) ?? stockLevels[0]),

    listStockMovements: (): Promise<ApiCollectionResponse<StockMovement>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-movements');
            return { ...response, data: response.data.map(normalizeMovement) };
        },
        () => mockCollectionResponse(stockMovements),
    ),

    getStockMovement: (id: string): Promise<ApiResponse<StockMovement>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/inventory/stock-movements/${id}`);
            return { ...response, data: normalizeMovement(response.data) };
        },
        () => mockResponse(getMovementById(id)),
    ),

    listReservations: (): Promise<ApiCollectionResponse<StockReservation>> => withMockFallback(
        () => httpClient<ApiCollectionResponse<StockReservation>>('/api/inventory/stock-reservations'),
        () => mockCollectionResponse(reservations),
    ),
    createReservation: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/inventory/stock-reservations', { body: input, method: 'POST' }), () => mockResponse(input)),
    releaseReservation: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/inventory/stock-reservations/${id}`, { method: 'DELETE' }), () => mockResponse({ action: 'release-requested', id })),

    listTransfers: (): Promise<ApiCollectionResponse<StockTransfer>> => withMockFallback(
        () => httpClient<ApiCollectionResponse<StockTransfer>>('/api/inventory/stock-transfers'),
        () => mockCollectionResponse(transfers),
    ),
    getTransfer: (id: string): Promise<ApiResponse<StockTransfer>> => mockResponse(getTransferById(id)),
    createTransfer: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/inventory/stock-transfers', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateTransfer: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/inventory/stock-transfers/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),
    submitTransfer: (id: string) => mockResponse({ action: 'submit-transfer', id }),
    approveTransfer: (id: string) => mockResponse({ action: 'approve-transfer', id }),
    completeTransfer: (id: string) => mockResponse({ action: 'complete-transfer', id }),
    cancelTransfer: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/inventory/stock-transfers/${id}`, { method: 'DELETE' }), () => mockResponse({ action: 'cancel-transfer', id })),

    listAdjustments: (): Promise<ApiCollectionResponse<StockAdjustment>> => withMockFallback(
        () => httpClient<ApiCollectionResponse<StockAdjustment>>('/api/inventory/stock-adjustments'),
        () => mockCollectionResponse(adjustments),
    ),
    getAdjustment: (id: string): Promise<ApiResponse<StockAdjustment>> => mockResponse(getAdjustmentById(id)),
    createAdjustment: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/inventory/stock-adjustments', { body: input, method: 'POST' }), () => mockResponse(input)),
    updateAdjustment: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/inventory/stock-adjustments/${id}`, { body: input, method: 'PUT' }), () => mockResponse(input)),
    postAdjustment: (id: string) => mockResponse({ action: 'post-adjustment', id }),
    cancelAdjustment: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/inventory/stock-adjustments/${id}`, { method: 'DELETE' }), () => mockResponse({ action: 'cancel-adjustment', id })),

    listCycleCounts: (): Promise<ApiCollectionResponse<CycleCount>> => withMockFallback(() => httpClient<ApiCollectionResponse<CycleCount>>('/api/inventory/cycle-count-headers'), () => mockCollectionResponse(cycleCounts)),
    listBatches: (): Promise<ApiCollectionResponse<InventoryBatch>> => withMockFallback(() => httpClient<ApiCollectionResponse<InventoryBatch>>('/api/inventory/batches'), () => mockCollectionResponse(batches)),
    listSerials: (): Promise<ApiCollectionResponse<InventorySerial>> => withMockFallback(() => httpClient<ApiCollectionResponse<InventorySerial>>('/api/inventory/serials'), () => mockCollectionResponse(serials)),
    listReceiptInspections: (): Promise<ApiCollectionResponse<ReceiptInspection>> => withMockFallback(() => httpClient<ApiCollectionResponse<ReceiptInspection>>('/api/inventory/receipt-inspections'), () => mockCollectionResponse(receiptInspections)),
    listPutAwayTasks: (): Promise<ApiCollectionResponse<PutAwayTask>> => withMockFallback(() => httpClient<ApiCollectionResponse<PutAwayTask>>('/api/inventory/put-away-tasks'), () => mockCollectionResponse(putAwayTasks)),
    listPickingTasks: (): Promise<ApiCollectionResponse<PickingTask>> => withMockFallback(() => httpClient<ApiCollectionResponse<PickingTask>>('/api/inventory/picking-tasks'), () => mockCollectionResponse(pickingTasks)),
    listValuation: (): Promise<ApiCollectionResponse<InventoryValuation>> => mockCollectionResponse(valuations),
    getCostLayers: (_itemId?: string): Promise<ApiCollectionResponse<CostLayer>> => withMockFallback(() => httpClient<ApiCollectionResponse<CostLayer>>('/api/inventory/inventory-cost-layers'), () => mockCollectionResponse(costLayers)),

    previewStockAvailability: (input: StockAvailabilityPreviewRequest): Promise<ApiPreviewResponse<StockAvailabilityPreviewRequest, StockAvailabilityPreviewResult['calculated']>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiPreviewResponse<StockAvailabilityPreviewRequest, StockAvailabilityPreviewResult['calculated']>>('/api/inventory/engines/stock-availability/preview', { body: input, method: 'POST' });
            return response;
        },
        () => mockPreviewResponse(input, availabilityPreview.calculated, availabilityPreview.breakdown, availabilityPreview.warnings),
    ),

    getTraceability: (): Promise<ApiCollectionResponse<InventoryAuditEntry>> => withMockFallback(() => httpClient<ApiCollectionResponse<InventoryAuditEntry>>('/api/inventory/trace-logs'), () => mockCollectionResponse(traceability)),
    getInventoryActivity: (): Promise<ApiCollectionResponse<InventoryAuditEntry>> => mockCollectionResponse(traceability),
};
