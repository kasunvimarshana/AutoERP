import type { NamedResource } from '@/shared/types/common';
import type { ApiCollection } from '@/shared/types/api';

export const warehouseTypes = ['standard', 'virtual', 'transit', 'quarantine'] as const;
export const warehouseLocationTypes = ['zone', 'aisle', 'rack', 'shelf', 'bin', 'staging', 'dispatch'] as const;

export type WarehouseType = typeof warehouseTypes[number];
export type WarehouseLocationType = typeof warehouseLocationTypes[number];

export interface WarehouseSummary extends NamedResource {
    row_version?: number;
    type: WarehouseType;
    type_label?: string;
    organization_unit?: NamedResource | null;
    is_default: boolean;
    is_active: boolean;
    locations_count?: number;
    default_location?: WarehouseLocationSummary | null;
    metadata?: Record<string, unknown> | null;
}

export interface Warehouse extends WarehouseSummary {
    locations?: WarehouseLocationSummary[];
}

export interface WarehouseLocationSummary extends NamedResource {
    row_version?: number;
    warehouse?: WarehouseSummary | null;
    parent?: NamedResource | null;
    organization_unit?: NamedResource | null;
    path?: string | null;
    depth: number;
    type: WarehouseLocationType;
    type_label?: string;
    capacity?: string | null;
    is_default: boolean;
    is_pickable: boolean;
    is_receivable: boolean;
    is_active: boolean;
    metadata?: Record<string, unknown> | null;
}

export type WarehouseLocation = WarehouseLocationSummary;

export interface WarehousePayload {
    name: string;
    code?: string | null;
    type: WarehouseType;
    is_active: boolean;
    is_default: boolean;
    row_version?: number | null;
}

export interface WarehouseLocationPayload {
    warehouse_id: number | null;
    parent_id?: number | null;
    name: string;
    code?: string | null;
    type: WarehouseLocationType;
    capacity?: string | null;
    is_pickable: boolean;
    is_receivable: boolean;
    is_active: boolean;
    is_default: boolean;
    row_version?: number | null;
}

export type WarehouseListResponse<T> = ApiCollection<T>;
