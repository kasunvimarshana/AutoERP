export interface InventoryRelation extends Record<string, unknown> {
    id: number;
    name?: string;
    code?: string;
}

export interface StockBalance extends Record<string, unknown> {
    id: number;
    item?: InventoryRelation;
    warehouse?: InventoryRelation;
    warehouse_location?: InventoryRelation;
    batch?: InventoryRelation & { batch_number?: string; lot_number?: string };
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

export interface InventoryAvailability extends Record<string, unknown> {
    itemId: number;
    warehouseId: number;
    quantityOnHand: string;
    quantityReserved: string;
    quantityAllocated: string;
    quantityAvailable: string;
    quantityInTransit: string;
    quantityReturned: string;
    quantityDamaged: string;
    quantityQuarantine: string;
    quantityExpired: string;
    quantityScrapped: string;
    quantityTotal: string;
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
    lines: Array<{
        item_id: number;
        quantity: string;
        unit_cost?: string;
        item_variant_id?: number;
        batch_id?: number;
        serial_number_id?: number;
        uom_id?: number;
    }>;
}

export interface StockCountPayload {
    count_date: string;
    count_type?: 'stock_count' | 'cycle_count';
    warehouse_id: number;
    warehouse_location_id?: number;
    reason?: string;
    notes?: string;
    lines: Array<{
        item_id: number;
        counted_quantity: string;
        system_quantity?: string;
        unit_cost?: string;
        item_variant_id?: number;
        batch_id?: number;
        serial_number_id?: number;
        uom_id?: number;
        notes?: string;
    }>;
}

export interface CostAdjustmentPayload {
    adjustment_date: string;
    reason?: string;
    notes?: string;
    lines: Array<{ valuation_layer_id: number; adjustment_amount: string; reason?: string }>;
}
