import type { ApiCollectionResponse } from '../../../services/api/apiResponse';
import { getStoredTenantId } from '../../../services/api/authTokenStorage';
import { httpClient } from '../../../services/api/httpClient';
import type { SettingRecord } from '../types/settings.types';

type BackendRecord = Record<string, unknown>;

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined || value === '' ? fallback : String(value);
}

function normalizeSetting(raw: BackendRecord): SettingRecord {
    return {
        area: asString(raw.group_name ?? raw.area ?? raw.group_id, 'Tenant'),
        groupId: asString(raw.group_id) || undefined,
        id: asString(raw.id),
        key: asString(raw.key, 'setting'),
        status: raw.deleted_at ? 'Inactive' : 'Active',
        updatedAt: asString(raw.updated_at) || undefined,
        value: asString(raw.value, 'Not configured'),
    };
}

export const settingsApi = {
    async list(): Promise<ApiCollectionResponse<SettingRecord>> {
        const tenantId = getStoredTenantId();
        if (!tenantId) {
            return { data: [] };
        }

        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/tenant/settings', { query: { tenant_id: Number(tenantId) } });
        return { ...response, data: response.data.map(normalizeSetting) };
    },
};
