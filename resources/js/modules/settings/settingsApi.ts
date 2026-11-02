import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type {
    ConfigurationDefinition,
    ConfigurationEntry,
    ConfigurationEntryPage,
    ConfigurationGlobalImpact,
    ConfigurationImportPreview,
    ConfigurationImportResult,
    ConfigurationOrganizationTarget,
    ConfigurationRevisionPage,
    ConfigurationScope,
    ConfigurationTransferDocument,
    PlatformConfigurationTarget,
} from './settingsTypes';

const tenantConfigurationBase = '/api/v1/configuration';
const platformConfigurationBase = '/api/v1/platform/configuration';
const scopePath: Record<ConfigurationScope, string> = {
    global: 'global',
    tenant: 'tenant',
    organization_unit: 'organization',
};

export async function listConfigurationDefinitions(scope: ConfigurationScope, signal?: AbortSignal, platformMode = false) {
    const response = await apiClient.get<ApiCollection<ConfigurationDefinition>>(
        `${platformMode ? platformConfigurationBase : configurationBase(scope)}/definitions`,
        { signal },
    );
    return response.data.data.map(validateDefinition);
}

export async function listConfigurationEntries(
    scope: ConfigurationScope,
    filters: { search?: string; owner?: string; page: number; per_page?: number },
    signal?: AbortSignal,
    platformTarget?: PlatformConfigurationTarget,
): Promise<ConfigurationEntryPage> {
    const response = await apiClient.get<{
        data: ConfigurationEntry[];
        meta: ConfigurationEntryPage['meta'];
        existing_keys: string[];
    }>(
        `${configurationScopeBase(scope, platformTarget)}/entries`,
        {
            params: {
                search: filters.search || undefined,
                owner: filters.owner || undefined,
                page: filters.page,
                per_page: filters.per_page ?? 25,
            },
            signal,
        },
    );
    return {
        ...response.data,
        data: response.data.data.map(validateEntry),
        existing_keys: response.data.existing_keys.map(assertCanonicalConfigurationKey),
    };
}

export async function exportGlobalConfiguration(signal?: AbortSignal): Promise<ConfigurationTransferDocument> {
    const response = await apiClient.get<ApiResource<ConfigurationTransferDocument>>(
        `${platformConfigurationBase}/export`,
        { signal },
    );
    return response.data.data;
}

export async function previewGlobalConfigurationImport(document: ConfigurationTransferDocument): Promise<ConfigurationImportPreview> {
    const response = await apiClient.post<ApiResource<ConfigurationImportPreview>>(
        `${platformConfigurationBase}/import/preview`,
        { document },
    );
    return response.data.data;
}

export async function applyGlobalConfigurationImport(
    document: ConfigurationTransferDocument,
    confirmationDigest: string,
    reason: string,
): Promise<ConfigurationImportResult> {
    const response = await apiClient.post<ApiResource<ConfigurationImportResult>>(
        `${platformConfigurationBase}/import/apply`,
        { document, confirmation_digest: confirmationDigest, reason },
    );
    return response.data.data;
}

export async function getGlobalConfigurationImpact(key: string, signal?: AbortSignal): Promise<ConfigurationGlobalImpact> {
    assertCanonicalConfigurationKey(key);
    const response = await apiClient.get<ApiResource<ConfigurationGlobalImpact>>(
        `${platformConfigurationBase}/global/entries/${encodeURIComponent(key)}/impact`,
        { signal },
    );
    return response.data.data;
}

export async function createConfigurationEntry(scope: ConfigurationScope, key: string, value: unknown, platformTarget?: PlatformConfigurationTarget) {
    assertCanonicalConfigurationKey(key);
    const response = await apiClient.post<ApiResource<ConfigurationEntry>>(
        `${configurationScopeBase(scope, platformTarget)}/entries`,
        { key, value },
    );
    return validateEntry(response.data.data);
}

export async function updateConfigurationEntry(scope: ConfigurationScope, entry: ConfigurationEntry, value: unknown, platformTarget?: PlatformConfigurationTarget) {
    assertCanonicalConfigurationKey(entry.key);
    const response = await apiClient.put<ApiResource<ConfigurationEntry>>(
        `${configurationScopeBase(scope, platformTarget)}/entries/${encodeURIComponent(entry.key)}`,
        { expected_version: entry.row_version, value },
    );
    return validateEntry(response.data.data);
}

export async function deleteConfigurationEntry(scope: ConfigurationScope, entry: ConfigurationEntry, platformTarget?: PlatformConfigurationTarget) {
    assertCanonicalConfigurationKey(entry.key);
    await apiClient.delete(`${configurationScopeBase(scope, platformTarget)}/entries/${encodeURIComponent(entry.key)}`, {
        data: { expected_version: entry.row_version },
    });
}

export async function listConfigurationHistory(
    scope: ConfigurationScope,
    key: string,
    page = 1,
    signal?: AbortSignal,
    platformTarget?: PlatformConfigurationTarget,
): Promise<ConfigurationRevisionPage> {
    assertCanonicalConfigurationKey(key);
    const response = await apiClient.get<ConfigurationRevisionPage>(
        `${configurationScopeBase(scope, platformTarget)}/entries/${encodeURIComponent(key)}/history`,
        { params: { page, per_page: 20 }, signal },
    );
    return response.data;
}

export async function rollbackConfigurationEntry(
    scope: ConfigurationScope,
    entry: ConfigurationEntry,
    revisionId: number,
    reason: string,
    platformTarget?: PlatformConfigurationTarget,
): Promise<ConfigurationEntry | null> {
    assertCanonicalConfigurationKey(entry.key);
    const response = await apiClient.post<ApiResource<ConfigurationEntry | null>>(
        `${configurationScopeBase(scope, platformTarget)}/entries/${encodeURIComponent(entry.key)}/rollback`,
        { revision_id: revisionId, expected_version: entry.row_version, reason },
    );
    return response.data.data === null ? null : validateEntry(response.data.data);
}

export async function listPlatformConfigurationOrganizationTargets(
    tenantId: number,
    { search, page, perPage, signal }: { search: string; page: number; perPage: number; signal: AbortSignal },
) {
    const response = await apiClient.get<ApiCollection<ConfigurationOrganizationTarget>>(
        `/api/v1/platform/configuration-targets/tenants/${tenantId}/organization-units`,
        { params: { search: search || undefined, page, per_page: perPage }, signal },
    );
    return { data: response.data.data, meta: response.data.meta };
}

export async function getPlatformConfigurationOrganizationTarget(
    tenantId: number,
    organizationUnitId: number,
    signal?: AbortSignal,
): Promise<ConfigurationOrganizationTarget> {
    const response = await apiClient.get<ApiResource<ConfigurationOrganizationTarget>>(
        `/api/v1/platform/configuration-targets/tenants/${tenantId}/organization-units/${organizationUnitId}`,
        { signal },
    );
    return response.data.data;
}

const canonicalConfigurationKeyPattern = /^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)+$/;

function assertCanonicalConfigurationKey(value: unknown): string {
    if (typeof value !== 'string' || !canonicalConfigurationKeyPattern.test(value)) {
        throw new Error('Configuration contract mismatch: the server returned a non-canonical setting key. Deploy matching backend/frontend builds and reconcile legacy configuration data.');
    }
    return value;
}

function validateDefinition(definition: ConfigurationDefinition): ConfigurationDefinition {
    assertCanonicalConfigurationKey(definition.key);
    if (!Number.isSafeInteger(definition.version) || definition.version < 1) {
        throw new Error(`Configuration contract mismatch: [${definition.key}] has an invalid definition version.`);
    }
    if (typeof definition.inherit_organization_hierarchy !== 'boolean') {
        throw new Error(`Configuration contract mismatch: [${definition.key}] is missing its hierarchy-inheritance policy.`);
    }
    return definition;
}

function validateEntry(entry: ConfigurationEntry): ConfigurationEntry {
    assertCanonicalConfigurationKey(entry.key);
    return entry;
}

function configurationBase(scope: ConfigurationScope): string {
    return scope === 'global' ? platformConfigurationBase : tenantConfigurationBase;
}

function configurationScopeBase(scope: ConfigurationScope, platformTarget?: PlatformConfigurationTarget): string {
    if (scope === 'global') return `${platformConfigurationBase}/global`;
    if (platformTarget) {
        if (scope === 'tenant') return `${platformConfigurationBase}/tenants/${platformTarget.tenant_id}`;
        if (!platformTarget.organization_unit_id) throw new Error('An organization-unit target is required.');
        return `${platformConfigurationBase}/tenants/${platformTarget.tenant_id}/organizations/${platformTarget.organization_unit_id}`;
    }
    return `${tenantConfigurationBase}/${scopePath[scope]}`;
}
