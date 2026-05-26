export type PriceListRecord = {
    id: number;
    tenant_id: number;
    name: string;
    type: 'purchase' | 'sales';
    currency_id: number | null;
    is_default: boolean;
    valid_from: string | null;
    valid_to: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type PriceListItemRecord = {
    id: number;
    tenant_id: number;
    price_list_id: number;
    product_id: number;
    variant_id: number | null;
    uom_id: number | null;
    min_quantity: number | string;
    price: number | string;
    discount_pct: number | string | null;
    valid_from: string | null;
    valid_to: string | null;
    created_at: string;
    updated_at: string;
};

export type PriceListFilters = {
    tenant_id?: number;
    name?: string;
    type?: 'purchase' | 'sales';
    currency_id?: number;
    is_default?: boolean;
    is_active?: boolean;
    per_page?: number;
    page?: number;
    sort?: string;
};
