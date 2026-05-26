import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type { AuditLogFilters, AuditLogRecord } from './types';

export const auditApi = {
    listAuditLogs(filters: AuditLogFilters): Promise<PaginatedResult<AuditLogRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<AuditLogRecord>>('/audit-logs', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated(payload));
    },
    getAuditLog(auditLogId: number) {
        return apiClient.get<ApiResourceEnvelope<AuditLogRecord> | AuditLogRecord>(`/audit-logs/${auditLogId}`).then((payload) => unwrapResource(payload));
    },
};
