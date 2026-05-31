import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { getStoredAuthSession, getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
import { httpClient } from '../../../services/api/httpClient';
import type {
    CostLayer,
    CycleCount,
    InventoryAuditEntry,
    InventoryBatch,
    InventoryLookupOption,
    InventorySerial,
    InventoryValuation,
    PickingTask,
    PutAwayTask,
    ReceiptInspection,
    StockAdjustment,
    StockAdjustmentFormInput,
    StockAdjustmentLine,
    StockAvailabilityPreviewRequest,
    StockAvailabilityPreviewResult,
    StockLevel,
    StockMovement,
    StockMovementType,
    StockReservation,
    StockTransfer,
    StockTransferFormInput,
    StockTransferLine,
} from '../types/inventory.types';

type BackendRecord = Record<string, unknown>;

function record(value: unknown): BackendRecord {
    return value !== null && typeof value === 'object' && !Array.isArray(value) ? value as BackendRecord : {};
}

function asString(value: unknown, fallback = ''): string {
    return value === null || value === undefined || value === '' ? fallback : String(value);
}

function asNumberString(value: unknown, fallback = '0.0000'): string {
    const number = Number(value);
    return Number.isFinite(number) ? number.toFixed(4) : fallback;
}

function asStatus(value: unknown, fallback = 'active'): string {
    return asString(value, fallback).toLowerCase();
}

function numberOrUndefined(value: string | null | undefined): number | undefined {
    const parsed = Number(value);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : undefined;
}

function contextQuery(extra: Record<string, string | number | boolean | null | undefined> = {}) {
    return {
        organization_unit_id: numberOrUndefined(getStoredOrganizationUnitId()),
        per_page: 100,
        tenant_id: numberOrUndefined(getStoredTenantId()),
        ...extra,
    };
}

function contextPayload(input: BackendRecord = {}): BackendRecord {
    return {
        ...input,
        organization_unit_id: input.organization_unit_id ?? numberOrUndefined(getStoredOrganizationUnitId()),
        tenant_id: input.tenant_id ?? numberOrUndefined(getStoredTenantId()),
    };
}

function userId(): number | undefined {
    return numberOrUndefined(getStoredAuthSession().user?.id);
}

function collectionPayload<T>(response: ApiCollectionResponse<BackendRecord>, mapper: (row: BackendRecord) => T): ApiCollectionResponse<T> {
    return { ...response, data: response.data.map(mapper) };
}

function normalizeStockLevel(raw: BackendRecord): StockLevel {
    const meta = record(raw.metadata);
    const onHand = Number(raw.quantity_on_hand ?? 0);
    const reserved = Number(raw.quantity_reserved ?? 0);
    const blocked = Number(raw.quantity_blocked ?? 0);
    const damaged = Number(raw.quantity_damaged ?? 0);
    const available = Math.max(0, onHand - reserved - blocked - damaged);

    return {
        available: asNumberString(available),
        batchOrSerial: asString(meta.batch_or_serial ?? raw.batch_number ?? raw.serial_number, 'None'),
        id: asString(raw.id),
        itemCode: asString(meta.item_code ?? raw.item_code ?? raw.item_id),
        itemName: asString(meta.item_name ?? raw.item_name ?? raw.item_id, 'Item'),
        location: asString(meta.location_name ?? raw.location_id, 'Default'),
        onHand: asNumberString(raw.quantity_on_hand),
        reserved: asNumberString(raw.quantity_reserved),
        status: asStatus(raw.condition, 'good'),
        uom: asString(meta.uom_code ?? raw.base_uom_id, 'UOM'),
        updatedAt: asString(raw.updated_at),
        warehouse: asString(meta.warehouse_name ?? raw.warehouse_id, 'Warehouse'),
    };
}

function normalizeMovementType(value: unknown): StockMovementType {
    const normalized = asString(value, 'adjustment_in').toLowerCase().replace('stock_', '').replace('opening_balance', 'receipt');
    const allowed: StockMovementType[] = ['receipt', 'issue', 'consumption', 'transfer_in', 'transfer_out', 'adjustment_in', 'adjustment_out', 'return_in', 'return_out'];
    return allowed.includes(normalized as StockMovementType) ? normalized as StockMovementType : 'adjustment_in';
}

function normalizeMovement(raw: BackendRecord): StockMovement {
    const meta = record(raw.metadata);
    return {
        batchOrSerial: asString(meta.batch_or_serial ?? raw.batch_id ?? raw.serial_id, 'None'),
        costEffect: asNumberString(raw.total_cost ?? meta.cost_effect),
        id: asString(raw.id),
        itemName: asString(meta.item_name ?? raw.item_id, 'Item'),
        location: asString(meta.location_name ?? raw.location_id, 'Default'),
        movementDate: asString(raw.performed_at ?? raw.created_at).slice(0, 10),
        movementNumber: asString(raw.movement_number ?? raw.reference ?? raw.source_reference, `MOV-${asString(raw.id)}`),
        movementType: normalizeMovementType(raw.movement_type),
        quantity: asNumberString(raw.quantity),
        quantityEffect: asString(raw.direction, 'IN'),
        sourceModule: asString(raw.source_module, 'inventory'),
        sourceReference: asString(raw.source_reference ?? raw.source_id, 'Source'),
        status: asStatus(raw.status, 'posted'),
        uom: asString(meta.uom_code ?? raw.transaction_uom_id, 'UOM'),
        warehouse: asString(meta.warehouse_name ?? raw.warehouse_id, 'Warehouse'),
    };
}

function normalizeReservation(raw: BackendRecord): StockReservation {
    const meta = record(raw.metadata);
    return {
        availableDecision: asString(meta.available_decision, 'reserved'),
        expiresAt: asString(raw.expires_at),
        id: asString(raw.id),
        itemName: asString(meta.item_name ?? raw.item_id, 'Item'),
        quantity: asNumberString(raw.quantity),
        reservedFor: asString(raw.reserved_for_type ?? meta.reserved_for, 'Generic source'),
        sourceModule: asString(meta.source_module ?? raw.reserved_for_type, 'inventory'),
        sourceReference: asString(meta.source_reference ?? raw.reserved_for_id, 'Reservation'),
        status: asStatus(raw.status, 'active'),
        uom: asString(meta.uom_code ?? raw.transaction_uom_id, 'UOM'),
        warehouse: asString(meta.warehouse_name ?? raw.warehouse_id, 'Warehouse'),
    };
}

function normalizeTransferLine(raw: BackendRecord): StockTransferLine {
    const meta = record(raw.metadata);
    return {
        batchOrSerial: asString(meta.batch_or_serial ?? raw.batch_id ?? raw.serial_id, 'None'),
        id: asString(raw.id),
        itemName: asString(meta.item_name ?? raw.item_id, 'Item'),
        requestedQuantity: asNumberString(raw.quantity),
        uom: asString(meta.uom_code ?? raw.uom_id, 'UOM'),
    };
}

function normalizeTransfer(raw: BackendRecord, lines: StockTransferLine[] = []): StockTransfer {
    const meta = record(raw.metadata);
    return {
        destinationLocation: asString(meta.to_location_name ?? raw.to_location_id, 'Destination'),
        destinationWarehouse: asString(meta.to_warehouse_name ?? raw.to_warehouse_id, 'Warehouse'),
        id: asString(raw.id),
        lines,
        reason: asString(raw.notes, 'Transfer'),
        sourceLocation: asString(meta.from_location_name ?? raw.from_location_id, 'Source'),
        sourceWarehouse: asString(meta.from_warehouse_name ?? raw.from_warehouse_id, 'Warehouse'),
        status: asStatus(raw.status, 'draft'),
        transferDate: asString(raw.transferred_at ?? raw.created_at).slice(0, 10),
        transferNumber: asString(raw.reference_number, `TRF-${asString(raw.id)}`),
    };
}

function normalizeAdjustmentLine(raw: BackendRecord): StockAdjustmentLine {
    const direction = asString(raw.direction, 'INCREASE').toUpperCase() === 'DECREASE' ? 'decrease' : 'increase';
    return {
        adjustmentType: direction,
        batchOrSerial: asString(raw.batch_id ?? raw.serial_id, 'None'),
        id: asString(raw.id),
        itemName: asString(record(raw.metadata).item_name ?? raw.item_id, 'Item'),
        quantity: asNumberString(raw.adjustment_quantity),
        quantityImpact: asNumberString(raw.base_adjustment_quantity),
        uom: asString(record(raw.metadata).uom_code ?? raw.transaction_uom_id, 'UOM'),
    };
}

function normalizeAdjustment(raw: BackendRecord, lines: StockAdjustmentLine[] = []): StockAdjustment {
    const meta = record(raw.metadata);
    return {
        adjustmentDate: asString(raw.approved_at ?? raw.counted_at ?? raw.created_at).slice(0, 10),
        adjustmentNumber: asString(raw.reference_number, `ADJ-${asString(raw.id)}`),
        id: asString(raw.id),
        lines,
        location: asString(meta.location_name ?? raw.location_id, 'Default'),
        reason: asString(raw.reason, 'Adjustment'),
        status: asStatus(raw.status, 'draft'),
        warehouse: asString(meta.warehouse_name ?? raw.warehouse_id, 'Warehouse'),
    };
}

function normalizeBatch(raw: BackendRecord): InventoryBatch {
    return {
        availableQuantity: asNumberString(record(raw.metadata).available_quantity),
        batchNumber: asString(raw.batch_number),
        expiryDate: asString(raw.expiry_date),
        id: asString(raw.id),
        itemName: asString(record(raw.metadata).item_name ?? raw.item_id, 'Item'),
        location: asString(record(raw.metadata).location_name, 'Location'),
        sourceReference: asString(record(raw.metadata).source_reference, 'Batch'),
        status: asStatus(raw.status, 'active'),
        warehouse: asString(record(raw.metadata).warehouse_name, 'Warehouse'),
    };
}

function normalizeSerial(raw: BackendRecord): InventorySerial {
    return {
        id: asString(raw.id),
        itemName: asString(record(raw.metadata).item_name ?? raw.item_id, 'Item'),
        location: asString(record(raw.metadata).location_name ?? raw.current_location_id, 'Location'),
        serialNumber: asString(raw.serial_number),
        sourceReference: asString(record(raw.metadata).source_reference, 'Serial'),
        status: asStatus(raw.status, 'available'),
        warehouse: asString(record(raw.metadata).warehouse_name, 'Warehouse'),
    };
}

function normalizeSimple(raw: BackendRecord): InventoryAuditEntry {
    const meta = record(raw.metadata);
    return {
        actor: asString(raw.performed_by ?? meta.actor, 'Backend'),
        description: asString(meta.description ?? raw.action_type, 'Inventory activity'),
        id: asString(raw.id),
        time: asString(raw.performed_at ?? raw.created_at),
        type: asString(raw.action_type ?? raw.type, 'activity'),
    };
}

function normalizeCostLayer(raw: BackendRecord): CostLayer {
    const meta = record(raw.metadata);
    return {
        id: asString(raw.id),
        itemName: asString(meta.item_name ?? raw.item_id, 'Item'),
        layerDate: asString(raw.layer_date),
        quantity: asNumberString(raw.quantity_in),
        remainingQuantity: asNumberString(raw.quantity_remaining),
        sourceReference: asString(meta.source_reference ?? raw.reference_id, 'Cost layer'),
        unitCost: asNumberString(raw.unit_cost),
    };
}

function normalizeValuation(raw: BackendRecord): InventoryValuation {
    const quantity = Number(raw.quantity_remaining ?? raw.quantity_in ?? 0);
    const unitCost = Number(raw.unit_cost ?? 0);
    return {
        id: asString(raw.id),
        itemName: asString(record(raw.metadata).item_name ?? raw.item_id, 'Item'),
        latestCostLayer: asString(raw.layer_date),
        quantity: asNumberString(quantity),
        totalValue: asNumberString(quantity * unitCost),
        unitCost: asNumberString(unitCost),
        updatedAt: asString(raw.updated_at),
        valuationMethod: asString(raw.valuation_method, 'weighted_average'),
        warehouse: asString(record(raw.metadata).warehouse_name ?? raw.warehouse_id, 'Warehouse'),
    };
}

function normalizeLookup(raw: BackendRecord): InventoryLookupOption {
    return {
        id: asString(raw.id),
        label: asString(raw.name ?? raw.display_name ?? raw.sku ?? raw.code ?? raw.id),
        secondary: asString(raw.code ?? raw.sku ?? raw.symbol),
    };
}

function transferPayload(input: StockTransferFormInput): BackendRecord {
    return contextPayload({
        from_location_id: numberOrUndefined(input.fromLocationId),
        from_warehouse_id: Number(input.fromWarehouseId),
        lines: input.lines.map((line) => ({
            item_id: Number(line.itemId),
            quantity: line.quantity,
            to_location_id: numberOrUndefined(line.toLocationId ?? input.toLocationId),
            uom_id: Number(line.uomId),
        })),
        notes: input.notes,
        reference_number: input.referenceNumber || `TRF-${Date.now()}`,
        requested_by: userId(),
        status: input.status ?? 'DRAFT',
        to_location_id: numberOrUndefined(input.toLocationId),
        to_warehouse_id: Number(input.toWarehouseId),
    });
}

function adjustmentPayload(input: StockAdjustmentFormInput): BackendRecord {
    return contextPayload({
        counted_by: userId(),
        lines: input.lines.map((line) => ({
            adjustment_quantity: line.adjustmentQuantity,
            direction: line.direction,
            item_id: Number(line.itemId),
            uom_id: Number(line.uomId),
        })),
        location_id: numberOrUndefined(input.locationId),
        reason: input.reason,
        reference_number: input.referenceNumber || `ADJ-${Date.now()}`,
        status: input.status ?? 'DRAFT',
        type: input.type ?? 'cycle_count',
        warehouse_id: Number(input.warehouseId),
    });
}

export const inventoryApi = {
    cancelAdjustment: (id: string) => httpClient<void>(`/api/inventory/stock-adjustments/${id}`, { method: 'DELETE' }),
    cancelTransfer: (id: string) => httpClient<void>(`/api/inventory/stock-transfers/${id}`, { method: 'DELETE' }),
    completeTransfer: (id: string) => httpClient<ApiResponse<BackendRecord>>(`/api/inventory/stock-transfers/${id}`, { body: contextPayload({ status: 'COMPLETED' }), method: 'PUT' }),
    createAdjustment: async (input: StockAdjustmentFormInput): Promise<ApiResponse<StockAdjustment>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/inventory/stock-adjustments', { body: adjustmentPayload(input), method: 'POST' });
        return { ...response, data: normalizeAdjustment(response.data ?? record(response)) };
    },
    createReservation: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/inventory/stock-reservations', { body: contextPayload(record(input)), method: 'POST' }),
    createTransfer: async (input: StockTransferFormInput): Promise<ApiResponse<StockTransfer>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/inventory/stock-transfers', { body: transferPayload(input), method: 'POST' });
        return { ...response, data: normalizeTransfer(response.data ?? record(response)) };
    },
    getAdjustment: async (id: string): Promise<ApiResponse<StockAdjustment>> => {
        const [header, lines] = await Promise.all([
            httpClient<ApiResponse<BackendRecord> | BackendRecord>(`/api/inventory/stock-adjustments/${id}`),
            inventoryApi.listAdjustmentLines(),
        ]);
        const raw = 'data' in header && header.data ? header.data as BackendRecord : header as BackendRecord;
        return { data: normalizeAdjustment(raw, lines.data.filter((line) => line.id && asString(record(line).stock_adjustment_id) === id)) };
    },
    getCostLayers: async (): Promise<ApiCollectionResponse<CostLayer>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/inventory-cost-layers', { query: contextQuery() }), normalizeCostLayer),
    getInventoryActivity: async (): Promise<ApiCollectionResponse<InventoryAuditEntry>> => inventoryApi.getTraceability(),
    getStockLevel: async (id: string): Promise<ApiResponse<StockLevel>> => {
        const response = await httpClient<ApiResponse<BackendRecord> | BackendRecord>(`/api/inventory/stock-levels/${id}`);
        const raw = 'data' in response && response.data ? response.data as BackendRecord : response as BackendRecord;
        return { data: normalizeStockLevel(raw) };
    },
    getStockMovement: async (id: string): Promise<ApiResponse<StockMovement>> => {
        const response = await httpClient<ApiResponse<BackendRecord> | BackendRecord>(`/api/inventory/stock-movements/${id}`);
        const raw = 'data' in response && response.data ? response.data as BackendRecord : response as BackendRecord;
        return { data: normalizeMovement(raw) };
    },
    getTraceability: async (): Promise<ApiCollectionResponse<InventoryAuditEntry>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/trace-logs', { query: contextQuery() }), normalizeSimple),
    getTransfer: async (id: string): Promise<ApiResponse<StockTransfer>> => {
        const [header, lines] = await Promise.all([
            httpClient<ApiResponse<BackendRecord> | BackendRecord>(`/api/inventory/stock-transfers/${id}`),
            inventoryApi.listTransferLines(),
        ]);
        const raw = 'data' in header && header.data ? header.data as BackendRecord : header as BackendRecord;
        return { data: normalizeTransfer(raw, lines.data.filter((line) => line.id && asString(record(line).stock_transfer_id) === id)) };
    },
    listAdjustmentLines: async (): Promise<ApiCollectionResponse<StockAdjustmentLine[] & StockAdjustmentLine>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-adjustment-lines', { query: contextQuery() });
        return { ...response, data: response.data.map((row) => ({ ...normalizeAdjustmentLine(row), ...row })) as never };
    },
    listAdjustments: async (): Promise<ApiCollectionResponse<StockAdjustment>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-adjustments', { query: contextQuery() }), (row) => normalizeAdjustment(row)),
    listBatches: async (): Promise<ApiCollectionResponse<InventoryBatch>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/batches', { query: contextQuery() }), normalizeBatch),
    listCycleCounts: async (): Promise<ApiCollectionResponse<CycleCount>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/cycle-count-headers', { query: contextQuery() }), (raw) => ({
        countNumber: asString(raw.count_number ?? raw.reference_number, `COUNT-${asString(raw.id)}`),
        countedDate: asString(raw.counted_at),
        id: asString(raw.id),
        lineSummary: asString(raw.line_count, 'Backend lines'),
        scheduledDate: asString(raw.scheduled_at ?? raw.created_at).slice(0, 10),
        status: asStatus(raw.status, 'draft'),
        variance: asString(raw.variance_summary, 'Backend variance'),
        warehouse: asString(record(raw.metadata).warehouse_name ?? raw.warehouse_id, 'Warehouse'),
    })),
    listDashboardMetrics: async (): Promise<ApiCollectionResponse<{ label: string; status: string; value: string }>> => {
        const [levels, movements, reservations, transfers, adjustments, layers] = await Promise.all([
            inventoryApi.listStockLevels(),
            inventoryApi.listStockMovements(),
            inventoryApi.listReservations(),
            inventoryApi.listTransfers(),
            inventoryApi.listAdjustments(),
            inventoryApi.getCostLayers(),
        ]);
        return {
            data: [
                { label: 'Stock levels', status: 'active', value: String(levels.data.length) },
                { label: 'Movements', status: 'posted', value: String(movements.data.length) },
                { label: 'Reservations', status: 'active', value: String(reservations.data.length) },
                { label: 'Transfers', status: 'draft', value: String(transfers.data.length) },
                { label: 'Adjustments', status: 'draft', value: String(adjustments.data.length) },
                { label: 'Cost layers', status: 'active', value: String(layers.data.length) },
            ],
        };
    },
    listItems: async (): Promise<ApiCollectionResponse<InventoryLookupOption>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/items', { query: contextQuery() }), normalizeLookup),
    listLocations: async (): Promise<ApiCollectionResponse<InventoryLookupOption>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/warehouse/warehouse-locations', { query: contextQuery() }), normalizeLookup),
    listPickingTasks: async (): Promise<ApiCollectionResponse<PickingTask>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/picking-tasks', { query: contextQuery() }), (raw) => ({
        id: asString(raw.id),
        itemName: asString(record(raw.metadata).item_name ?? raw.item_id, 'Item'),
        quantity: asNumberString(raw.quantity),
        sourceReference: asString(raw.source_reference ?? raw.reference_id, 'Source'),
        status: asStatus(raw.status, 'draft'),
        warehouse: asString(record(raw.metadata).warehouse_name ?? raw.source_warehouse_id, 'Warehouse'),
    })),
    listPutAwayTasks: async (): Promise<ApiCollectionResponse<PutAwayTask>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/put-away-tasks', { query: contextQuery() }), (raw) => ({
        destinationLocation: asString(raw.target_location_id, 'Location'),
        id: asString(raw.id),
        itemName: asString(record(raw.metadata).item_name ?? raw.item_id, 'Item'),
        quantity: asNumberString(raw.quantity),
        sourceReference: asString(raw.source_reference ?? raw.reference_id, 'Source'),
        status: asStatus(raw.status, 'draft'),
    })),
    listReceiptInspections: async (): Promise<ApiCollectionResponse<ReceiptInspection>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/receipt-inspections', { query: contextQuery() }), (raw) => ({
        id: asString(raw.id),
        inspectionNumber: asString(raw.inspection_number ?? raw.reference_number, `INSP-${asString(raw.id)}`),
        itemName: asString(record(raw.metadata).item_name ?? raw.item_id, 'Item'),
        result: asString(raw.result, 'pending'),
        sourceReference: asString(raw.source_reference ?? raw.reference_id, 'Source'),
        status: asStatus(raw.status, 'draft'),
    })),
    listReservations: async (): Promise<ApiCollectionResponse<StockReservation>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-reservations', { query: contextQuery() }), normalizeReservation),
    listSerials: async (): Promise<ApiCollectionResponse<InventorySerial>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/serials', { query: contextQuery() }), normalizeSerial),
    listStockLevels: async (): Promise<ApiCollectionResponse<StockLevel>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-levels', { query: contextQuery() }), normalizeStockLevel),
    listStockMovements: async (): Promise<ApiCollectionResponse<StockMovement>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-movements', { query: contextQuery() }), normalizeMovement),
    listTransferLines: async (): Promise<ApiCollectionResponse<StockTransferLine[] & StockTransferLine>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-transfer-lines', { query: contextQuery() });
        return { ...response, data: response.data.map((row) => ({ ...normalizeTransferLine(row), ...row })) as never };
    },
    listTransfers: async (): Promise<ApiCollectionResponse<StockTransfer>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-transfers', { query: contextQuery() }), (row) => normalizeTransfer(row)),
    listUoms: async (): Promise<ApiCollectionResponse<InventoryLookupOption>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/units-of-measure', { query: contextQuery() }), normalizeLookup),
    listValuation: async (): Promise<ApiCollectionResponse<InventoryValuation>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/inventory-cost-layers', { query: contextQuery() }), normalizeValuation),
    listWarehouses: async (): Promise<ApiCollectionResponse<InventoryLookupOption>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/warehouse/warehouses', { query: contextQuery() }), normalizeLookup),
    postAdjustment: (id: string) => httpClient<ApiResponse<BackendRecord>>(`/api/inventory/stock-adjustments/${id}`, { body: contextPayload({ approved_at: new Date().toISOString(), status: 'COMPLETED' }), method: 'PUT' }),
    previewStockAvailability: async (input: StockAvailabilityPreviewRequest): Promise<ApiPreviewResponse<StockAvailabilityPreviewRequest, StockAvailabilityPreviewResult['calculated']>> => {
        const response = await httpClient<ApiPreviewResponse<StockAvailabilityPreviewRequest, StockAvailabilityPreviewResult['calculated']>>('/api/inventory/engines/stock-availability/preview', {
            body: contextPayload({
                item_id: Number(input.itemId),
                location_id: numberOrUndefined(input.location),
                quantity: input.quantity,
                uom_id: numberOrUndefined(input.uom),
                warehouse_id: numberOrUndefined(input.warehouse),
            }),
            method: 'POST',
        });

        return response;
    },
    releaseReservation: (id: string) => httpClient<void>(`/api/inventory/stock-reservations/${id}`, { method: 'DELETE' }),
    submitTransfer: (id: string) => httpClient<ApiResponse<BackendRecord>>(`/api/inventory/stock-transfers/${id}`, { body: contextPayload({ status: 'PENDING' }), method: 'PUT' }),
    updateAdjustment: async (id: string, input: StockAdjustmentFormInput): Promise<ApiResponse<StockAdjustment>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/inventory/stock-adjustments/${id}`, { body: adjustmentPayload(input), method: 'PUT' });
        return { ...response, data: normalizeAdjustment(response.data ?? record(response)) };
    },
    updateTransfer: async (id: string, input: StockTransferFormInput): Promise<ApiResponse<StockTransfer>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/inventory/stock-transfers/${id}`, { body: transferPayload(input), method: 'PUT' });
        return { ...response, data: normalizeTransfer(response.data ?? record(response)) };
    },
};
