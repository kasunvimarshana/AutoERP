import type { PaginationMeta } from '@/shared/types/pagination';
import type { AuditListFilters, AuditListResponse, AuditLogDetail } from '@/modules/audit/auditTypes';

export interface PlatformOperatorInvitationSummary {
    status: 'pending' | 'accepted' | 'revoked' | 'expired';
    delivery_status: 'queued' | 'sending' | 'sent' | 'failed' | 'cancelled';
    expires_at: string | null;
    sent_at: string | null;
    failed_at: string | null;
    error_message: string | null;
}

export interface PlatformOperator {
    id: number;
    first_name: string;
    last_name: string | null;
    display_name: string;
    email: string;
    status: 'invited' | 'active' | 'inactive';
    invitation: PlatformOperatorInvitationSummary | null;
    permissions: string[];
    row_version: number;
    created_at: string;
    updated_at: string;
}

export interface PlatformOperatorPage {
    data: PlatformOperator[];
    meta: PaginationMeta;
    available_permissions: string[];
}

export interface CreatePlatformOperatorPayload {
    first_name: string;
    last_name?: string | null;
    email: string;
    permissions: string[];
}

export interface PlatformSession {
    id: string;
    operator: {
        id: number;
        name: string;
        email: string;
        status: string;
    } | null;
    status: string;
    ip_address: string | null;
    device_name: string | null;
    user_agent: string | null;
    authenticated_at: string | null;
    last_activity_at: string | null;
    expires_at: string | null;
    revoked_at: string | null;
}

export interface PlatformSessionPage {
    data: PlatformSession[];
    meta: PaginationMeta;
}

export interface PlatformAuditFilters extends AuditListFilters {
    tenant_id?: number;
}

export type PlatformAuditListResponse = AuditListResponse;
export type PlatformAuditDetail = AuditLogDetail;

export interface CountMap {
    [status: string]: number;
}

export interface PlatformHealthFailureBase {
    tenant_id: number;
    tenant_code: string;
    tenant_name: string;
    error_code: string | null;
    error_message: string | null;
}

export interface PlatformOnboardingFailure extends PlatformHealthFailureBase {
    failed_step: string | null;
    correlation_id: string | null;
    updated_at: string | null;
}

export interface PlatformDomainFailure extends PlatformHealthFailureBase {
    domain: string;
    ownership_status: string;
    operational_status: string;
    updated_at: string | null;
}

export interface PlatformOutboxFailure extends PlatformHealthFailureBase {
    event_uuid: string;
    event_type: string;
    attempts: number;
    failed_at: string | null;
}

export interface PlatformStorageFailure extends PlatformHealthFailureBase {
    job_id: number;
    reason: string;
    attempts: number;
    failed_at: string | null;
}

export interface PlatformInvitationDeliveryFailure extends PlatformHealthFailureBase {
    public_id: string;
    email: string;
    attempt_number: number;
    processing_attempt_count: number;
    failed_at: string | null;
}

export interface PlatformInfrastructureHealth {
    ready: boolean;
    mail: {
        ready: boolean;
        mailer: string | null;
        from_address_configured: boolean;
        external_transport: boolean;
    };
    queue: {
        ready: boolean;
        connection: string | null;
        requires_worker: boolean;
        pending_jobs: number | null;
        failed_jobs: number | null;
    };
    administrator_invitation_url: {
        ready: boolean;
        origin: string | null;
    };
    capabilities: {
        database: { strategy: string; tenant_specific_profiles_supported: boolean };
        storage: { strategy: string; isolation: string; disk: string; tenant_specific_profiles_supported: boolean };
        mail: { strategy: string; tenant_specific_profiles_supported: boolean };
        configuration: { precedence: string[]; arbitrary_laravel_config_overrides_supported: boolean };
    };
}

export interface PlatformInvitationDeliveryHealth {
    counts: CountMap;
    failed: number;
    stale: number;
}

export interface PlatformHealthOverview {
    generated_at: string;
    release: {
        release_id: string | null;
        commit: string | null;
        environment: string;
        database_strategy: string;
    };
    tenants: CountMap;
    onboarding: CountMap;
    domains: { ownership: CountMap; operational: CountMap };
    subscriptions: CountMap;
    operations: { outbox: CountMap; storage_cleanup: CountMap };
    infrastructure: PlatformInfrastructureHealth;
    invitation_delivery: PlatformInvitationDeliveryHealth;
    storage: { tracked_document_bytes: number; tracked_document_count: number };
    alerts: {
        onboarding_failures: number;
        domain_failures: number;
        dead_outbox_events: number;
        dead_storage_cleanup_jobs: number;
        failed_invitation_deliveries: number;
        stale_invitation_deliveries: number;
        requires_attention: boolean;
    };
    failures: {
        onboarding: PlatformOnboardingFailure[];
        domains: PlatformDomainFailure[];
        outbox: PlatformOutboxFailure[];
        storage_cleanup: PlatformStorageFailure[];
        invitation_delivery: PlatformInvitationDeliveryFailure[];
    };
}

export interface PlatformTenantHealthDetail {
    generated_at: string;
    tenant: {
        id: number;
        code: string;
        name: string;
        status: string;
        row_version: number;
    };
    onboarding: null | {
        status: string;
        failed_step: string | null;
        error_code: string | null;
        error_message: string | null;
        correlation_id: string | null;
        steps: Array<{
            step: string;
            owner_module: string;
            status: string;
            attempt_count: number;
            error_code: string | null;
            error_message: string | null;
        }>;
    };
    domains: Array<{
        id: number;
        domain: string;
        status: string;
        ownership_status: string;
        operational_status: string;
        last_checked_at: string | null;
        retry_at: string | null;
        error_code: string | null;
        error_message: string | null;
    }>;
    subscription: null | {
        state: string;
        state_reason: string | null;
        subscription_id: number;
        plan: string | null;
        starts_at: string | null;
        ends_at: string | null;
    };
    capacity: null | {
        measured_at: string;
        plan_revision_id: number;
        usage: Record<string, number>;
        limits: Record<string, number>;
        utilization_percent: Record<string, number | null>;
        blockers: Array<{ code: string; message: string; context: Record<string, unknown> }>;
    };
    infrastructure: PlatformInfrastructureHealth;
    invitation_delivery: PlatformInvitationDeliveryHealth;
    storage: {
        tracked_document_bytes: number;
        tracked_document_count: number;
        reconciliation: {
            measured_at: string;
            healthy: boolean;
            storage_reachable: boolean;
            error_code: string | null;
            error_message: string | null;
            tracked_files: number;
            actual_files: number;
            tracked_bytes: number;
            actual_bytes: number;
            missing_files: number;
            orphan_files: number;
            size_mismatches: number;
            unreadable_files: number;
            invalid_metadata_paths: number;
        };
        cleanup: CountMap;
    };
    outbox: CountMap;
}
