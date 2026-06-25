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
    sensitive: boolean;
    row_version: number;
    updated_at: string;
}

export type ConfigurationRevisionAction = 'created' | 'updated' | 'removed';

export interface ConfigurationRevision {
    id: number;
    scope: ConfigurationScope;
    key: string;
    action: ConfigurationRevisionAction;
    value_type: ConfigurationValueType;
    sensitive: boolean;
    before_exists: boolean;
    before_value: unknown;
    after_exists: boolean;
    after_value: unknown;
    entry_row_version: number;
    changed_by_name: string;
    created_at: string;
}

export type ConfigurationPanelMode = 'tenant' | 'platform';
