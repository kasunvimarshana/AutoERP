import { Badge } from '../../../shared/components/ui/Badge';
import type { CustomerStatus } from '../types/customer.types';

const tones: Record<CustomerStatus, 'danger' | 'info' | 'success' | 'warning'> = {
    active: 'success',
    archived: 'warning',
    blocked: 'danger',
    draft: 'info',
    inactive: 'warning',
    pending_approval: 'info',
    suspended: 'danger',
};

export function CustomerStatusBadge({ status }: { status: CustomerStatus }) {
    return <Badge tone={tones[status]}>{status.replace('_', ' ')}</Badge>;
}
