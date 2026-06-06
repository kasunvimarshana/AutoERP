import { humanize } from '@/shared/utils/object';

const positive = new Set(['active', 'approved', 'posted', 'paid', 'received', 'available', 'open']);
const negative = new Set(['inactive', 'cancelled', 'void', 'reversed', 'blacklisted', 'closed']);

export function StatusBadge({ status }: { status?: string | null }) {
    const color = status && positive.has(status)
        ? 'bg-emerald-100 text-emerald-700'
        : status && negative.has(status)
            ? 'bg-rose-100 text-rose-700'
            : 'bg-amber-100 text-amber-800';
    return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${color}`}>{humanize(status)}</span>;
}
