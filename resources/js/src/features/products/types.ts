export type ProductType = 'physical' | 'service' | 'digital' | 'combo' | 'variable';
export type ProductValuationMethod = 'fifo' | 'lifo' | 'weighted_average' | 'standard';
export type ProductActivityStatus = 'active' | 'inactive' | 'all';
export type IdentifierTechnology = 'barcode_1d' | 'barcode_2d' | 'qr_code' | 'rfid_hf' | 'rfid_uhf' | 'nfc' | 'gs1_epc' | 'custom';
export type IdentifierFormat = 'ean13' | 'ean8' | 'upc_a' | 'code128' | 'code39' | 'qr' | 'datamatrix' | 'gs1_128' | 'epc_sgtin' | 'other';

export type ProductMetadata = {
    sales_price?: number | string | null;
    purchase_price?: number | string | null;
    profit_margin?: number | string | null;
    price_list_note?: string | null;
    supplier_reference?: string | null;
    [key: string]: unknown;
};

export type Product = {
    id: number;
    tenant_id: number;
    category_id: number | null;
    brand_id: number | null;
    org_unit_id: number | null;
    type: ProductType;
    name: string;
    image_path: string | null;
    slug: string | null;
    sku: string | null;
    description: string | null;
    base_uom_id: number | null;
    purchase_uom_id: number | null;
    sales_uom_id: number | null;
    tax_group_id: number | null;
    uom_conversion_factor: string | number | null;
    is_batch_tracked: boolean;
    is_lot_tracked: boolean;
    is_serial_tracked: boolean;
    valuation_method: ProductValuationMethod | null;
    standard_cost: string | number | null;
    income_account_id: number | null;
    cogs_account_id: number | null;
    inventory_account_id: number | null;
    expense_account_id: number | null;
    is_active: boolean;
    metadata: ProductMetadata | null;
    created_at: string;
    updated_at: string;
};

export type ProductRecord = Product;

export type ProductBrand = {
    id: number;
    tenant_id: number;
    parent_id: number | null;
    name: string;
    image_path: string | null;
    slug: string | null;
    code: string | null;
    path: string | null;
    depth: number | null;
    is_active: boolean;
    website: string | null;
    description: string | null;
    attributes: Record<string, unknown> | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type ProductCategory = {
    id: number;
    tenant_id: number;
    parent_id: number | null;
    name: string;
    image_path: string | null;
    slug: string | null;
    code: string | null;
    path: string | null;
    depth: number | null;
    is_active: boolean;
    description: string | null;
    attributes: Record<string, unknown> | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type UnitOfMeasureType = 'unit' | 'mass' | 'volume' | 'length' | 'time' | 'other';

export type UnitOfMeasure = {
    id: number;
    tenant_id: number;
    name: string;
    symbol: string;
    type: UnitOfMeasureType;
    is_base: boolean;
    created_at: string;
    updated_at: string;
};

export type ProductVariant = {
    id: number;
    tenant_id: number;
    product_id: number;
    name: string;
    sku: string | null;
    is_default: boolean;
    is_active: boolean;
    metadata: {
        attribute_summary?: string | null;
        notes?: string | null;
        [key: string]: unknown;
    } | null;
    created_at: string;
    updated_at: string;
};

export type ProductIdentifier = {
    id: number;
    tenant_id: number;
    product_id: number;
    variant_id: number | null;
    batch_id: number | null;
    serial_id: number | null;
    technology: IdentifierTechnology | null;
    format: IdentifierFormat | null;
    value: string | null;
    gs1_company_prefix: string | null;
    gs1_application_identifiers: string | null;
    is_primary: boolean;
    is_active: boolean;
    format_config: Record<string, unknown> | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type UomConversion = {
    id: number;
    from_uom_id: number;
    to_uom_id: number;
    factor: string | number;
    created_at: string;
    updated_at: string;
};

export type ProductListFilters = {
    tenant_id: number;
    page?: number;
    per_page?: number;
    name?: string;
    type?: ProductType;
    category_id?: number;
    brand_id?: number;
    is_active?: boolean;
    sort?: string;
};

export type ProductBrandListFilters = {
    tenant_id: number;
    page?: number;
    per_page?: number;
    name?: string;
    code?: string;
    parent_id?: number;
    is_active?: boolean;
    sort?: string;
};

export type ProductCategoryListFilters = {
    tenant_id: number;
    page?: number;
    per_page?: number;
    name?: string;
    code?: string;
    parent_id?: number;
    is_active?: boolean;
    sort?: string;
};

export type UnitOfMeasureListFilters = {
    tenant_id: number;
    page?: number;
    per_page?: number;
    name?: string;
    symbol?: string;
    type?: UnitOfMeasureType;
    is_base?: boolean;
    sort?: string;
};

export type ProductVariantListFilters = {
    tenant_id: number;
    product_id?: number;
    page?: number;
    per_page?: number;
    name?: string;
    sku?: string;
    is_default?: boolean;
    is_active?: boolean;
    sort?: string;
};

export type ProductIdentifierListFilters = {
    tenant_id: number;
    product_id?: number;
    variant_id?: number;
    page?: number;
    per_page?: number;
    technology?: string;
    format?: string;
    value?: string;
    is_primary?: boolean;
    is_active?: boolean;
    sort?: string;
};

export type ProductPayload = {
    tenant_id: number;
    type: ProductType;
    name: string;
    slug?: string;
    sku?: string | null;
    description?: string | null;
    category_id?: number | null;
    brand_id?: number | null;
    base_uom_id: number;
    purchase_uom_id?: number | null;
    sales_uom_id?: number | null;
    uom_conversion_factor?: number | null;
    valuation_method?: ProductValuationMethod | null;
    standard_cost?: number | null;
    is_batch_tracked?: boolean;
    is_lot_tracked?: boolean;
    is_serial_tracked?: boolean;
    is_active?: boolean;
    metadata?: ProductMetadata | null;
};

export type ProductBrandPayload = {
    tenant_id: number;
    name: string;
    slug?: string;
    code?: string | null;
    parent_id?: number | null;
    website?: string | null;
    description?: string | null;
    is_active?: boolean;
};

export type ProductCategoryPayload = {
    tenant_id: number;
    name: string;
    slug?: string;
    code?: string | null;
    parent_id?: number | null;
    description?: string | null;
    is_active?: boolean;
};

export type UnitOfMeasurePayload = {
    tenant_id: number;
    name: string;
    symbol: string;
    type?: UnitOfMeasureType;
    is_base?: boolean;
};

export type ProductVariantPayload = {
    tenant_id: number;
    product_id: number;
    name: string;
    sku?: string | null;
    is_default?: boolean;
    is_active?: boolean;
    metadata?: ProductVariant['metadata'] | null;
};

export type ProductIdentifierPayload = {
    tenant_id: number;
    product_id: number;
    variant_id?: number | null;
    technology: IdentifierTechnology;
    format?: IdentifierFormat | null;
    value: string;
    gs1_company_prefix?: string | null;
    is_primary?: boolean;
    is_active?: boolean;
    format_config?: Record<string, unknown> | null;
    metadata?: Record<string, unknown> | null;
};

export type UomConversionPayload = {
    from_uom_id: number;
    to_uom_id: number;
    factor: number;
};
