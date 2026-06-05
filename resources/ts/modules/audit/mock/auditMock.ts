import type { AuditRecord } from '../types/audit.types';

export const auditRecords: AuditRecord[] = [
    { actor: 'System', entity: 'JobCard', event: 'Mock audit capture', id: 'audit-001', time: 'Today 09:10' },
];
