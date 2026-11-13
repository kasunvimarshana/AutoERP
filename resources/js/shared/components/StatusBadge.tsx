import type { ReactNode } from 'react';
import { humanize } from '@/shared/utils/object';

const positive = new Set([
    'active', 'approved', 'posted', 'paid', 'received', 'available', 'completed',
    'settled', 'disbursed', 'verified', 'fully_paid', 'success',
]);
const negative = new Set([
    'inactive', 'cancelled', 'void', 'voided', 'reversed', 'blacklisted', 'closed',
    'rejected', 'failed', 'overdue', 'expired',
]);
const informational = new Set([
    'submitted', 'processing', 'in_progress', 'open', 'inspected', 'in_custody',
    'reserved', 'invoiced', 'dispatched',
]);
const neutral = new Set([
    'draft', 'pending', 'pending_approval', 'unverified',
]);

export function StatusBadge({ status, label }: { status?: string | null; label?: ReactNode }) {
    return <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold shadow-2xs ${statusBadgeClassName(status)}`}>{label ?? humanize(status)}</span>;
}

export function statusBadgeClassName(status?: string | null): string {
    const normalized = status?.toLowerCase();

    return normalized && positive.has(normalized)
        ? 'bg-emerald-100 text-emerald-800 border border-emerald-200/80'
        : normalized && negative.has(normalized)
            ? 'bg-rose-100 text-rose-800 border border-rose-200/80'
            : normalized && informational.has(normalized)
                ? 'bg-blue-100 text-blue-800 border border-blue-200/80'
                : normalized && neutral.has(normalized)
                    ? 'bg-slate-100 text-slate-700 border border-slate-200/80'
                    : 'bg-amber-100 text-amber-800 border border-amber-200/80';
}
