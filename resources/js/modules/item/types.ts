import type { NamedResource } from '@/shared/types/common';

export interface Item extends NamedResource {
    item_type: string;
    tracking_type?: string;
    costing_method?: string;
    sku?: string | null;
    barcode?: string | null;
    description?: string | null;
    is_stockable?: boolean;
    is_combo?: boolean;
    is_active?: boolean;
    category?: NamedResource | null;
    brand?: NamedResource | null;
    base_uom?: NamedResource | null;
    units?: Record<string, unknown>[];
    variants?: Record<string, unknown>[];
    bundle_lines?: Record<string, unknown>[];
    prices?: Record<string, unknown>[];
    codes?: Record<string, unknown>[];
    usage_rules?: Record<string, unknown>[];
}

export interface ItemPayload {
    code: string;
    name: string;
    item_type: string;
    tracking_type?: string;
    costing_method?: string;
    item_category_id?: number;
    item_brand_id?: number;
    sku?: string;
    barcode?: string;
    description?: string;
    base_uom_id?: number;
    is_stockable?: boolean;
    is_combo?: boolean;
    is_active?: boolean;
    units?: Array<{ uom_id: number; unit_role: string; conversion_factor?: string; is_default?: boolean }>;
    variants?: Array<{ code: string; name: string; sku?: string; barcode?: string }>;
    bundles?: Array<{ child_item_id: number; quantity: string; line_type: string }>;
    prices?: Array<{ price_type: string; amount: string }>;
    codes?: Array<{ code_type: string; code: string; is_primary?: boolean }>;
    usage_rules?: Array<{ module_code: string; is_enabled?: boolean }>;
}
