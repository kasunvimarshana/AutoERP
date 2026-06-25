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
    return response.data.data;
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
    return response.data;
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
    const response = await apiClient.get<ApiResource<ConfigurationGlobalImpact>>(
        `${platformConfigurationBase}/global/entries/${encodeURIComponent(key)}/impact`,
        { signal },
    );
    return response.data.data;
}

export async function createConfigurationEntry(scope: ConfigurationScope, key: string, value: unknown, platformTarget?: PlatformConfigurationTarget) {
    const response = await apiClient.post<ApiResource<ConfigurationEntry>>(
        `${configurationScopeBase(scope, platformTarget)}/entries`,
        { key, value },
    );
    return response.data.data;
}

export async function updateConfigurationEntry(scope: ConfigurationScope, entry: ConfigurationEntry, value: unknown, platformTarget?: PlatformConfigurationTarget) {
    const response = await apiClient.put<ApiResource<ConfigurationEntry>>(
        `${configurationScopeBase(scope, platformTarget)}/entries/${encodeURIComponent(entry.key)}`,
        { expected_version: entry.row_version, value },
    );
    return response.data.data;
}

export async function deleteConfigurationEntry(scope: ConfigurationScope, entry: ConfigurationEntry, platformTarget?: PlatformConfigurationTarget) {
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
    const response = await apiClient.post<ApiResource<ConfigurationEntry | null>>(
        `${configurationScopeBase(scope, platformTarget)}/entries/${encodeURIComponent(entry.key)}/rollback`,
        { revision_id: revisionId, expected_version: entry.row_version, reason },
    );
    return response.data.data;
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
