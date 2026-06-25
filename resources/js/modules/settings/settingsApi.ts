import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type {
    ConfigurationDefinition,
    ConfigurationEntry,
    ConfigurationPanelMode,
    ConfigurationRevision,
    ConfigurationScope,
} from './settingsTypes';

const configurationBase = '/api/v1/configuration';
const platformDefaultsBase = '/api/v1/platform/defaults';
const scopePath: Record<Exclude<ConfigurationScope, 'global'>, string> = {
    tenant: 'tenant',
    organization_unit: 'organization',
};

function entryBase(scope: ConfigurationScope): string {
    return scope === 'global'
        ? platformDefaultsBase
        : `${configurationBase}/${scopePath[scope]}`;
}

export async function listConfigurationDefinitions(
    mode: ConfigurationPanelMode,
    signal?: AbortSignal,
) {
    const base = mode === 'platform' ? platformDefaultsBase : configurationBase;
    const response = await apiClient.get<ApiCollection<ConfigurationDefinition>>(
        `${base}/definitions`,
        { signal },
    );
    return response.data.data;
}

export async function listConfigurationEntries(
    scope: ConfigurationScope,
    prefix: string,
    page: number,
    signal?: AbortSignal,
) {
    const response = await apiClient.get<ApiCollection<ConfigurationEntry>>(
        `${entryBase(scope)}/entries`,
        { params: { prefix: prefix || undefined, page, per_page: 25 }, signal },
    );
    return response.data;
}

export async function listConfigurationHistory(
    scope: ConfigurationScope,
    key: string,
    page: number,
    signal?: AbortSignal,
) {
    const response = await apiClient.get<ApiCollection<ConfigurationRevision>>(
        `${entryBase(scope)}/entries/${encodeURIComponent(key)}/history`,
        { params: { page, per_page: 20 }, signal },
    );
    return response.data;
}

export async function createConfigurationEntry(
    scope: ConfigurationScope,
    key: string,
    value: unknown,
) {
    const response = await apiClient.post<ApiResource<ConfigurationEntry>>(
        `${entryBase(scope)}/entries`,
        { key, value },
    );
    return response.data.data;
}

export async function updateConfigurationEntry(
    scope: ConfigurationScope,
    entry: ConfigurationEntry,
    value: unknown,
) {
    const response = await apiClient.put<ApiResource<ConfigurationEntry>>(
        `${entryBase(scope)}/entries/${encodeURIComponent(entry.key)}`,
        { expected_version: entry.row_version, value },
    );
    return response.data.data;
}

export async function deleteConfigurationEntry(
    scope: ConfigurationScope,
    entry: ConfigurationEntry,
) {
    await apiClient.delete(
        `${entryBase(scope)}/entries/${encodeURIComponent(entry.key)}`,
        { data: { expected_version: entry.row_version } },
    );
}
