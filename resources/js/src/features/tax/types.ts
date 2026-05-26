export type TaxGroupRecord = {
    id: number;
    tenant_id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
};

export type TaxRateRecord = {
    id: number;
    tenant_id: number;
    tax_group_id: number;
    name: string;
    rate: number | string;
    type: 'percentage' | 'fixed';
    account_id: number | null;
    is_compound: boolean;
    is_active: boolean;
    valid_from: string | null;
    valid_to: string | null;
    created_at: string;
    updated_at: string;
};

export type TaxRuleRecord = {
    id: number;
    tenant_id: number;
    tax_group_id: number;
    product_category_id: number | null;
    party_type: 'customer' | 'supplier' | null;
    region: string | null;
    priority: number;
    created_at: string;
    updated_at: string;
};

export type TaxGroupFilters = {
    tenant_id?: number;
    name?: string;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type TaxRateFilters = {
    tenant_id: number;
    name?: string;
    type?: 'percentage' | 'fixed';
    is_compound?: boolean;
    is_active?: boolean;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type TaxRuleFilters = {
    tenant_id: number;
    product_category_id?: number;
    party_type?: 'customer' | 'supplier';
    region?: string;
    priority?: number;
    per_page?: number;
    page?: number;
    sort?: string;
};
