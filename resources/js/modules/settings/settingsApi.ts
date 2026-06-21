import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type {
    ConfigurationDefinition,
    ConfigurationEntry,
    ConfigurationScope,
} from './settingsTypes';

const configurationBase = '/api/v1/configuration';
const scopePath: Record<ConfigurationScope, string> = {
    global: 'global',
    tenant: 'tenant',
    organization_unit: 'organization',
};

export async function listConfigurationDefinitions(signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<ConfigurationDefinition>>(`${configurationBase}/definitions`, { signal });
    return response.data.data;
}

export async function listConfigurationEntries(scope: ConfigurationScope, prefix: string, page: number, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<ConfigurationEntry>>(
        `${configurationBase}/${scopePath[scope]}/entries`,
        { params: { prefix: prefix || undefined, page, per_page: 25 }, signal },
    );
    return response.data;
}

export async function createConfigurationEntry(scope: ConfigurationScope, key: string, value: unknown) {
    const response = await apiClient.post<ApiResource<ConfigurationEntry>>(
        `${configurationBase}/${scopePath[scope]}/entries`,
        { key, value },
    );
    return response.data.data;
}

export async function updateConfigurationEntry(scope: ConfigurationScope, entry: ConfigurationEntry, value: unknown) {
    const response = await apiClient.put<ApiResource<ConfigurationEntry>>(
        `${configurationBase}/${scopePath[scope]}/entries/${encodeURIComponent(entry.key)}`,
        { expected_version: entry.row_version, value },
    );
    return response.data.data;
}

export async function deleteConfigurationEntry(scope: ConfigurationScope, entry: ConfigurationEntry) {
    await apiClient.delete(`${configurationBase}/${scopePath[scope]}/entries/${encodeURIComponent(entry.key)}`, {
        data: { expected_version: entry.row_version },
    });
}
