export type SupplierRecord = {
    id: number;
    tenant_id: number;
    user_id: number | null;
    supplier_code: string | null;
    name: string;
    type: 'individual' | 'company';
    org_unit_id: number | null;
    tax_number: string | null;
    registration_number: string | null;
    currency_id: number | null;
    payment_terms_days: number | null;
    ap_account_id: number | null;
    status: 'active' | 'inactive';
    notes: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type SupplierAddress = {
    id: number;
    tenant_id: number;
    supplier_id: number;
    type: 'billing' | 'shipping' | 'remittance' | 'other';
    label: string | null;
    address_line1: string;
    address_line2: string | null;
    city: string;
    state: string | null;
    postal_code: string;
    country_id: number;
    is_default: boolean;
    created_at: string;
    updated_at: string;
};

export type SupplierContact = {
    id: number;
    tenant_id: number;
    supplier_id: number;
    name: string;
    role: string | null;
    email: string | null;
    phone: string | null;
    is_primary: boolean;
    created_at: string;
    updated_at: string;
};

export type SupplierProduct = {
    id: number;
    tenant_id: number;
    supplier_id: number;
    product_id: number;
    variant_id: number | null;
    supplier_sku: string | null;
    lead_time_days: number | null;
    min_order_qty: number | string;
    is_preferred: boolean;
    last_purchase_price: number | string | null;
    created_at: string;
    updated_at: string;
};

export type SupplierPriceListAssignment = {
    id: number;
    tenant_id: number;
    supplier_id: number;
    price_list_id: number;
    priority: number;
    created_at: string;
    updated_at: string;
};

export type SupplierListFilters = {
    tenant_id?: number;
    user_id?: number;
    org_unit_id?: number;
    supplier_code?: string;
    name?: string;
    type?: 'individual' | 'company';
    status?: 'active' | 'inactive';
    currency_id?: number;
    ap_account_id?: number;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type SupplierPayload = {
    tenant_id: number;
    user?: {
        email: string;
        first_name: string;
        last_name: string;
        phone?: string | null;
        active?: boolean;
        address?: Record<string, unknown> | null;
        preferences?: Record<string, unknown> | null;
    };
    supplier_code?: string | null;
    name: string;
    type?: 'individual' | 'company';
    org_unit_id?: number | null;
    tax_number?: string | null;
    registration_number?: string | null;
    currency_id?: number | null;
    payment_terms_days?: number | null;
    ap_account_id?: number | null;
    status?: 'active' | 'inactive';
    notes?: string | null;
    metadata?: Record<string, unknown> | null;
};
