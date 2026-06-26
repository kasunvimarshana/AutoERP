import { apiClient } from '@/shared/api/apiClient';
import type { ApiResource } from '@/shared/types/api';
import type {
    CreatePlatformOperatorPayload,
    PlatformAuditDetail,
    PlatformAuditFilters,
    PlatformAuditListResponse,
    PlatformHealthOverview,
    PlatformOperator,
    PlatformOperatorPage,
    PlatformSession,
    PlatformSessionPage,
    PlatformTenantHealthDetail,
} from './platformAdministrationTypes';

const PLATFORM_OPERATORS = '/api/v1/platform/operators';
const PLATFORM_AUTH = '/api/v1/platform/auth';
const PLATFORM_AUDIT = '/api/v1/platform/audit-logs';
const PLATFORM_HEALTH = '/api/v1/platform/health';

export interface PlatformOperatorListParams {
    search?: string;
    status?: string;
    page?: number;
    per_page?: number;
}

export const platformAdministrationApi = {
    listOperators: (params: PlatformOperatorListParams, signal?: AbortSignal) =>
        apiClient.get<PlatformOperatorPage>(PLATFORM_OPERATORS, { params, signal }).then((response) => response.data),

    getOperator: (operatorId: number, signal?: AbortSignal) =>
        apiClient.get<ApiResource<PlatformOperator>>(`${PLATFORM_OPERATORS}/${operatorId}`, { signal }).then((response) => response.data.data),

    createOperator: (payload: CreatePlatformOperatorPayload) =>
        apiClient.post<ApiResource<PlatformOperator>>(PLATFORM_OPERATORS, payload).then((response) => response.data.data),

    updateOperatorPermissions: (operator: PlatformOperator, permissions: string[]) =>
        apiClient.put<ApiResource<PlatformOperator>>(`${PLATFORM_OPERATORS}/${operator.id}/permissions`, {
            expected_version: operator.row_version,
            permissions,
        }).then((response) => response.data.data),

    changeOperatorStatus: (operator: PlatformOperator, status: 'active' | 'inactive', reason: string) =>
        apiClient.patch<ApiResource<PlatformOperator>>(`${PLATFORM_OPERATORS}/${operator.id}/${status === 'active' ? 'activate' : 'deactivate'}`, {
            expected_version: operator.row_version,
            reason,
        }).then((response) => response.data.data),

    resendOperatorInvitation: (operator: PlatformOperator) =>
        apiClient.post<ApiResource<PlatformOperator>>(`${PLATFORM_OPERATORS}/${operator.id}/invitation/resend`, {
            expected_version: operator.row_version,
        }).then((response) => response.data.data),

    revokeOperatorInvitation: (operator: PlatformOperator, reason: string) =>
        apiClient.delete<ApiResource<PlatformOperator>>(`${PLATFORM_OPERATORS}/${operator.id}/invitation`, {
            data: {
                expected_version: operator.row_version,
                reason,
            },
        }).then((response) => response.data.data),

    listSessions: (params: { operator_id?: number; page?: number; per_page?: number }, signal?: AbortSignal) =>
        apiClient.get<PlatformSessionPage>(`${PLATFORM_AUTH}/sessions`, { params, signal }).then((response) => response.data),

    revokeSession: (session: PlatformSession, reason: string) =>
        apiClient.delete<ApiResource<PlatformSession>>(`${PLATFORM_AUTH}/sessions/${session.id}`, { data: { reason } })
            .then((response) => response.data.data),

    revokeOperatorSessions: (operatorId: number, reason: string) =>
        apiClient.delete<{ revoked_count: number }>(`${PLATFORM_AUTH}/operators/${operatorId}/sessions`, { data: { reason } })
            .then((response) => response.data.revoked_count),

    recoverOperatorAccess: (operator: Pick<PlatformOperator, 'id' | 'row_version'>, reason: string) =>
        apiClient.post<ApiResource<PlatformOperator>>(`${PLATFORM_OPERATORS}/${operator.id}/security-recovery`, {
            expected_version: operator.row_version,
            reason,
        }).then((response) => response.data.data),

    listAudit: (params: PlatformAuditFilters, signal?: AbortSignal) =>
        apiClient.get<PlatformAuditListResponse>(PLATFORM_AUDIT, { params, signal }).then((response) => response.data),

    getAudit: (id: number, signal?: AbortSignal) =>
        apiClient.get<ApiResource<PlatformAuditDetail>>(`${PLATFORM_AUDIT}/${id}`, { signal }).then((response) => response.data.data),

    getHealth: (signal?: AbortSignal) =>
        apiClient.get<ApiResource<PlatformHealthOverview>>(PLATFORM_HEALTH, { signal }).then((response) => response.data.data),

    getTenantHealth: (tenantId: number, signal?: AbortSignal) =>
        apiClient.get<ApiResource<PlatformTenantHealthDetail>>(`${PLATFORM_HEALTH}/tenants/${tenantId}`, { signal })
            .then((response) => response.data.data),

    retryFailedDomains: (tenantId: number | null, reason: string, limit = 50) =>
        apiClient.post<ApiResource<{ requeued_count: number }>>(`${PLATFORM_HEALTH}/domains/retry-failed`, {
            tenant_id: tenantId,
            limit,
            reason,
        }).then((response) => response.data.data.requeued_count),

    retryOutbox: (eventUuid: string, reason: string) =>
        apiClient.post<ApiResource<{ requeued_count: number }>>(`${PLATFORM_HEALTH}/outbox/${eventUuid}/retry`, { reason })
            .then((response) => response.data.data.requeued_count),

    retryStorageCleanup: (jobId: number, reason: string) =>
        apiClient.post<ApiResource<{ requeued_count: number }>>(`${PLATFORM_HEALTH}/storage-cleanups/${jobId}/retry`, { reason })
            .then((response) => response.data.data.requeued_count),
};
