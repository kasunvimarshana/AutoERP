import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type {
    DomainVerificationChallenge,
    TenantDocument,
    TenantDomain,
    TenantPage,
    TenantPlan,
    TenantPlanPage,
    TenantPlanRevision,
    TenantRecord,
    TenantSubscription,
    TenantSubscriptionReadiness,
    TenantOnboardingReadiness,
    TenantOnboardingProvisionResult,
} from './tenantTypes';

export interface TenantListParams {
    page?: number;
    per_page?: number;
    search?: string;
    status?: string;
}

export async function listPlatformTenants(params: TenantListParams, signal?: AbortSignal): Promise<TenantPage> {
    const response = await apiClient.get<ApiCollection<TenantRecord>>('/api/v1/platform/tenants', { params, signal });
    if (!response.data.meta) throw new Error('Tenant pagination metadata is missing.');
    return { data: response.data.data, meta: response.data.meta };
}

export async function getPlatformTenant(id: number, signal?: AbortSignal): Promise<TenantRecord> {
    const response = await apiClient.get<ApiResource<TenantRecord>>(`/api/v1/platform/tenants/${id}`, { signal });
    return response.data.data;
}

export async function createPlatformTenant(payload: FormData): Promise<TenantRecord> {
    const response = await apiClient.post<ApiResource<TenantRecord>>('/api/v1/platform/tenants', payload);
    return response.data.data;
}

export async function updatePlatformTenant(id: number, payload: FormData): Promise<TenantRecord> {
    const requestPayload = withMethodOverride(payload, 'PATCH');
    const response = await apiClient.post<ApiResource<TenantRecord>>(`/api/v1/platform/tenants/${id}`, requestPayload);
    return response.data.data;
}

export async function changeTenantStatus(id: number, action: 'activate' | 'suspend' | 'deactivate' | 'archive', expectedVersion: number, reason: string): Promise<TenantRecord> {
    const response = await apiClient.patch<ApiResource<TenantRecord>>(`/api/v1/platform/tenants/${id}/${action}`, { expected_version: expectedVersion, reason });
    return response.data.data;
}


export async function getTenantOnboardingReadiness(tenantId: number, signal?: AbortSignal): Promise<TenantOnboardingReadiness> {
    const response = await apiClient.get<ApiResource<TenantOnboardingReadiness>>(`/api/v1/platform/tenants/${tenantId}/onboarding/readiness`, { signal });
    return response.data.data;
}

export async function provisionTenantOnboarding(tenant: TenantRecord, initialAdminEmail: string): Promise<TenantOnboardingProvisionResult> {
    const response = await apiClient.post<ApiResource<TenantOnboardingProvisionResult>>(`/api/v1/platform/tenants/${tenant.id}/onboarding/provision`, {
        expected_version: tenant.row_version,
        initial_admin_email: initialAdminEmail,
    });
    return response.data.data;
}

export async function getTenantSubscription(tenantId: number, signal?: AbortSignal): Promise<TenantSubscription | null> {
    const response = await apiClient.get<ApiResource<TenantSubscription | null>>(`/api/v1/platform/tenants/${tenantId}/subscription`, { signal });
    return response.data.data;
}

export async function getTenantSubscriptionReadiness(tenantId: number, planRevisionId: number, signal?: AbortSignal): Promise<TenantSubscriptionReadiness> {
    const response = await apiClient.get<ApiResource<TenantSubscriptionReadiness>>(`/api/v1/platform/tenants/${tenantId}/subscription/readiness/${planRevisionId}`, { signal });
    return response.data.data;
}

export async function assignTenantSubscription(tenant: TenantRecord, payload: {
    tenant_plan_revision_id: number;
    status: 'trial' | 'active';
    starts_at?: string | null;
    trial_ends_at?: string | null;
    ends_at?: string | null;
}): Promise<TenantSubscription> {
    const response = await apiClient.put<ApiResource<TenantSubscription>>(`/api/v1/platform/tenants/${tenant.id}/subscription`, {
        ...payload,
        expected_tenant_version: tenant.row_version,
    });
    return response.data.data;
}

export async function listPlatformTenantDomains(tenantId: number, signal?: AbortSignal): Promise<TenantDomain[]> {
    const response = await apiClient.get<ApiCollection<TenantDomain>>(`/api/v1/platform/tenants/${tenantId}/domains`, { signal });
    return response.data.data;
}

export async function createPlatformTenantDomain(tenantId: number, domain: string): Promise<TenantDomain> {
    const response = await apiClient.post<ApiResource<TenantDomain>>(`/api/v1/platform/tenants/${tenantId}/domains`, { domain });
    return response.data.data;
}

export async function requestPlatformDomainVerification(tenantId: number, domain: TenantDomain): Promise<{ data: TenantDomain; challenge: DomainVerificationChallenge }> {
    const response = await apiClient.post<{ data: TenantDomain; challenge: DomainVerificationChallenge }>(`/api/v1/platform/tenants/${tenantId}/domains/${domain.id}/verification-challenge`, { expected_version: domain.row_version });
    return response.data;
}

export async function verifyPlatformTenantDomain(tenantId: number, domain: TenantDomain): Promise<TenantDomain> {
    const response = await apiClient.post<ApiResource<TenantDomain>>(`/api/v1/platform/tenants/${tenantId}/domains/${domain.id}/verify`, { expected_version: domain.row_version });
    return response.data.data;
}

export async function changePlatformTenantDomain(tenantId: number, domain: TenantDomain, action: 'primary' | 'disable'): Promise<TenantDomain> {
    const response = await apiClient.patch<ApiResource<TenantDomain>>(`/api/v1/platform/tenants/${tenantId}/domains/${domain.id}/${action}`, { expected_version: domain.row_version });
    return response.data.data;
}

export async function deletePlatformTenantDomain(tenantId: number, domain: TenantDomain): Promise<void> {
    await apiClient.delete(`/api/v1/platform/tenants/${tenantId}/domains/${domain.id}`, { data: { expected_version: domain.row_version } });
}

export async function getTenantProfile(signal?: AbortSignal): Promise<TenantRecord> {
    const response = await apiClient.get<ApiResource<TenantRecord>>('/api/v1/tenant/profile', { signal });
    return response.data.data;
}

export async function updateTenantProfile(payload: FormData): Promise<TenantRecord> {
    const requestPayload = withMethodOverride(payload, 'PATCH');
    const response = await apiClient.post<ApiResource<TenantRecord>>('/api/v1/tenant/profile', requestPayload);
    return response.data.data;
}

export async function listTenantDomains(signal?: AbortSignal): Promise<TenantDomain[]> {
    const response = await apiClient.get<ApiCollection<TenantDomain>>('/api/v1/tenant/domains', { signal });
    return response.data.data;
}

export async function createTenantDomain(domain: string): Promise<TenantDomain> {
    const response = await apiClient.post<ApiResource<TenantDomain>>('/api/v1/tenant/domains', { domain });
    return response.data.data;
}

export async function requestDomainVerification(domain: TenantDomain): Promise<{ data: TenantDomain; challenge: DomainVerificationChallenge }> {
    const response = await apiClient.post<{ data: TenantDomain; challenge: DomainVerificationChallenge }>(`/api/v1/tenant/domains/${domain.id}/verification-challenge`, { expected_version: domain.row_version });
    return response.data;
}

export async function verifyTenantDomain(domain: TenantDomain): Promise<TenantDomain> {
    const response = await apiClient.post<ApiResource<TenantDomain>>(`/api/v1/tenant/domains/${domain.id}/verify`, { expected_version: domain.row_version });
    return response.data.data;
}

export async function changeTenantDomain(domain: TenantDomain, action: 'primary' | 'disable'): Promise<TenantDomain> {
    const response = await apiClient.patch<ApiResource<TenantDomain>>(`/api/v1/tenant/domains/${domain.id}/${action}`, { expected_version: domain.row_version });
    return response.data.data;
}

export async function deleteTenantDomain(domain: TenantDomain): Promise<void> {
    await apiClient.delete(`/api/v1/tenant/domains/${domain.id}`, { data: { expected_version: domain.row_version } });
}

export async function listTenantDocuments(signal?: AbortSignal): Promise<TenantDocument[]> {
    const response = await apiClient.get<ApiCollection<TenantDocument>>('/api/v1/tenant/documents', { signal });
    return response.data.data;
}

export async function createTenantDocument(payload: FormData): Promise<TenantDocument> {
    const response = await apiClient.post<ApiResource<TenantDocument>>('/api/v1/tenant/documents', payload);
    return response.data.data;
}

export async function updateTenantDocument(id: number, payload: FormData): Promise<TenantDocument> {
    const requestPayload = withMethodOverride(payload, 'PATCH');
    const response = await apiClient.post<ApiResource<TenantDocument>>(`/api/v1/tenant/documents/${id}`, requestPayload);
    return response.data.data;
}

export async function deleteTenantDocument(document: TenantDocument): Promise<void> {
    await apiClient.delete(`/api/v1/tenant/documents/${document.id}`, { data: { expected_version: document.row_version } });
}

export async function downloadTenantDocument(document: TenantDocument): Promise<void> {
    const response = await apiClient.get<Blob>(`/api/v1/tenant/documents/${document.id}/download`, { responseType: 'blob' });
    const url = URL.createObjectURL(response.data);
    const link = window.document.createElement('a');
    link.href = url;
    link.download = document.original_filename;
    window.document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1_000);
}

export async function listTenantPlans(params: { page?: number; per_page?: number; search?: string; is_active?: boolean }, signal?: AbortSignal): Promise<TenantPlanPage> {
    const response = await apiClient.get<ApiCollection<TenantPlan>>('/api/v1/platform/tenant-plans', { params, signal });
    if (!response.data.meta) throw new Error('Tenant plan pagination metadata is missing.');
    return { data: response.data.data, meta: response.data.meta };
}

export async function createTenantPlan(payload: Record<string, unknown>): Promise<TenantPlan> {
    const response = await apiClient.post<ApiResource<TenantPlan>>('/api/v1/platform/tenant-plans', payload);
    return response.data.data;
}

export async function updateTenantPlan(plan: TenantPlan, payload: Record<string, unknown>): Promise<TenantPlan> {
    const response = await apiClient.patch<ApiResource<TenantPlan>>(`/api/v1/platform/tenant-plans/${plan.id}`, { ...payload, expected_version: plan.row_version });
    return response.data.data;
}

export async function deactivateTenantPlan(plan: TenantPlan): Promise<TenantPlan> {
    const response = await apiClient.patch<ApiResource<TenantPlan>>(`/api/v1/platform/tenant-plans/${plan.id}/deactivate`, { expected_version: plan.row_version });
    return response.data.data;
}

export async function activateTenantPlan(plan: TenantPlan): Promise<TenantPlan> {
    const response = await apiClient.patch<ApiResource<TenantPlan>>(`/api/v1/platform/tenant-plans/${plan.id}/activate`, { expected_version: plan.row_version });
    return response.data.data;
}

export async function listTenantPlanRevisions(planId: number, signal?: AbortSignal): Promise<TenantPlanRevision[]> {
    const response = await apiClient.get<ApiCollection<TenantPlanRevision>>(`/api/v1/platform/tenant-plans/${planId}/revisions`, { signal });
    return response.data.data;
}

function withMethodOverride(source: FormData, method: 'PATCH'): FormData {
    const payload = new FormData();
    source.forEach((value, key) => payload.append(key, value));
    payload.set('_method', method);
    return payload;
}
