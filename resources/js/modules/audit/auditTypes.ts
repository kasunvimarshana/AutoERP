export const AUDIT_EVENT_CATEGORIES = [
    'authentication',
    'authorization',
    'administration',
    'configuration',
    'data',
    'financial',
    'inventory',
    'security',
    'workflow',
    'system',
] as const;

export type AuditActorType = 'user' | 'system' | 'integration' | 'job';
export type AuditEventCategory = (typeof AUDIT_EVENT_CATEGORIES)[number];

export interface AuditActor {
    type: AuditActorType;
    id: string | null;
    name: string | null;
}

export interface AuditSubject {
    type: string;
    id: string;
    reference: string | null;
}

export interface AuditNamedScope {
    id: number | null;
    name: string | null;
}

export interface AuditLogSummary {
    id: number;
    event_uuid: string;
    event_category: AuditEventCategory;
    event_name: string;
    source_module: string;
    actor: AuditActor;
    subject: AuditSubject;
    tenant: AuditNamedScope;
    organization_unit: AuditNamedScope;
    tags: string[];
    occurred_at: string;
    recorded_at: string;
}

export interface AuditSource {
    module: string;
    type: string | null;
    id: string | null;
    reference: string | null;
}

export interface AuditRequestContext {
    id: string | null;
    method: string | null;
    route_name: string | null;
    route_path: string | null;
    ip_address: string | null;
    user_agent: string | null;
    actor_guard: string | null;
    actor_provider: string | null;
    application_id: string | null;
    impersonator_user_id: number | null;
}

export interface AuditLogDetail extends AuditLogSummary {
    tenant: AuditNamedScope;
    source: AuditSource;
    sensitive_details_visible: boolean;
    producer_key?: string | null;
    changes?: Record<string, unknown> | null;
    metadata?: Record<string, unknown> | null;
    request?: AuditRequestContext;
}

export interface AuditListFilters {
    event_category?: string;
    event_name?: string;
    source_module?: string;
    actor_type?: string;
    actor_id?: string;
    subject_type?: string;
    subject_id?: string;
    from_date?: string;
    to_date?: string;
    per_page?: number;
    cursor?: string;
}

export interface AuditListResponse {
    data: AuditLogSummary[];
    meta: {
        next_cursor: string | null;
        has_more: boolean;
        per_page: number;
    };
}
