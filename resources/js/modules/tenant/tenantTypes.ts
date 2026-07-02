import type { TenantModuleCode } from '@/app/access/tenantModules';
import type { PaginationMeta } from '@/shared/types/pagination';

export type TenantStatus = 'draft' | 'active' | 'inactive' | 'suspended' | 'archived';
export type TenantSubscriptionContractStatus = 'trial' | 'active';
export type TenantSubscriptionEffectiveStatus = 'missing' | 'scheduled' | 'trial' | 'active' | 'cancelled' | 'expired' | 'invalid';
export type TenantCurrentSubscriptionState = 'assigned' | 'cancelled' | 'expired';
export type TenantSubscriptionOperation = 'assign' | 'renew' | 'extend' | 'correct';
export type TenantOnboardingStatus =
    | 'pending'
    | 'provisioning'
    | 'awaiting_administrator'
    | 'awaiting_domain'
    | 'ready'
    | 'completed'
    | 'failed';

export interface NamedReference {
    id: number;
    code?: string | null;
    name: string;
    symbol?: string | null;
    is_active?: boolean;
}

export type { TenantModuleCode };

export type PlatformTenantTargetPurpose = 'configuration' | 'audit' | 'health';

export interface PlatformTenantTarget {
    id: number;
    name: string;
    code: string;
    status: TenantStatus;
}

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
    features_schema_version: number;
    features: TenantPlanFeatures;
    limits_schema_version: number;
    limits: TenantPlanLimits;
    price: string;
    currency_id: number | null;
    currency?: NamedReference | null;
    billing_interval: 'month' | 'quarter' | 'year';
    effective_at: string;
    change_note: string;
    plan?: Pick<TenantPlan, 'id' | 'name' | 'slug' | 'is_active'> | null;
    total_subscription_count?: number;
    assigned_subscription_count?: number;
    current_subscription_count?: number;
    historical_subscription_count?: number;
    created_at: string;
}

export interface TenantSubscriptionRevision {
    id: number;
    tenant_id: number;
    revision_number: number;
    operation: TenantSubscriptionOperation;
    tenant_plan_revision_id: number;
    supersedes_subscription_id: number | null;
    contract_status: TenantSubscriptionContractStatus;
    effective_status: TenantSubscriptionEffectiveStatus;
    starts_at: string;
    trial_ends_at: string | null;
    ends_at: string | null;
    change_reason: string | null;
    plan_name: string;
    plan_slug: string;
    plan_features_schema_version: number;
    plan_features: TenantPlanFeatures;
    plan_limits_schema_version: number;
    plan_limits: TenantPlanLimits;
    price: string;
    currency_code: string | null;
    currency_symbol: string | null;
    billing_interval: 'month' | 'quarter' | 'year';
    revision?: TenantPlanRevision | null;
    created_at: string;
}

export interface TenantCurrentSubscription extends TenantSubscriptionRevision {
    current_state: TenantCurrentSubscriptionState;
    current_state_reason: string | null;
    current_state_changed_at: string | null;
    row_version: number;
    assigned_at: string | null;
    assigned_by: number | null;
}

/** @deprecated Use TenantCurrentSubscription for the current pointer and TenantSubscriptionRevision for history. */
export type TenantSubscription = TenantCurrentSubscription;

export interface TenantOnboardingStep {
    step: string;
    owner_module: string;
    status: 'pending' | 'running' | 'completed' | 'failed';
    attempt_count: number;
    started_at: string | null;
    completed_at: string | null;
    error_code: string | null;
    error_message: string | null;
    correlation_id: string | null;
}

export interface TenantOnboardingSummary {
    status: TenantOnboardingStatus;
    operation_id?: string | null;
    initial_admin_email: string | null;
    root_organization_unit_id: number | null;
    super_admin_role_id: number | null;
    administrator_user_id: number | null;
    completed_steps: string[];
    failed_step: string | null;
    last_error_code: string | null;
    last_error_message: string | null;
    correlation_id: string | null;
    provisioned_at: string | null;
    completed_at: string | null;
    row_version: number;
    steps?: TenantOnboardingStep[];
}

export interface TenantRecord {
    id: number;
    uuid: string;
    code: string;
    name: string;
    slug: string;
    has_logo: boolean;
    base_currency_id: number | null;
    status: TenantStatus;
    status_reason: string | null;
    activated_at: string | null;
    suspended_at: string | null;
    archived_at: string | null;
    row_version: number;
    base_currency: NamedReference | null;
    current_subscription: TenantCurrentSubscription | null;
    onboarding: TenantOnboardingSummary | null;
    primary_domain: TenantDomain | null;
    created_at: string;
    updated_at: string;
}

export interface TenantPlan {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    row_version: number;
    revisions_count: number;
    total_subscription_count: number;
    assigned_subscription_count: number;
    current_subscription_count: number;
    historical_subscription_count: number;
    current_revision: TenantPlanRevision | null;
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

export interface TenantPlanCapabilities {
    commercial_modules: Array<{ code: TenantModuleCode; label: string }>;
    always_on_modules: string[];
    limits: Array<keyof TenantPlanLimits>;
}

export interface TenantDomain {
    id: number;
    domain: string;
    is_primary: boolean;
    status: 'pending' | 'active' | 'disabled';
    ownership_status: 'pending' | 'checking' | 'verified' | 'failed' | 'expired';
    routing_status: 'pending' | 'checking' | 'ready' | 'failed';
    tls_status: 'pending' | 'checking' | 'ready' | 'failed';
    reachability_status: 'pending' | 'checking' | 'ready' | 'failed';
    operational_status: 'pending' | 'checking' | 'ready' | 'failed' | 'disabled';
    operational_error_code: string | null;
    operational_error_message: string | null;
    last_operational_check_at: string | null;
    operational_retry_at: string | null;
    tls_expires_at: string | null;
    verification_method: 'dns_txt';
    verification_error_code: string | null;
    verification_error_message: string | null;
    last_verification_attempt_at: string | null;
    last_verified_at: string | null;
    revalidation_due_at: string | null;
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
    onboarding_status: TenantOnboardingStatus | 'missing';
    checks: Record<string, boolean>;
    blockers: Array<{
        code: string;
        stage: string;
        owner: string;
        action: string;
        message: string;
        context?: Record<string, unknown>;
    }>;
    routing: {
        ready: boolean;
        mode: 'verified_domain' | 'local_fallback' | 'unavailable';
        message: string;
        local_fallback?: {
            supported: boolean;
            enabled: boolean;
            configured_tenant_code: string | null;
            matches_tenant: boolean;
        };
    };
    infrastructure: {
        database: { strategy: string; tenant_specific_profiles_supported: boolean };
        storage: { strategy: string; isolation: string; disk: string; tenant_specific_profiles_supported: boolean };
        mail: { strategy: string; tenant_specific_profiles_supported: boolean };
        configuration: { precedence: string[]; arbitrary_laravel_config_overrides_supported: boolean };
    };
}

export interface TenantOnboardingProvisionResult {
    state: TenantOnboardingSummary;
    permission_count: number;
    administrator: {
        user_id: number;
        email: string;
        status: string;
    } | null;
    tenant_row_version: number;
    readiness: TenantOnboardingReadiness | null;
    correlation_id: string;
}

export interface TenantPage {
    data: TenantRecord[];
    meta: PaginationMeta;
}

export interface TenantPlanPage {
    data: TenantPlan[];
    meta: PaginationMeta;
}

export interface TenantDomainPage {
    data: TenantDomain[];
    meta: PaginationMeta;
}

export interface TenantDocumentPage {
    data: TenantDocument[];
    meta: PaginationMeta;
}
