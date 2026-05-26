import { apiClient, unwrapPaginated } from '../../api/client';
import type { ApiPaginatedEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type {
    TenantDomainFilters,
    TenantDomainRecord,
    TenantFilters,
    TenantPlanFilters,
    TenantPlanRecord,
    TenantRecord,
    TenantSettingFilters,
    TenantSettingRecord,
} from './types';

export const tenantAdminApi = {
    listTenants(filters: TenantFilters): Promise<PaginatedResult<TenantRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<TenantRecord>>('/tenants', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    listTenantPlans(filters: TenantPlanFilters): Promise<PaginatedResult<TenantPlanRecord>> {
        return apiClient.get<ApiPaginatedEnvelope<TenantPlanRecord>>('/tenant-plans', { query: toQuery(filters) }).then((payload) => unwrapPaginated(payload));
    },
    listTenantDomains(tenantId: number, filters: TenantDomainFilters): Promise<PaginatedResult<TenantDomainRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<TenantDomainRecord>>(`/tenants/${tenantId}/domains`, { query: toQuery(filters) })
            .then((payload) => unwrapPaginated(payload));
    },
    listTenantSettings(tenantId: number, filters: TenantSettingFilters): Promise<PaginatedResult<TenantSettingRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<TenantSettingRecord>>(`/tenants/${tenantId}/settings`, { query: toQuery(filters) })
            .then((payload) => unwrapPaginated(payload));
    },
};
