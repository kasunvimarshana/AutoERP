import type { PaginationMeta } from '@/shared/types/pagination';

export type ConfigurationScope = 'global' | 'tenant' | 'organization_unit';
export type ConfigurationValueType = 'string' | 'integer' | 'decimal' | 'boolean' | 'json';

export interface PlatformConfigurationTarget {
    tenant_id: number;
    organization_unit_id?: number;
}

export interface ConfigurationOrganizationTarget {
    id: number;
    name: string;
    code: string;
    path: string;
    depth: number;
    is_active: boolean;
}

export interface ConfigurationDefinition {
    key: string;
    label: string;
    description: string;
    owner: string;
    value_type: ConfigurationValueType;
    allowed_scopes: ConfigurationScope[];
    default_value: unknown;
    nullable: boolean;
    sensitive: boolean;
    runtime_mutable: boolean;
    options: Array<string | number | boolean>;
    minimum: string | null;
    maximum: string | null;
    lookup: string | null;
}



export interface ConfigurationTransferDocument {
    schema_version: number;
    scope: 'global';
    generated_at?: string;
    sensitive_values_included?: false;
    entries: Array<{ key: string; value: unknown }>;
}

export interface ConfigurationImportPreviewEntry {
    key: string;
    label: string;
    owner: string;
    action: 'create' | 'update' | 'unchanged';
    current_value: unknown;
    import_value: unknown;
    current_version: number | null;
}

export interface ConfigurationImportPreview {
    schema_version: number;
    scope: 'global';
    confirmation_digest: string;
    summary: { total: number; create: number; update: number; unchanged: number };
    entries: ConfigurationImportPreviewEntry[];
}

export interface ConfigurationImportResult {
    created: number;
    updated: number;
    unchanged: number;
}

export interface ConfigurationGlobalImpact {
    key: string;
    tenant_count: number;
    tenant_override_count: number;
    inheriting_tenant_count: number;
}

export interface ConfigurationEntry {
    key: string;
    label: string;
    description: string;
    owner: string;
    value_type: ConfigurationValueType;
    scope: ConfigurationScope;
    value: unknown;
    display_value: string | null;
    effective_value: unknown;
    effective_display_value: string | null;
    source_scope: ConfigurationScope;
    inherited_value: unknown;
    inherited_display_value: string | null;
    inherited_configured: boolean;
    inherited_source_scope: ConfigurationScope | 'default';
    inherited_uses_default: boolean;
    sensitive: boolean;
    runtime_mutable: boolean;
    row_version: number;
    updated_at: string;
}

export interface ConfigurationEntryPage {
    data: ConfigurationEntry[];
    meta: PaginationMeta;
    existing_keys: string[];
}

export interface ConfigurationRevision {
    id: number;
    operation: 'created' | 'updated' | 'removed' | 'rolled_back';
    scope: ConfigurationScope;
    tenant_id: number | null;
    organization_unit_id: number | null;
    key: string;
    value: unknown;
    display_value: string | null;
    configured: boolean;
    sensitive: boolean;
    resulting_row_version: number | null;
    source_revision_id: number | null;
    actor: { type: 'system' | 'platform_operator' | 'tenant_user'; id: number | null; name: string | null; email: string | null };
    reason: string | null;
    created_at: string;
}

export interface ConfigurationRevisionPage {
    data: ConfigurationRevision[];
    meta: PaginationMeta;
}
