import type { TenantModuleCode } from '@/app/access/tenantModules';
import type { PaginationMeta } from '@/shared/types/pagination';

export type TenantStatus = 'draft' | 'active' | 'inactive' | 'suspended' | 'archived';
export type TenantSubscriptionStatus = 'trial' | 'active' | 'expired' | 'cancelled';

export interface NamedReference {
    id: number;
    code?: string | null;
    name: string;
    symbol?: string | null;
    is_active?: boolean;
}

export type { TenantModuleCode };

export interface TenantPlanFeatures {
    enabled_modules: TenantModuleCode[];
}

export interface TenantPlanLimits {
    max_users?: number;
    max_organization_units?: number;
    max_warehouses?: number;
    max_storage_mb?: number;
}

export interface TenantPlanRevision {
    id: number;
    tenant_plan_id: number;
    revision_number: number;
    features: TenantPlanFeatures;
    limits: TenantPlanLimits;
    price: string;
    currency_id: number | null;
    currency?: NamedReference | null;
    billing_interval: 'month' | 'quarter' | 'year';
    effective_at: string;
    plan?: Pick<TenantPlan, 'id' | 'name' | 'slug' | 'is_active'> | null;
    created_at: string;
}

export interface TenantSubscription {
    id: number;
    tenant_id: number;
    status: TenantSubscriptionStatus;
    starts_at: string;
    trial_ends_at: string | null;
    ends_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    row_version: number;
    revision: TenantPlanRevision;
    created_at: string;
    updated_at: string;
}

export interface TenantOnboardingSummary {
    status: 'pending' | 'provisioning' | 'awaiting_domain' | 'ready' | 'completed' | 'failed';
    initial_admin_email: string | null;
    completed_steps: string[] | null;
    last_error: string | null;
    provisioned_at: string | null;
    completed_at: string | null;
    row_version: number;
}

export interface TenantRecord {
    id: number;
    uuid: string;
    code: string;
    name: string;
    slug: string;
    has_logo: boolean;
    cross_org_transactions: boolean;
    base_currency_id: number | null;
    status: TenantStatus;
    status_reason: string | null;
    activated_at: string | null;
    suspended_at: string | null;
    archived_at: string | null;
    row_version: number;
    base_currency: NamedReference | null;
    current_subscription: TenantSubscription | null;
    onboarding: TenantOnboardingSummary | null;
    created_at: string;
    updated_at: string;
}

export interface TenantPlan {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    row_version: number;
    latest_revision: TenantPlanRevision | null;
    features: TenantPlanFeatures | null;
    limits: TenantPlanLimits | null;
    price: string | null;
    currency_id: number | null;
    currency?: NamedReference | null;
    billing_interval: 'month' | 'quarter' | 'year' | null;
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

export interface TenantSubscriptionReadiness {
    ready: boolean;
    tenant_id: number;
    plan_revision_id: number;
    usage: Record<string, number>;
    limits: Record<string, number>;
    removed_modules: string[];
    blockers: Array<{ code: string; message: string; context: Record<string, unknown> }>;
}

export interface TenantOnboardingReadiness {
    ready: boolean;
    tenant_id: number;
    onboarding_status: TenantOnboardingSummary['status'];
    checks: Record<string, boolean>;
    blockers: Array<{ code: string; message: string }>;
}

export interface TenantOnboardingProvisionResult {
    state: TenantOnboardingSummary;
    invitation_token: string | null;
    invitation_expires_at: string | null;
    permission_count: number;
    tenant_row_version: number;
    readiness: TenantOnboardingReadiness;
}

export interface TenantPage {
    data: TenantRecord[];
    meta: PaginationMeta;
}

export interface TenantPlanPage {
    data: TenantPlan[];
    meta: PaginationMeta;
}
