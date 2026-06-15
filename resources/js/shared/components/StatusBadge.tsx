import { humanize } from '@/shared/utils/object';

const positive = new Set(['active', 'approved', 'posted', 'paid', 'received', 'available', 'completed']);
const negative = new Set(['inactive', 'cancelled', 'void', 'voided', 'reversed', 'blacklisted', 'closed', 'rejected', 'failed']);
const informational = new Set(['submitted', 'processing', 'in_progress', 'open']);
const neutral = new Set(['draft', 'pending', 'pending_approval']);

export function StatusBadge({ status }: { status?: string | null }) {
    const normalized = status?.toLowerCase();
    const color = normalized && positive.has(normalized)
        ? 'bg-emerald-100 text-emerald-700'
        : normalized && negative.has(normalized)
            ? 'bg-rose-100 text-rose-700'
            : normalized && informational.has(normalized)
                ? 'bg-blue-100 text-blue-700'
                : normalized && neutral.has(normalized)
                    ? 'bg-slate-100 text-slate-700'
                    : 'bg-amber-100 text-amber-800';
    return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${color}`}>{humanize(status)}</span>;
}
