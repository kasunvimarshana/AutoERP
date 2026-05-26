import type { WarehouseStockLevelRecord, WarehouseStockMovementRecord } from '../warehouse/types';

export type InventoryStockLevelRecord = WarehouseStockLevelRecord;
export type InventoryStockMovementRecord = WarehouseStockMovementRecord;

export type TransferOrderLineRecord = {
    id: number;
    tenant_id: number;
    product_id: number;
    variant_id: number | null;
    batch_id: number | null;
    serial_id: number | null;
    from_location_id: number | null;
    to_location_id: number | null;
    uom_id: number;
    requested_qty: string | number;
    shipped_qty: string | number;
    received_qty: string | number;
    unit_cost: string | number | null;
};

export type TransferOrderRecord = {
    id: number;
    tenant_id: number;
    org_unit_id: number | null;
    from_warehouse_id: number;
    to_warehouse_id: number;
    transfer_number: string;
    status: 'draft' | 'approved' | 'in_transit' | 'received' | 'cancelled';
    request_date: string;
    expected_date: string | null;
    shipped_date: string | null;
    received_date: string | null;
    notes: string | null;
    metadata: Record<string, unknown> | null;
    lines: TransferOrderLineRecord[];
};

export type CycleCountLineRecord = {
    id: number;
    tenant_id: number;
    product_id: number;
    variant_id: number | null;
    batch_id: number | null;
    serial_id: number | null;
    system_qty: string | number;
    counted_qty: string | number | null;
    variance_qty: string | number | null;
    unit_cost: string | number | null;
    variance_value: string | number | null;
    adjustment_movement_id: number | null;
};

export type CycleCountRecord = {
    id: number;
    tenant_id: number;
    warehouse_id: number;
    location_id: number | null;
    status: 'draft' | 'in_progress' | 'completed' | 'cancelled';
    counted_by_user_id: number | null;
    counted_at: string | null;
    approved_by_user_id: number | null;
    approved_at: string | null;
    lines: CycleCountLineRecord[];
};

export type StockReservationRecord = {
    id: number;
    tenant_id: number;
    product_id: number;
    variant_id: number | null;
    batch_id: number | null;
    serial_id: number | null;
    location_id: number;
    quantity: string | number;
    reserved_for_type: string | null;
    reserved_for_id: number | null;
    expires_at: string | null;
};

export type ValuationConfigRecord = {
    id: number;
    tenant_id: number;
    org_unit_id: number | null;
    warehouse_id: number | null;
    product_id: number | null;
    transaction_type: string | null;
    valuation_method: 'fifo' | 'lifo' | 'fefo' | 'weighted_average' | 'standard' | 'specific';
    allocation_strategy: 'fifo' | 'lifo' | 'fefo' | 'nearest_bin' | 'manual';
    is_active: boolean;
    metadata: Record<string, unknown> | null;
};

export type InventoryWarehouseFilters = {
    tenant_id: number;
    product_id?: number;
    movement_type?: string;
    from_location_id?: number;
    to_location_id?: number;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type TransferOrderListFilters = {
    tenant_id: number;
    per_page?: number;
    page?: number;
};

export type CycleCountListFilters = {
    tenant_id: number;
    per_page?: number;
    page?: number;
};

export type StockReservationListFilters = {
    tenant_id: number;
    per_page?: number;
    page?: number;
};

export type ValuationConfigListFilters = {
    tenant_id: number;
    per_page?: number;
    page?: number;
};

export type TransferOrderPayload = {
    tenant_id: number;
    org_unit_id?: number | null;
    from_warehouse_id: number;
    to_warehouse_id: number;
    transfer_number: string;
    status?: TransferOrderRecord['status'];
    request_date: string;
    expected_date?: string | null;
    notes?: string | null;
    metadata?: Record<string, unknown> | null;
    lines: Array<{
        product_id: number;
        variant_id?: number | null;
        batch_id?: number | null;
        serial_id?: number | null;
        from_location_id?: number | null;
        to_location_id?: number | null;
        uom_id: number;
        requested_qty: number;
        unit_cost?: number | null;
    }>;
};

export type TransferOrderReceivePayload = {
    tenant_id: number;
    lines: Array<{
        line_id: number;
        received_qty: number;
    }>;
};

export type CycleCountPayload = {
    tenant_id: number;
    warehouse_id: number;
    location_id?: number | null;
    counted_by_user_id?: number | null;
    lines: Array<{
        product_id: number;
        variant_id?: number | null;
        batch_id?: number | null;
        serial_id?: number | null;
        counted_qty?: number | null;
        unit_cost?: number | null;
    }>;
};

export type CycleCountCompletePayload = {
    tenant_id: number;
    approved_by_user_id: number;
    lines: Array<{
        line_id: number;
        counted_qty: number;
    }>;
};

export type StockReservationPayload = {
    tenant_id: number;
    product_id: number;
    variant_id?: number | null;
    batch_id?: number | null;
    serial_id?: number | null;
    location_id: number;
    quantity: number;
    reserved_for_type?: string | null;
    reserved_for_id?: number | null;
    expires_at?: string | null;
};

export type ReleaseExpiredPayload = {
    tenant_id: number;
    expires_before?: string | null;
};

export type ValuationConfigPayload = {
    tenant_id: number;
    org_unit_id?: number | null;
    warehouse_id?: number | null;
    product_id?: number | null;
    transaction_type?: string | null;
    valuation_method: ValuationConfigRecord['valuation_method'];
    allocation_strategy: ValuationConfigRecord['allocation_strategy'];
    is_active?: boolean;
    metadata?: Record<string, unknown> | null;
};
