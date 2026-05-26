export type AuditLogRecord = {
    id: number;
    tenant_id: number | null;
    user_id: number | null;
    event: string;
    auditable_type: string;
    auditable_id: string | number;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    diff: Record<string, unknown> | null;
    url: string | null;
    ip_address: string | null;
    user_agent: string | null;
    tags: string[] | null;
    metadata: Record<string, unknown> | null;
    occurred_at: string;
    created_at: string;
};

export type AuditLogFilters = {
    tenant_id?: number;
    user_id?: number;
    event?: string;
    auditable_type?: string;
    auditable_id?: string | number;
    per_page?: number;
    page?: number;
    sort?: string;
};
