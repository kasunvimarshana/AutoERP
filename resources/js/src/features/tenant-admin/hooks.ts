import { useQuery } from '@tanstack/react-query';
import { tenantAdminApi } from './api';
import type { TenantDomainFilters, TenantFilters, TenantPlanFilters, TenantSettingFilters } from './types';

const tenantAdminKeys = {
    all: ['tenant-admin'] as const,
    tenants: () => [...tenantAdminKeys.all, 'tenants'] as const,
    tenantList: (filters: TenantFilters) => [...tenantAdminKeys.tenants(), filters] as const,
    plans: () => [...tenantAdminKeys.all, 'plans'] as const,
    planList: (filters: TenantPlanFilters) => [...tenantAdminKeys.plans(), filters] as const,
    domains: (tenantId: number, filters: TenantDomainFilters) => [...tenantAdminKeys.all, 'domains', tenantId, filters] as const,
    settings: (tenantId: number, filters: TenantSettingFilters) => [...tenantAdminKeys.all, 'settings', tenantId, filters] as const,
};

export function useTenants(filters: TenantFilters) {
    return useQuery({
        queryKey: tenantAdminKeys.tenantList(filters),
        queryFn: () => tenantAdminApi.listTenants(filters),
    });
}

export function useTenantPlans(filters: TenantPlanFilters) {
    return useQuery({
        queryKey: tenantAdminKeys.planList(filters),
        queryFn: () => tenantAdminApi.listTenantPlans(filters),
    });
}

export function useTenantDomains(tenantId: number, filters: TenantDomainFilters, enabled = true) {
    return useQuery({
        queryKey: tenantAdminKeys.domains(tenantId, filters),
        queryFn: () => tenantAdminApi.listTenantDomains(tenantId, filters),
        enabled,
    });
}

export function useTenantSettings(tenantId: number, filters: TenantSettingFilters, enabled = true) {
    return useQuery({
        queryKey: tenantAdminKeys.settings(tenantId, filters),
        queryFn: () => tenantAdminApi.listTenantSettings(tenantId, filters),
        enabled,
    });
}
