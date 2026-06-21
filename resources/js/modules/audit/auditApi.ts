import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type { AuditListFilters, AuditListResponse, AuditLogDetail } from './auditTypes';

export const auditApi = {
    list: (params: AuditListFilters, signal?: AbortSignal) =>
        apiClient.get<AuditListResponse>(endpoints.auditLogs, { params, signal }).then((response) => response.data),
    get: (id: number, signal?: AbortSignal) =>
        apiClient.get<ApiResource<AuditLogDetail>>(`${endpoints.auditLogs}/${id}`, { signal }).then((response) => response.data.data),
};
