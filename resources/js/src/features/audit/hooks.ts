import { useQuery } from '@tanstack/react-query';
import { auditApi } from './api';
import type { AuditLogFilters } from './types';

const auditKeys = {
    all: ['audit-logs'] as const,
    lists: () => [...auditKeys.all, 'list'] as const,
    list: (filters: AuditLogFilters) => [...auditKeys.lists(), filters] as const,
    detail: (auditLogId: number) => [...auditKeys.all, 'detail', auditLogId] as const,
};

export function useAuditLogs(filters: AuditLogFilters) {
    return useQuery({
        queryKey: auditKeys.list(filters),
        queryFn: () => auditApi.listAuditLogs(filters),
    });
}

export function useAuditLog(auditLogId: number, enabled = true) {
    return useQuery({
        queryKey: auditKeys.detail(auditLogId),
        queryFn: () => auditApi.getAuditLog(auditLogId),
        enabled,
    });
}
