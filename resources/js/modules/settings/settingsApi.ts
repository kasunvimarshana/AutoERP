import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export interface ConfigurationEntry extends Record<string, unknown> {
    key: string;
    value: unknown;
    source?: string | null;
    description?: string | null;
    scope?: string | null;
    tenant_id?: number | null;
    organization_unit_id?: number | null;
    updated_at?: string | null;
}

export async function listConfigurationEntries(params: ListParams, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<ConfigurationEntry>>(endpoints.configuration, { params, signal });
    return response.data;
}

export async function createConfigurationEntry(payload: Record<string, unknown>) {
    const response = await apiClient.post<ApiResource<ConfigurationEntry>>(endpoints.configuration, payload);
    return response.data.data;
}
