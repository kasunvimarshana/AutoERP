import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import type { SupplierStatus } from '../types/supplier.types';

const labels: Record<SupplierStatus, string> = {
    active: 'Active',
    archived: 'Archived',
    blocked: 'Blocked',
    draft: 'Draft',
    inactive: 'Inactive',
    pending_approval: 'Pending Approval',
    suspended: 'Suspended',
};

export function SupplierStatusBadge({ status }: { status: SupplierStatus }) {
    return <StatusBadge status={labels[status] ?? status} />;
}
