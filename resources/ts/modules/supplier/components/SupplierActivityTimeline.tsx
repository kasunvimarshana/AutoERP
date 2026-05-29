import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';
import type { SupplierAuditEntry } from '../types/supplier.types';

export function SupplierActivityTimeline({ entries }: { entries: SupplierAuditEntry[] }) {
    return (
        <AuditTimeline
            events={entries.map((entry) => ({
                actor: entry.actor,
                description: entry.description,
                time: entry.time,
            }))}
        />
    );
}
