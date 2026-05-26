export type CustomerRecord = {
    id: number;
    tenant_id: number;
    user_id: number | null;
    customer_code: string | null;
    name: string;
    type: 'individual' | 'company';
    org_unit_id: number | null;
    tax_number: string | null;
    registration_number: string | null;
    currency_id: number | null;
    credit_limit: number | string | null;
    payment_terms_days: number | null;
    ar_account_id: number | null;
    status: 'active' | 'inactive' | 'blocked';
    notes: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type CustomerAddress = {
    id: number;
    tenant_id: number;
    customer_id: number;
    type: 'billing' | 'shipping' | 'other';
    label: string | null;
    address_line1: string;
    address_line2: string | null;
    city: string;
    state: string | null;
    postal_code: string;
    country_id: number;
    is_default: boolean;
    geo_lat: number | string | null;
    geo_lng: number | string | null;
    created_at: string;
    updated_at: string;
};

export type CustomerContact = {
    id: number;
    tenant_id: number;
    customer_id: number;
    name: string;
    role: string | null;
    email: string | null;
    phone: string | null;
    is_primary: boolean;
    created_at: string;
    updated_at: string;
};

export type CustomerPriceListAssignment = {
    id: number;
    tenant_id: number;
    customer_id: number;
    price_list_id: number;
    priority: number;
    created_at: string;
    updated_at: string;
};

export type CustomerListFilters = {
    tenant_id?: number;
    user_id?: number;
    org_unit_id?: number;
    customer_code?: string;
    name?: string;
    type?: 'individual' | 'company';
    status?: 'active' | 'inactive' | 'blocked';
    currency_id?: number;
    ar_account_id?: number;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type CustomerPayload = {
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
    customer_code?: string | null;
    name: string;
    type?: 'individual' | 'company';
    org_unit_id?: number | null;
    tax_number?: string | null;
    registration_number?: string | null;
    currency_id?: number | null;
    credit_limit?: number | null;
    payment_terms_days?: number | null;
    ar_account_id?: number | null;
    status?: 'active' | 'inactive' | 'blocked';
    notes?: string | null;
    metadata?: Record<string, unknown> | null;
};
