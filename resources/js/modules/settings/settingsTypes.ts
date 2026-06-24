import type { PaginationMeta } from '@/shared/types/pagination';

export type ConfigurationScope = 'global' | 'tenant' | 'organization_unit';
export type ConfigurationValueType = 'string' | 'integer' | 'decimal' | 'boolean' | 'json';

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
    minimum: number | null;
    maximum: number | null;
    lookup: string | null;
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
