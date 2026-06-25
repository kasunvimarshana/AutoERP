import type { PaginationMeta } from '@/shared/types/pagination';

export type TenantStatus = 'draft' | 'active' | 'inactive' | 'suspended' | 'archived';

export interface NamedReference {
    id: number;
    code?: string | null;
    name: string;
    symbol?: string | null;
    is_active?: boolean;
}

export interface TenantRecord {
    id: number;
    uuid: string;
    code: string;
    name: string;
    slug: string;
    has_logo: boolean;
    cross_org_transactions: boolean;
    tenant_plan_id: number | null;
    base_currency_id: number | null;
    status: TenantStatus;
    status_reason: string | null;
    activated_at: string | null;
    suspended_at: string | null;
    archived_at: string | null;
    trial_ends_at: string | null;
    subscription_ends_at: string | null;
    row_version: number;
    plan: Pick<TenantPlan, 'id' | 'name' | 'slug' | 'is_active'> | null;
    base_currency: NamedReference | null;
    created_at: string;
    updated_at: string;
}

export interface TenantPlan {
    id: number;
    name: string;
    slug: string;
    features: Record<string, unknown> | null;
    limits: Record<string, unknown> | null;
    price: string;
    currency_id: number | null;
    currency?: NamedReference | null;
    billing_interval: 'month' | 'quarter' | 'year';
    is_active: boolean;
    row_version: number;
    created_at: string;
    updated_at: string;
}

export interface TenantDomain {
    id: number;
    domain: string;
    is_primary: boolean;
    status: 'pending' | 'active' | 'disabled';
    verification_method: 'dns_txt';
    verification_expires_at: string | null;
    verified_at: string | null;
    row_version: number;
    created_at: string;
    updated_at: string;
}

export interface TenantDocument {
    id: number;
    name: string;
    document_type: string | null;
    original_filename: string;
    mime_type: string;
    size_bytes: number;
    checksum_sha256: string;
    row_version: number;
    created_at: string;
    updated_at: string;
}

export interface DomainVerificationChallenge {
    method: 'dns_txt';
    host: string;
    value: string;
    expires_at: string;
}


export interface TenantReadinessCheck {
    key: string;
    label: string;
    ready: boolean;
    guidance: string;
}

export interface TenantReadiness {
    ready_for_activation: boolean;
    checks: TenantReadinessCheck[];
}

export interface TenantPage {
    data: TenantRecord[];
    meta: PaginationMeta;
}

export interface TenantPlanPage {
    data: TenantPlan[];
    meta: PaginationMeta;
}
