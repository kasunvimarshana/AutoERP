import type { PaginationMeta } from '@/shared/types/pagination';

export const uomTypes = ['unit', 'weight', 'volume', 'length', 'area', 'time', 'service', 'other'] as const;
export const uomCategories = ['quantity', 'weight', 'volume', 'length', 'area', 'time', 'service', 'other'] as const;

export type UomType = typeof uomTypes[number];
export type UomCategory = typeof uomCategories[number];

export interface UomSummary {
    id: number;
    code: string;
    name: string;
    symbol?: string | null;
}

export interface UnitOfMeasure extends UomSummary {
    type: UomType;
    category: UomCategory;
    decimal_precision: number;
    allow_fractional_quantity: boolean;
    is_base: boolean;
    is_active: boolean;
    description?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
}

export interface UomPayload {
    code: string;
    name: string;
    symbol: string;
    type: UomType;
    category: UomCategory;
    decimal_precision: number;
    allow_fractional_quantity: boolean;
    is_base: boolean;
    is_active: boolean;
    description?: string | null;
}

export interface UomConversion {
    id: number;
    from_uom: UomSummary | null;
    to_uom: UomSummary | null;
    conversion_factor: string;
    is_active: boolean;
    description?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
}

export interface UomConversionPayload {
    from_uom_id: number;
    to_uom_id: number;
    conversion_factor: string;
    is_active: boolean;
    description?: string | null;
}

export interface UomConvertResult {
    quantity: string;
    from_uom: UomSummary;
    to_uom: UomSummary;
    conversion_factor: string;
    converted_quantity: string;
}

export interface UomListResponse<T> {
    data: T[];
    meta?: PaginationMeta;
}
