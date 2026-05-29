import { Badge } from '../ui/Badge';
import type { StatusTone } from '../../types/common.types';

const toneMap: Record<string, StatusTone> = {
    active: 'success',
    cancelled: 'danger',
    closed: 'dark',
    draft: 'default',
    paid: 'success',
    pending: 'warning',
    posted: 'info',
    unpaid: 'warning',
};

type StatusBadgeProps = {
    status: string;
};

export function StatusBadge({ status }: StatusBadgeProps) {
    return <Badge tone={toneMap[status.toLowerCase()] ?? 'default'}>{status}</Badge>;
}
