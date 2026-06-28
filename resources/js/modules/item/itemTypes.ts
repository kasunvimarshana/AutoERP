import type { NamedResource } from '@/shared/types/common';

export const itemTypes = ['stock', 'non_stock', 'service', 'labour', 'asset', 'consumable', 'package', 'combo'] as const;
export const trackingTypes = ['none', 'batch', 'lot', 'serial'] as const;
export const costingMethods = ['fifo', 'weighted_average', 'standard', 'manual', 'none'] as const;
export const itemUnitRoles = ['base', 'purchase', 'sales', 'service', 'rental'] as const;
export const itemPriceTypes = ['purchase', 'sales', 'service', 'rental'] as const;
export const itemCodeTypes = ['sku', 'barcode', 'supplier_code', 'customer_code', 'internal_code', 'oem_code'] as const;
export const bundleLineTypes = ['stock', 'service', 'labour', 'non_stock', 'charge'] as const;

export type ItemType = typeof itemTypes[number];

export interface ItemSummary extends NamedResource {
    code: string;
    sku?: string | null;
    barcode?: string | null;
    item_type: ItemType;
    tracking_type: string;
    costing_method: string;
    category?: NamedResource | null;
    brand?: NamedResource | null;
    tenant_base_currency?: NamedResource | null;
    base_uom?: NamedResource | null;
    standard_price?: string | null;
    standard_price_basis?: string | null;
    default_tax_group_id?: number | null;
    purchase_tax_group_id?: number | null;
    sales_tax_group_id?: number | null;
    is_stockable: boolean;
    is_combo: boolean;
    is_active: boolean;
}

export interface Item extends ItemSummary {
    description?: string | null;
    metadata?: Record<string, unknown> | null;
    default_tax_group?: NamedResource | null;
    purchase_tax_group?: NamedResource | null;
    sales_tax_group?: NamedResource | null;
    is_tax_exempt?: boolean;
    units?: ItemUnit[];
    variants?: ItemVariant[];
    bundles?: ItemBundle[];
    prices?: ItemPrice[];
    codes?: ItemCode[];
    usage_rules?: ItemUsageRule[];
}

export interface ItemPayload {
    code: string;
    name: string;
    item_type: string;
    tracking_type: string;
    costing_method: string;
    item_category_id?: number | null;
    item_brand_id?: number | null;
    sku?: string | null;
    barcode?: string | null;
    description?: string | null;
    base_uom_id?: number | null;
    standard_price?: string | null;
    default_tax_group_id?: number | null;
    purchase_tax_group_id?: number | null;
    sales_tax_group_id?: number | null;
    is_stockable: boolean;
    is_combo: boolean;
    is_tax_exempt: boolean;
    is_active: boolean;
}

export interface ItemUnit {
    id: number;
    uom: NamedResource | null;
    unit_role: string;
    conversion_factor: string;
    is_default: boolean;
    is_active: boolean;
}

export interface ItemUnitPayload {
    uom_id: number;
    unit_role: string;
    conversion_factor: string;
    is_default: boolean;
    is_active: boolean;
}

export interface ItemVariant {
    id: number;
    code: string;
    name: string;
    sku?: string | null;
    barcode?: string | null;
    attributes?: Record<string, unknown> | null;
    is_active: boolean;
}

export type ItemVariantPayload = Omit<ItemVariant, 'id' | 'attributes'> & { attributes?: Record<string, unknown> | null };

export interface ItemBundle {
    id: number;
    child_item: ItemSummary | null;
    child_variant: NamedResource | null;
    quantity: string;
    uom: NamedResource | null;
    line_type: string;
    is_required: boolean;
    sort_order: number;
}

export interface ItemBundlePayload {
    child_item_id: number;
    child_variant_id?: number | null;
    quantity: string;
    uom_id?: number | null;
    line_type: string;
    is_required: boolean;
    sort_order: number;
}

export interface ItemPrice {
    id: number;
    variant: NamedResource | null;
    price_type: string;
    currency: NamedResource | null;
    uom: NamedResource | null;
    amount: string;
    effective_from?: string | null;
    effective_to?: string | null;
    is_active: boolean;
}

export interface ItemPricePayload {
    item_variant_id?: number | null;
    price_type: string;
    currency_id?: number | null;
    uom_id?: number | null;
    amount: string;
    effective_from?: string | null;
    effective_to?: string | null;
    is_active: boolean;
}

export interface ItemCode {
    id: number;
    variant: NamedResource | null;
    code_type: string;
    code: string;
    party_type?: string | null;
    is_primary: boolean;
}

export interface ItemCodePayload {
    item_variant_id?: number | null;
    code_type: string;
    code: string;
    party_type?: string | null;
    party_id?: number | null;
    is_primary: boolean;
}

export interface ItemUsageRule {
    id: number;
    module_code: string;
    is_enabled: boolean;
}

export type ItemUsageRulePayload = Omit<ItemUsageRule, 'id'>;

export interface ItemUsageModule {
    code: string;
    name: string;
    supported_item_types: string[];
}

export interface ItemCategory extends NamedResource {
    code: string;
    description?: string | null;
    parent?: NamedResource | null;
    is_active: boolean;
    sort_order: number;
}

export interface ItemCategoryPayload {
    code: string;
    name: string;
    parent_id?: number | null;
    description?: string | null;
    is_active: boolean;
    sort_order: number;
}

export interface ItemBrand extends NamedResource {
    code: string;
    description?: string | null;
    is_active: boolean;
}

export interface ItemBrandPayload {
    code: string;
    name: string;
    description?: string | null;
    is_active: boolean;
}

export interface ItemWithRelationsPayload {
    item: ItemPayload;
    units: ItemUnitPayload[];
    variants: ItemVariantPayload[];
    bundles: ItemBundlePayload[];
    prices: ItemPricePayload[];
    codes: ItemCodePayload[];
    usage_rules: ItemUsageRulePayload[];
}

export interface BaseUomAffectedModule {
    module: string;
    count: number;
    references: Record<string, number>;
}

export interface BaseUomBlocker {
    code: string;
    message: string;
    count: number;
}

export interface BaseUomUsageAudit {
    item: ItemSummary;
    has_usage: boolean;
    can_direct_edit: boolean;
    usage_count: number;
    affected_modules: BaseUomAffectedModule[];
    blockers: BaseUomBlocker[];
    warnings: string[];
}

export interface BaseUomPreviewRow {
    area: string;
    reference: string;
    metric: string;
    before: string;
    after: string;
}

export interface BaseUomConversionPreview {
    item: ItemSummary;
    old_base_uom: NamedResource | null;
    new_base_uom: NamedResource | null;
    conversion_factor: string | null;
    factor_source: string | null;
    effective_at: string;
    is_valid: boolean;
    affected_modules: BaseUomAffectedModule[];
    preview_rows: BaseUomPreviewRow[];
    blockers: BaseUomBlocker[];
    warnings: string[];
}

export interface BaseUomRevision {
    id: number;
    item?: NamedResource;
    old_base_uom: NamedResource | null;
    new_base_uom: NamedResource | null;
    conversion_factor: string;
    effective_at: string;
    reason?: string | null;
    status: string;
    validation_summary?: Record<string, unknown> | null;
    applied_at?: string | null;
    created_at: string;
}

export interface BaseUomChangePayload {
    new_base_uom_id: number;
    conversion_factor?: string | null;
    effective_at?: string | null;
    reason?: string | null;
}
