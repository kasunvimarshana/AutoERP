export type TenantRecord = {
    id: number;
    name: string;
    slug: string;
    domain: string | null;
    logo_url: string | null;
    database_config: Record<string, unknown> | null;
    mail_config: Record<string, unknown> | null;
    cache_config: Record<string, unknown> | null;
    queue_config: Record<string, unknown> | null;
    feature_flags: Record<string, unknown> | string[] | null;
    api_keys: Record<string, unknown> | string[] | null;
    settings: Record<string, unknown> | null;
    plan: unknown;
    tenant_plan_id: number | null;
    status: 'active' | 'suspended' | 'pending' | 'cancelled' | string;
    trial_ends_at: string | null;
    subscription_ends_at: string | null;
    active: boolean;
    created_at: string | null;
    updated_at: string | null;
};

export type TenantPlanRecord = {
    id: number;
    name: string;
    slug: string;
    features: Record<string, unknown> | null;
    limits: Record<string, unknown> | null;
    price: number | string | null;
    currency_code: string | null;
    billing_interval: 'month' | 'year' | string;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
};

export type TenantDomainRecord = {
    id: number;
    tenant_id: number;
    domain: string;
    is_primary: boolean;
    is_verified: boolean;
    verified_at: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type TenantSettingRecord = {
    id: number;
    tenant_id: number;
    key: string;
    value: unknown;
    group: string | null;
    is_public: boolean;
    created_at: string | null;
    updated_at: string | null;
};

export type TenantFilters = {
    name?: string;
    slug?: string;
    domain?: string;
    active?: boolean;
    status?: string;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type TenantPlanFilters = {
    billing_interval?: 'month' | 'year';
    per_page?: number;
    page?: number;
};

export type TenantDomainFilters = {
    is_primary?: boolean;
    is_verified?: boolean;
    per_page?: number;
    page?: number;
};

export type TenantSettingFilters = {
    group?: string;
    is_public?: boolean;
    per_page?: number;
    page?: number;
};
