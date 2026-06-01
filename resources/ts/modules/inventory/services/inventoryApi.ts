import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { getStoredAuthSession, getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
import { httpClient } from '../../../services/api/httpClient';
import type {
    CostLayer,
    CycleCount,
    InventoryAuditEntry,
    InventoryBatch,
    InventoryListQuery,
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

function nestedLabel(raw: BackendRecord, key: string, codeKeys: string[], nameKeys: string[], fallback = ''): string {
    const direct = asString(raw[`${key}_label`]);
    if (direct) {
        return direct;
    }

    const nested = record(raw[key]);
    const code = codeKeys.map((codeKey) => asString(nested[codeKey])).find(Boolean) ?? '';
    const name = nameKeys.map((nameKey) => asString(nested[nameKey])).find(Boolean) ?? '';

    if (code && name) {
        return `${code} - ${name}`;
    }

    return name || code || fallback;
}

function uomLabel(raw: BackendRecord, key: 'base_uom' | 'transaction_uom' | 'uom', fallback = 'UOM'): string {
    const direct = asString(raw[`${key}_label`]);
    if (direct) {
        return direct;
    }

    const nested = record(raw[key]);

    return asString(nested.symbol ?? nested.code ?? nested.name, fallback);
}

function batchSerialLabel(raw: BackendRecord, fallback = 'None'): string {
    const direct = asString(raw.batch_serial_label);
    if (direct) {
        return direct;
    }

    const batch = record(raw.batch);
    const serial = record(raw.serial);

    return asString(batch.batch_number ?? batch.lot_number ?? serial.serial_number, fallback);
}

function numberOrUndefined(value: string | null | undefined): number | undefined {
    const parsed = Number(value);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : undefined;
}

function contextQuery(extra: Record<string, string | number | boolean | null | undefined> = {}) {
    return {
        organization_unit_id: numberOrUndefined(getStoredOrganizationUnitId()),
        per_page: 25,
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

function collectionTotal<T>(response: ApiCollectionResponse<T>): number {
    return response.meta?.total ?? response.data.length;
}

function normalizeStockLevel(raw: BackendRecord): StockLevel {
    const onHand = Number(raw.quantity_on_hand ?? 0);
    const reserved = Number(raw.quantity_reserved ?? 0);
    const blocked = Number(raw.quantity_blocked ?? 0);
    const damaged = Number(raw.quantity_damaged ?? 0);
    const available = Math.max(0, onHand - reserved - blocked - damaged);

    return {
        available: asNumberString(available),
        batchOrSerial: batchSerialLabel(raw),
        id: asString(raw.id),
        itemCode: asString(record(raw.item).sku ?? record(raw.item).code),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        location: nestedLabel(raw, 'location', ['code'], ['name'], 'Default'),
        onHand: asNumberString(raw.quantity_on_hand),
        reserved: asNumberString(raw.quantity_reserved),
        status: asStatus(raw.condition, 'good'),
        uom: uomLabel(raw, 'base_uom'),
        updatedAt: asString(raw.updated_at),
        warehouse: nestedLabel(raw, 'warehouse', ['code'], ['name'], 'Warehouse'),
    };
}

function normalizeMovementType(value: unknown): StockMovementType {
    const normalized = asString(value, 'adjustment_in').toLowerCase().replace('stock_', '').replace('opening_balance', 'receipt');
    const allowed: StockMovementType[] = ['receipt', 'issue', 'consumption', 'transfer_in', 'transfer_out', 'adjustment_in', 'adjustment_out', 'return_in', 'return_out'];
    return allowed.includes(normalized as StockMovementType) ? normalized as StockMovementType : 'adjustment_in';
}

function normalizeMovement(raw: BackendRecord): StockMovement {
    return {
        batchOrSerial: batchSerialLabel(raw),
        costEffect: asNumberString(raw.total_cost ?? record(raw.metadata).cost_effect),
        id: asString(raw.id),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        location: nestedLabel(raw, 'location', ['code'], ['name'], 'Default'),
        movementDate: asString(raw.performed_at ?? raw.created_at).slice(0, 10),
        movementNumber: asString(raw.movement_number ?? raw.reference ?? raw.source_reference, 'Movement'),
        movementType: normalizeMovementType(raw.movement_type),
        quantity: asNumberString(raw.quantity),
        quantityEffect: asString(raw.direction, 'IN'),
        sourceModule: asString(raw.source_module, 'inventory'),
        sourceReference: asString(raw.source_reference, 'Not linked'),
        status: asStatus(raw.status, 'posted'),
        uom: uomLabel(raw, 'transaction_uom'),
        warehouse: nestedLabel(raw, 'warehouse', ['code'], ['name'], 'Warehouse'),
    };
}

function normalizeReservation(raw: BackendRecord): StockReservation {
    const meta = record(raw.metadata);
    return {
        availableDecision: asString(meta.available_decision, 'reserved'),
        expiresAt: asString(raw.expires_at),
        id: asString(raw.id),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        quantity: asNumberString(raw.quantity),
        reservedFor: asString(raw.reserved_for_type ?? meta.reserved_for, 'Generic source'),
        sourceModule: asString(meta.source_module ?? raw.reserved_for_type, 'inventory'),
        sourceReference: asString(meta.source_reference, 'Reservation'),
        status: asStatus(raw.status, 'active'),
        uom: uomLabel(raw, 'transaction_uom'),
        warehouse: nestedLabel(raw, 'warehouse', ['code'], ['name'], 'Warehouse'),
    };
}

function normalizeTransferLine(raw: BackendRecord): StockTransferLine {
    return {
        batchOrSerial: batchSerialLabel(raw),
        id: asString(raw.id),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        requestedQuantity: asNumberString(raw.quantity),
        uom: uomLabel(raw, 'uom'),
    };
}

function normalizeTransfer(raw: BackendRecord, lines: StockTransferLine[] = []): StockTransfer {
    return {
        destinationLocation: nestedLabel(raw, 'to_location', ['code'], ['name'], 'Destination'),
        destinationWarehouse: nestedLabel(raw, 'to_warehouse', ['code'], ['name'], 'Warehouse'),
        id: asString(raw.id),
        lines,
        reason: asString(raw.notes, 'Transfer'),
        sourceLocation: nestedLabel(raw, 'from_location', ['code'], ['name'], 'Source'),
        sourceWarehouse: nestedLabel(raw, 'from_warehouse', ['code'], ['name'], 'Warehouse'),
        status: asStatus(raw.status, 'draft'),
        transferDate: asString(raw.transferred_at ?? raw.created_at).slice(0, 10),
        transferNumber: asString(raw.reference_number, 'Transfer'),
    };
}

function normalizeAdjustmentLine(raw: BackendRecord): StockAdjustmentLine {
    const direction = asString(raw.direction, 'INCREASE').toUpperCase() === 'DECREASE' ? 'decrease' : 'increase';
    return {
        adjustmentType: direction,
        batchOrSerial: batchSerialLabel(raw),
        id: asString(raw.id),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        quantity: asNumberString(raw.adjustment_quantity),
        quantityImpact: asNumberString(raw.base_adjustment_quantity),
        uom: uomLabel(raw, 'transaction_uom'),
    };
}

function normalizeAdjustment(raw: BackendRecord, lines: StockAdjustmentLine[] = []): StockAdjustment {
    return {
        adjustmentDate: asString(raw.approved_at ?? raw.counted_at ?? raw.created_at).slice(0, 10),
        adjustmentNumber: asString(raw.reference_number, 'Adjustment'),
        id: asString(raw.id),
        lines,
        location: nestedLabel(raw, 'location', ['code'], ['name'], 'Default'),
        reason: asString(raw.reason, 'Adjustment'),
        status: asStatus(raw.status, 'draft'),
        warehouse: nestedLabel(raw, 'warehouse', ['code'], ['name'], 'Warehouse'),
    };
}

function normalizeBatch(raw: BackendRecord): InventoryBatch {
    return {
        availableQuantity: asNumberString(record(raw.metadata).available_quantity),
        batchNumber: asString(raw.batch_number),
        expiryDate: asString(raw.expiry_date),
        id: asString(raw.id),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        location: nestedLabel(raw, 'location', ['code'], ['name'], 'Location'),
        sourceReference: asString(record(raw.metadata).source_reference, 'Batch'),
        status: asStatus(raw.status, 'active'),
        warehouse: nestedLabel(raw, 'warehouse', ['code'], ['name'], 'Warehouse'),
    };
}

function normalizeSerial(raw: BackendRecord): InventorySerial {
    return {
        id: asString(raw.id),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        location: nestedLabel(raw, 'current_location', ['code'], ['name'], 'Location'),
        serialNumber: asString(raw.serial_number),
        sourceReference: asString(record(raw.metadata).source_reference, 'Serial'),
        status: asStatus(raw.status, 'available'),
        warehouse: nestedLabel(raw, 'warehouse', ['code'], ['name'], 'Warehouse'),
    };
}

function normalizeSimple(raw: BackendRecord): InventoryAuditEntry {
    const meta = record(raw.metadata);
    return {
        actor: asString(
            raw.performed_by_user_label
                ?? raw.approved_by_user_label
                ?? raw.requested_by_user_label
                ?? raw.reserved_by_user_label
                ?? raw.released_by_user_label
                ?? raw.counted_by_user_label
                ?? raw.inspected_by_user_label
                ?? meta.actor,
            'System',
        ),
        description: asString(meta.description ?? raw.action_type, 'Inventory activity'),
        id: asString(raw.id),
        time: asString(raw.performed_at ?? raw.created_at),
        type: asString(raw.action_type ?? raw.type, 'activity'),
    };
}

function normalizeCostLayer(raw: BackendRecord): CostLayer {
    return {
        id: asString(raw.id),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        layerDate: asString(raw.layer_date),
        quantity: asNumberString(raw.quantity_in),
        remainingQuantity: asNumberString(raw.quantity_remaining),
        sourceReference: asString(record(raw.metadata).source_reference, 'Cost layer'),
        unitCost: asNumberString(raw.unit_cost),
    };
}

function normalizeValuation(raw: BackendRecord): InventoryValuation {
    const quantity = Number(raw.quantity_remaining ?? raw.quantity_in ?? 0);
    const unitCost = Number(raw.unit_cost ?? 0);
    return {
        id: asString(raw.id),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        latestCostLayer: asString(raw.layer_date),
        quantity: asNumberString(quantity),
        totalValue: asNumberString(quantity * unitCost),
        unitCost: asNumberString(unitCost),
        updatedAt: asString(raw.updated_at),
        valuationMethod: asString(raw.valuation_method, 'weighted_average'),
        warehouse: nestedLabel(raw, 'warehouse', ['code'], ['name'], 'Warehouse'),
    };
}

function normalizeLookup(raw: BackendRecord): InventoryLookupOption {
    return {
        id: asString(raw.id),
        label: asString(raw.name ?? raw.display_name ?? raw.sku ?? raw.code, 'Unnamed option'),
        secondary: asString(raw.code ?? raw.sku ?? raw.symbol),
    };
}

function transferPayload(input: StockTransferFormInput): BackendRecord {
    return contextPayload({
        from_location_id: numberOrUndefined(input.fromLocationId),
        from_warehouse_id: Number(input.fromWarehouseId),
        lines: input.lines.map((line) => ({
            batch_id: numberOrUndefined(line.batchId),
            item_id: Number(line.itemId),
            quantity: line.quantity,
            serial_id: numberOrUndefined(line.serialId),
            to_location_id: numberOrUndefined(line.toLocationId ?? input.toLocationId),
            uom_id: Number(line.uomId),
        })),
        notes: input.notes,
        reference_number: input.referenceNumber || `TRF-${Date.now()}`,
        requested_by: userId(),
        status: input.status ?? 'DRAFT',
        transferred_at: input.transferDate || undefined,
        to_location_id: numberOrUndefined(input.toLocationId),
        to_warehouse_id: Number(input.toWarehouseId),
    });
}

function adjustmentPayload(input: StockAdjustmentFormInput): BackendRecord {
    return contextPayload({
        counted_by: userId(),
        counted_at: input.adjustmentDate || undefined,
        lines: input.lines.map((line) => ({
            adjustment_quantity: line.adjustmentQuantity,
            batch_id: numberOrUndefined(line.batchId),
            direction: line.direction,
            item_id: Number(line.itemId),
            serial_id: numberOrUndefined(line.serialId),
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
    getCostLayers: async (query: InventoryListQuery = {}): Promise<ApiCollectionResponse<CostLayer>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/inventory-cost-layers', { query: contextQuery(query) }), normalizeCostLayer),
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
    listAdjustments: async (query: InventoryListQuery = {}): Promise<ApiCollectionResponse<StockAdjustment>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-adjustments', { query: contextQuery(query) }), (row) => normalizeAdjustment(row)),
    listBatches: async (): Promise<ApiCollectionResponse<InventoryBatch>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/batches', { query: contextQuery() }), normalizeBatch),
    listCycleCounts: async (): Promise<ApiCollectionResponse<CycleCount>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/cycle-count-headers', { query: contextQuery() }), (raw) => ({
        countNumber: asString(raw.count_number ?? raw.reference_number, 'Cycle count'),
        countedDate: asString(raw.counted_at),
        id: asString(raw.id),
        lineSummary: asString(raw.line_count, 'No line summary'),
        scheduledDate: asString(raw.scheduled_at ?? raw.created_at).slice(0, 10),
        status: asStatus(raw.status, 'draft'),
        variance: asString(raw.variance_summary, 'No variance recorded'),
        warehouse: nestedLabel(raw, 'warehouse', ['code'], ['name'], 'Warehouse'),
    })),
    listDashboardMetrics: async (): Promise<ApiCollectionResponse<{ label: string; status: string; value: string }>> => {
        const [levels, movements, reservations, transfers, adjustments, layers] = await Promise.all([
            inventoryApi.listStockLevels({ per_page: 1 }),
            inventoryApi.listStockMovements({ per_page: 1 }),
            inventoryApi.listReservations({ per_page: 1 }),
            inventoryApi.listTransfers({ per_page: 1 }),
            inventoryApi.listAdjustments({ per_page: 1 }),
            inventoryApi.getCostLayers({ per_page: 1 }),
        ]);
        return {
            data: [
                { label: 'Stock levels', status: 'active', value: String(collectionTotal(levels)) },
                { label: 'Movements', status: 'posted', value: String(collectionTotal(movements)) },
                { label: 'Reservations', status: 'active', value: String(collectionTotal(reservations)) },
                { label: 'Transfers', status: 'draft', value: String(collectionTotal(transfers)) },
                { label: 'Adjustments', status: 'draft', value: String(collectionTotal(adjustments)) },
                { label: 'Cost layers', status: 'active', value: String(collectionTotal(layers)) },
            ],
        };
    },
    listItems: async (): Promise<ApiCollectionResponse<InventoryLookupOption>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/items', { query: contextQuery() }), normalizeLookup),
    listLocations: async (): Promise<ApiCollectionResponse<InventoryLookupOption>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/warehouse/warehouse-locations', { query: contextQuery() }), normalizeLookup),
    listPickingTasks: async (): Promise<ApiCollectionResponse<PickingTask>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/picking-tasks', { query: contextQuery() }), (raw) => ({
        id: asString(raw.id),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        quantity: asNumberString(raw.quantity),
        sourceReference: asString(raw.source_reference, 'Source'),
        status: asStatus(raw.status, 'draft'),
        warehouse: nestedLabel(raw, 'source_warehouse', ['code'], ['name'], 'Warehouse'),
    })),
    listPutAwayTasks: async (): Promise<ApiCollectionResponse<PutAwayTask>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/put-away-tasks', { query: contextQuery() }), (raw) => ({
        destinationLocation: nestedLabel(raw, 'target_location', ['code'], ['name'], 'Location'),
        id: asString(raw.id),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        quantity: asNumberString(raw.quantity),
        sourceReference: asString(raw.source_reference, 'Source'),
        status: asStatus(raw.status, 'draft'),
    })),
    listReceiptInspections: async (): Promise<ApiCollectionResponse<ReceiptInspection>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/receipt-inspections', { query: contextQuery() }), (raw) => ({
        id: asString(raw.id),
        inspectionNumber: asString(raw.inspection_number ?? raw.reference_number, 'Inspection'),
        itemName: nestedLabel(raw, 'item', ['sku', 'code'], ['name'], 'Item'),
        result: asString(raw.result, 'pending'),
        sourceReference: asString(raw.source_reference, 'Source'),
        status: asStatus(raw.status, 'draft'),
    })),
    listReservations: async (query: InventoryListQuery = {}): Promise<ApiCollectionResponse<StockReservation>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-reservations', { query: contextQuery(query) }), normalizeReservation),
    listSerials: async (): Promise<ApiCollectionResponse<InventorySerial>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/serials', { query: contextQuery() }), normalizeSerial),
    listStockLevels: async (query: InventoryListQuery = {}): Promise<ApiCollectionResponse<StockLevel>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-levels', { query: contextQuery(query) }), normalizeStockLevel),
    listStockMovements: async (query: InventoryListQuery = {}): Promise<ApiCollectionResponse<StockMovement>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-movements', { query: contextQuery(query) }), normalizeMovement),
    listTransferLines: async (): Promise<ApiCollectionResponse<StockTransferLine[] & StockTransferLine>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-transfer-lines', { query: contextQuery() });
        return { ...response, data: response.data.map((row) => ({ ...normalizeTransferLine(row), ...row })) as never };
    },
    listTransfers: async (query: InventoryListQuery = {}): Promise<ApiCollectionResponse<StockTransfer>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/stock-transfers', { query: contextQuery(query) }), (row) => normalizeTransfer(row)),
    listUoms: async (): Promise<ApiCollectionResponse<InventoryLookupOption>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/units-of-measure', { query: contextQuery() }), normalizeLookup),
    listValuation: async (query: InventoryListQuery = {}): Promise<ApiCollectionResponse<InventoryValuation>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/inventory/inventory-cost-layers', { query: contextQuery(query) }), normalizeValuation),
    listWarehouses: async (): Promise<ApiCollectionResponse<InventoryLookupOption>> => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/warehouse/warehouses', { query: contextQuery() }), normalizeLookup),
    postAdjustment: (id: string) => httpClient<ApiResponse<BackendRecord>>(`/api/inventory/stock-adjustments/${id}`, { body: contextPayload({ approved_at: new Date().toISOString(), status: 'COMPLETED' }), method: 'PUT' }),
    previewStockAvailability: async (input: StockAvailabilityPreviewRequest): Promise<ApiPreviewResponse<StockAvailabilityPreviewRequest, StockAvailabilityPreviewResult['calculated']>> => {
        const response = await httpClient<ApiPreviewResponse<StockAvailabilityPreviewRequest, StockAvailabilityPreviewResult['calculated']>>('/api/inventory/engines/stock-availability/preview', {
            body: contextPayload({
                item_id: Number(input.itemId),
                batch_id: numberOrUndefined(input.batchId),
                location_id: numberOrUndefined(input.location),
                quantity: input.quantity,
                serial_id: numberOrUndefined(input.serialId),
                source_id: numberOrUndefined(input.sourceId),
                source_module: input.sourceModule || undefined,
                source_reference: input.sourceReference || undefined,
                source_type: input.sourceType || undefined,
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
