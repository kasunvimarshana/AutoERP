import { Badge } from '../../../shared/components/ui/Badge';
import type { CustomerStatus } from '../types/customer.types';

const tones: Record<CustomerStatus, 'danger' | 'info' | 'success' | 'warning'> = {
    active: 'success',
    blocked: 'danger',
    inactive: 'warning',
    pending: 'info',
};

export function CustomerStatusBadge({ status }: { status: CustomerStatus }) {
    return <Badge tone={tones[status]}>{status}</Badge>;
}
