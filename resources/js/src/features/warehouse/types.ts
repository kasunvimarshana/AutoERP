export type WarehouseRecord = {
    id: number;
    tenant_id: number;
    org_unit_id: number | null;
    name: string;
    code: string | null;
    image_path: string | null;
    type: 'standard' | 'virtual' | 'transit' | 'quarantine';
    address_id: number | null;
    is_active: boolean;
    is_default: boolean;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type WarehouseLocationRecord = {
    id: number;
    tenant_id: number;
    warehouse_id: number;
    parent_id: number | null;
    name: string;
    code: string | null;
    path: string | null;
    depth: number;
    type: 'zone' | 'aisle' | 'rack' | 'shelf' | 'bin' | 'staging' | 'dispatch';
    is_active: boolean;
    is_pickable: boolean;
    is_receivable: boolean;
    capacity: string | number | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type WarehouseStockLevelRecord = {
    id: number;
    tenant_id: number;
    product_id: number;
    variant_id: number | null;
    location_id: number;
    batch_id: number | null;
    serial_id: number | null;
    uom_id: number;
    quantity_on_hand: string | number;
    quantity_reserved: string | number;
    unit_cost: string | number | null;
    last_movement_at: string | null;
    created_at?: string;
    updated_at?: string;
};

export type WarehouseStockMovementRecord = {
    id: number;
    tenant_id: number;
    product_id: number;
    variant_id: number | null;
    batch_id: number | null;
    serial_id: number | null;
    from_location_id: number | null;
    to_location_id: number | null;
    movement_type: string;
    reference_type: string | null;
    reference_id: number | null;
    uom_id: number;
    quantity: string | number;
    unit_cost: string | number | null;
    performed_by: number | null;
    performed_at: string | null;
    notes: string | null;
    metadata: Record<string, unknown> | null;
};

export type WarehouseListFilters = {
    tenant_id: number;
    org_unit_id?: number;
    name?: string;
    code?: string;
    type?: string;
    is_active?: boolean;
    is_default?: boolean;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type WarehouseLocationListFilters = {
    tenant_id: number;
    parent_id?: number;
    name?: string;
    code?: string;
    type?: string;
    is_active?: boolean;
    is_pickable?: boolean;
    is_receivable?: boolean;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type WarehouseStockMovementFilters = {
    tenant_id: number;
    product_id?: number;
    movement_type?: string;
    from_location_id?: number;
    to_location_id?: number;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type WarehousePayload = {
    tenant_id: number;
    org_unit_id?: number | null;
    name: string;
    code?: string | null;
    image_path?: string | null;
    type?: WarehouseRecord['type'];
    address_id?: number | null;
    is_active?: boolean;
    is_default?: boolean;
    metadata?: Record<string, unknown> | null;
};

export type WarehouseLocationPayload = {
    tenant_id: number;
    parent_id?: number | null;
    name: string;
    code?: string | null;
    type?: WarehouseLocationRecord['type'];
    is_active?: boolean;
    is_pickable?: boolean;
    is_receivable?: boolean;
    capacity?: number | null;
    metadata?: Record<string, unknown> | null;
};
