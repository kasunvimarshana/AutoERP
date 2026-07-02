import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type {
    DomainVerificationChallenge,
    TenantDocument,
    TenantDocumentPage,
    TenantDomain,
    TenantDomainPage,
    TenantOnboardingProvisionResult,
    TenantOnboardingReadiness,
    TenantPage,
    PlatformTenantTarget,
    PlatformTenantTargetPurpose,
    TenantPlan,
    TenantPlanCapabilities,
    TenantPlanPage,
    TenantPlanRevision,
    TenantRecord,
    TenantSubscription,
    TenantSubscriptionRevision,
    TenantSubscriptionContractStatus,
    TenantSubscriptionReadiness,
} from './tenantTypes';

export interface TenantListParams {
    page?: number;
    per_page?: number;
    search?: string;
    status?: string;
    onboarding_status?: string;
    domain_operational_status?: string;
    subscription_state?: string;
    subscription_effective_status?: string;
    plan_id?: number;
    expires_within_days?: number;
}

export interface PageParams {
    page?: number;
    per_page?: number;
}

const platformTenantTargetPath: Record<PlatformTenantTargetPurpose, string> = {
    configuration: 'configuration-targets',
    audit: 'audit-targets',
    health: 'health-targets',
};

export async function listPlatformTenantTargets(
    purpose: PlatformTenantTargetPurpose,
    { search, page, perPage, signal }: { search: string; page: number; perPage: number; signal: AbortSignal },
) {
    const response = await apiClient.get<ApiCollection<PlatformTenantTarget>>(
        `/api/v1/platform/${platformTenantTargetPath[purpose]}/tenants`,
        { params: { search: search || undefined, page, per_page: perPage }, signal },
    );
    return { data: response.data.data, meta: response.data.meta };
}

export async function getPlatformTenantTarget(
    purpose: PlatformTenantTargetPurpose,
    id: number,
    signal?: AbortSignal,
): Promise<PlatformTenantTarget> {
    const response = await apiClient.get<ApiResource<PlatformTenantTarget>>(
        `/api/v1/platform/${platformTenantTargetPath[purpose]}/tenants/${id}`,
        { signal },
    );
    return response.data.data;
}

export interface TenantSubscriptionAssignmentPayload {
    tenant_plan_revision_id: number;
    contract_status: TenantSubscriptionContractStatus;
    starts_at?: string | null;
    trial_ends_at?: string | null;
    ends_at?: string | null;
    reason?: string | null;
}

export async function listPlatformTenants(params: TenantListParams, signal?: AbortSignal): Promise<TenantPage> {
    const response = await apiClient.get<ApiCollection<TenantRecord>>('/api/v1/platform/tenants', { params, signal });
    return pageFromResponse(response.data, 'Tenant');
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
    const response = await apiClient.post<ApiResource<TenantRecord>>(
        `/api/v1/platform/tenants/${id}`,
        withMethodOverride(payload, 'PATCH'),
    );
    return response.data.data;
}

export async function changeTenantStatus(
    id: number,
    action: 'activate' | 'suspend' | 'deactivate' | 'archive',
    expectedVersion: number,
    reason: string,
): Promise<TenantRecord> {
    const response = await apiClient.patch<ApiResource<TenantRecord>>(`/api/v1/platform/tenants/${id}/${action}`, {
        expected_version: expectedVersion,
        reason,
    });
    return response.data.data;
}

export async function getTenantOnboardingReadiness(
    tenantId: number,
    signal?: AbortSignal,
): Promise<TenantOnboardingReadiness> {
    const response = await apiClient.get<ApiResource<TenantOnboardingReadiness>>(
        `/api/v1/platform/tenants/${tenantId}/onboarding/readiness`,
        { signal },
    );
    return response.data.data;
}

export interface TenantOnboardingProvisionPayload {
    firstName: string;
    lastName?: string | null;
    email: string;
    password: string;
    passwordConfirmation: string;
}

export async function provisionTenantOnboarding(
    tenant: TenantRecord,
    administrator: TenantOnboardingProvisionPayload,
): Promise<TenantOnboardingProvisionResult> {
    const response = await apiClient.post<ApiResource<TenantOnboardingProvisionResult>>(
        `/api/v1/platform/tenants/${tenant.id}/onboarding/provision`,
        {
            expected_version: tenant.row_version,
            initial_admin_first_name: administrator.firstName,
            initial_admin_last_name: administrator.lastName ?? null,
            initial_admin_email: administrator.email,
            initial_admin_password: administrator.password,
            initial_admin_password_confirmation: administrator.passwordConfirmation,
        },
    );
    return response.data.data;
}

export async function getTenantSubscription(tenantId: number, signal?: AbortSignal): Promise<TenantSubscription | null> {
    const response = await apiClient.get<ApiResource<TenantSubscription | null>>(
        `/api/v1/platform/tenants/${tenantId}/subscription`,
        { signal },
    );
    return response.data.data;
}

export async function listTenantSubscriptionHistory(
    tenantId: number,
    params: PageParams,
    signal?: AbortSignal,
): Promise<{ data: TenantSubscriptionRevision[]; meta: TenantPage['meta'] }> {
    const response = await apiClient.get<ApiCollection<TenantSubscriptionRevision>>(
        `/api/v1/platform/tenants/${tenantId}/subscription/history`,
        { params, signal },
    );
    return pageFromResponse(response.data, 'Subscription history');
}

export async function getTenantSubscriptionReadiness(
    tenantId: number,
    planRevisionId: number,
    signal?: AbortSignal,
): Promise<TenantSubscriptionReadiness> {
    const response = await apiClient.get<ApiResource<TenantSubscriptionReadiness>>(
        `/api/v1/platform/tenants/${tenantId}/subscription/readiness/${planRevisionId}`,
        { signal },
    );
    return response.data.data;
}

export async function assignTenantSubscription(
    tenant: TenantRecord,
    payload: TenantSubscriptionAssignmentPayload,
): Promise<TenantSubscription> {
    return changeSubscription(tenant.id, 'assign', {
        ...payload,
        expected_tenant_version: tenant.row_version,
        expected_subscription_version: tenant.current_subscription?.row_version ?? null,
    });
}

export async function renewTenantSubscription(
    tenant: TenantRecord,
    current: TenantSubscription,
    payload: TenantSubscriptionAssignmentPayload,
): Promise<TenantSubscription> {
    return changeSubscription(tenant.id, 'renew', {
        ...payload,
        expected_tenant_version: tenant.row_version,
        expected_subscription_version: current.row_version,
    });
}

export async function extendTenantSubscription(
    tenant: TenantRecord,
    current: TenantSubscription,
    endsAt: string,
    reason?: string | null,
): Promise<TenantSubscription> {
    return changeSubscription(tenant.id, 'extend', {
        expected_tenant_version: tenant.row_version,
        expected_subscription_version: current.row_version,
        ends_at: endsAt,
        reason,
    });
}

export async function correctTenantSubscription(
    tenant: TenantRecord,
    current: TenantSubscription,
    payload: Required<Pick<TenantSubscriptionAssignmentPayload, 'tenant_plan_revision_id' | 'contract_status' | 'starts_at' | 'reason'>>
        & Pick<TenantSubscriptionAssignmentPayload, 'trial_ends_at' | 'ends_at'>,
): Promise<TenantSubscription> {
    return changeSubscription(tenant.id, 'correct', {
        ...payload,
        expected_tenant_version: tenant.row_version,
        expected_subscription_version: current.row_version,
    });
}

export async function cancelTenantSubscription(
    tenant: TenantRecord,
    current: TenantSubscription,
    reason: string,
): Promise<TenantSubscription> {
    return changeSubscription(tenant.id, 'cancel', {
        expected_tenant_version: tenant.row_version,
        expected_subscription_version: current.row_version,
        reason,
    });
}

export async function listPlatformTenantDomains(
    tenantId: number,
    params: PageParams = {},
    signal?: AbortSignal,
): Promise<TenantDomainPage> {
    const response = await apiClient.get<ApiCollection<TenantDomain>>(`/api/v1/platform/tenants/${tenantId}/domains`, {
        params,
        signal,
    });
    return pageFromResponse(response.data, 'Tenant domain');
}

export async function createPlatformTenantDomain(tenantId: number, domain: string): Promise<TenantDomain> {
    const response = await apiClient.post<ApiResource<TenantDomain>>(`/api/v1/platform/tenants/${tenantId}/domains`, { domain });
    return response.data.data;
}

export async function requestPlatformDomainVerification(
    tenantId: number,
    domain: TenantDomain,
): Promise<{ data: TenantDomain; challenge: DomainVerificationChallenge }> {
    const response = await apiClient.post<{ data: TenantDomain; challenge: DomainVerificationChallenge }>(
        `/api/v1/platform/tenants/${tenantId}/domains/${domain.id}/verification-challenge`,
        { expected_version: domain.row_version },
    );
    return response.data;
}

export async function verifyPlatformTenantDomain(tenantId: number, domain: TenantDomain): Promise<TenantDomain> {
    const response = await apiClient.post<ApiResource<TenantDomain>>(
        `/api/v1/platform/tenants/${tenantId}/domains/${domain.id}/verify`,
        { expected_version: domain.row_version },
    );
    return response.data.data;
}

export async function changePlatformTenantDomain(
    tenantId: number,
    domain: TenantDomain,
    action: 'primary' | 'disable',
): Promise<TenantDomain> {
    const response = await apiClient.patch<ApiResource<TenantDomain>>(
        `/api/v1/platform/tenants/${tenantId}/domains/${domain.id}/${action}`,
        { expected_version: domain.row_version },
    );
    return response.data.data;
}

export async function deletePlatformTenantDomain(tenantId: number, domain: TenantDomain): Promise<void> {
    await apiClient.delete(`/api/v1/platform/tenants/${tenantId}/domains/${domain.id}`, {
        data: { expected_version: domain.row_version },
    });
}

export async function getTenantProfile(signal?: AbortSignal): Promise<TenantRecord> {
    const response = await apiClient.get<ApiResource<TenantRecord>>('/api/v1/tenant/profile', { signal });
    return response.data.data;
}

export async function updateTenantProfile(payload: FormData): Promise<TenantRecord> {
    const response = await apiClient.post<ApiResource<TenantRecord>>(
        '/api/v1/tenant/profile',
        withMethodOverride(payload, 'PATCH'),
    );
    return response.data.data;
}

export async function listTenantDomains(
    params: PageParams = {},
    signal?: AbortSignal,
): Promise<TenantDomainPage> {
    const response = await apiClient.get<ApiCollection<TenantDomain>>('/api/v1/tenant/domains', { params, signal });
    return pageFromResponse(response.data, 'Tenant domain');
}

export async function createTenantDomain(domain: string): Promise<TenantDomain> {
    const response = await apiClient.post<ApiResource<TenantDomain>>('/api/v1/tenant/domains', { domain });
    return response.data.data;
}

export async function requestDomainVerification(
    domain: TenantDomain,
): Promise<{ data: TenantDomain; challenge: DomainVerificationChallenge }> {
    const response = await apiClient.post<{ data: TenantDomain; challenge: DomainVerificationChallenge }>(
        `/api/v1/tenant/domains/${domain.id}/verification-challenge`,
        { expected_version: domain.row_version },
    );
    return response.data;
}

export async function verifyTenantDomain(domain: TenantDomain): Promise<TenantDomain> {
    const response = await apiClient.post<ApiResource<TenantDomain>>(`/api/v1/tenant/domains/${domain.id}/verify`, {
        expected_version: domain.row_version,
    });
    return response.data.data;
}

export async function changeTenantDomain(domain: TenantDomain, action: 'primary' | 'disable'): Promise<TenantDomain> {
    const response = await apiClient.patch<ApiResource<TenantDomain>>(`/api/v1/tenant/domains/${domain.id}/${action}`, {
        expected_version: domain.row_version,
    });
    return response.data.data;
}

export async function deleteTenantDomain(domain: TenantDomain): Promise<void> {
    await apiClient.delete(`/api/v1/tenant/domains/${domain.id}`, { data: { expected_version: domain.row_version } });
}

export async function listTenantDocuments(
    params: PageParams = {},
    signal?: AbortSignal,
): Promise<TenantDocumentPage> {
    const response = await apiClient.get<ApiCollection<TenantDocument>>('/api/v1/tenant/documents', { params, signal });
    return pageFromResponse(response.data, 'Tenant document');
}

export async function createTenantDocument(payload: FormData): Promise<TenantDocument> {
    const response = await apiClient.post<ApiResource<TenantDocument>>('/api/v1/tenant/documents', payload);
    return response.data.data;
}

export async function updateTenantDocument(id: number, payload: FormData): Promise<TenantDocument> {
    const response = await apiClient.post<ApiResource<TenantDocument>>(
        `/api/v1/tenant/documents/${id}`,
        withMethodOverride(payload, 'PATCH'),
    );
    return response.data.data;
}

export async function deleteTenantDocument(document: TenantDocument): Promise<void> {
    await apiClient.delete(`/api/v1/tenant/documents/${document.id}`, {
        data: { expected_version: document.row_version },
    });
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


export async function listSubscriptionPlans(
    params: { page?: number; per_page?: number; search?: string },
    signal?: AbortSignal,
): Promise<TenantPlanPage> {
    const response = await apiClient.get<ApiCollection<TenantPlan>>('/api/v1/platform/subscription-plans', { params, signal });
    return pageFromResponse(response.data, 'Subscription plan');
}

export async function getSubscriptionPlan(planId: number, signal?: AbortSignal): Promise<TenantPlan> {
    const response = await apiClient.get<ApiResource<TenantPlan>>(`/api/v1/platform/subscription-plans/${planId}`, { signal });
    return response.data.data;
}

export async function listSubscriptionPlanRevisions(planId: number, signal?: AbortSignal): Promise<TenantPlanRevision[]> {
    const response = await apiClient.get<ApiCollection<TenantPlanRevision>>(
        `/api/v1/platform/subscription-plans/${planId}/revisions`,
        { signal },
    );
    return response.data.data;
}

export async function listTenantPlanAssignments(
    planId: number,
    params: PageParams,
    signal?: AbortSignal,
): Promise<TenantPage> {
    const response = await apiClient.get<ApiCollection<TenantRecord>>(
        `/api/v1/platform/tenant-plans/${planId}/tenants`,
        { params, signal },
    );
    return pageFromResponse(response.data, 'Assigned tenant');
}

export async function getTenantPlanCapabilities(signal?: AbortSignal): Promise<TenantPlanCapabilities> {
    const response = await apiClient.get<ApiResource<TenantPlanCapabilities>>('/api/v1/platform/tenant-plans/capabilities', { signal });
    return response.data.data;
}

export async function listTenantPlans(
    params: { page?: number; per_page?: number; search?: string; is_active?: boolean },
    signal?: AbortSignal,
): Promise<TenantPlanPage> {
    const response = await apiClient.get<ApiCollection<TenantPlan>>('/api/v1/platform/tenant-plans', { params, signal });
    return pageFromResponse(response.data, 'Tenant plan');
}

export async function getTenantPlan(planId: number, signal?: AbortSignal): Promise<TenantPlan> {
    const response = await apiClient.get<ApiResource<TenantPlan>>(`/api/v1/platform/tenant-plans/${planId}`, { signal });
    return response.data.data;
}

export async function createTenantPlan(payload: Record<string, unknown>): Promise<TenantPlan> {
    const response = await apiClient.post<ApiResource<TenantPlan>>('/api/v1/platform/tenant-plans', payload);
    return response.data.data;
}

export async function updateTenantPlan(plan: TenantPlan, payload: Record<string, unknown>): Promise<TenantPlan> {
    const response = await apiClient.patch<ApiResource<TenantPlan>>(`/api/v1/platform/tenant-plans/${plan.id}`, {
        ...payload,
        expected_version: plan.row_version,
    });
    return response.data.data;
}

export async function deactivateTenantPlan(plan: TenantPlan): Promise<TenantPlan> {
    const response = await apiClient.patch<ApiResource<TenantPlan>>(`/api/v1/platform/tenant-plans/${plan.id}/deactivate`, {
        expected_version: plan.row_version,
    });
    return response.data.data;
}

export async function activateTenantPlan(plan: TenantPlan): Promise<TenantPlan> {
    const response = await apiClient.patch<ApiResource<TenantPlan>>(`/api/v1/platform/tenant-plans/${plan.id}/activate`, {
        expected_version: plan.row_version,
    });
    return response.data.data;
}

export async function listTenantPlanRevisions(planId: number, signal?: AbortSignal): Promise<TenantPlanRevision[]> {
    const response = await apiClient.get<ApiCollection<TenantPlanRevision>>(
        `/api/v1/platform/tenant-plans/${planId}/revisions`,
        { signal },
    );
    return response.data.data;
}

async function changeSubscription(
    tenantId: number,
    action: 'assign' | 'renew' | 'extend' | 'correct' | 'cancel',
    payload: Record<string, unknown>,
): Promise<TenantSubscription> {
    const response = await apiClient.post<ApiResource<TenantSubscription>>(
        `/api/v1/platform/tenants/${tenantId}/subscription/${action}`,
        payload,
    );
    return response.data.data;
}

function pageFromResponse<T>(response: ApiCollection<T>, label: string): { data: T[]; meta: TenantPage['meta'] } {
    if (!response.meta) throw new Error(`${label} pagination metadata is missing.`);
    return { data: response.data, meta: response.meta };
}

function withMethodOverride(source: FormData, method: 'PATCH'): FormData {
    const payload = new FormData();
    source.forEach((value, key) => payload.append(key, value));
    payload.set('_method', method);
    return payload;
}
