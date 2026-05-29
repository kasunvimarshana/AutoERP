import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Button } from '../../../shared/components/ui/Button';
import { DataTable } from '../../../shared/components/data/DataTable';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import type { CustomerUserAccess } from '../types/customer.types';

export function CustomerUserAccessPanel({ access }: { access: CustomerUserAccess[] }) {
    if (!access.length) {
        return (
            <div className="space-y-4">
                <EmptyState description="Customer can remain without a user account. Link access only when the business explicitly needs a portal login." title="No user access linked" />
                <Button variant="secondary">Link Optional User Access</Button>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <DataTable
                columns={[
                    { header: 'User', key: 'userName' },
                    { header: 'Email', key: 'email' },
                    { header: 'Last Login', key: 'lastLogin' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                ]}
                getRowKey={(row) => row.id}
                rows={access}
            />
            <Button variant="secondary">Deactivate User Access</Button>
        </div>
    );
}
