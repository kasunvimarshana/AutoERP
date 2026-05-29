import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import type { SupplierUserAccess } from '../types/supplier.types';

export function SupplierUserAccessPanel({ access }: { access: SupplierUserAccess[] }) {
    if (!access.length) {
        return (
            <div className="space-y-4">
                <EmptyState description="Supplier can remain without a user account. Link or invite access only when portal access is explicitly needed." title="No user access linked" />
                <div className="flex flex-wrap gap-2">
                    <Button variant="secondary">Link Existing User</Button>
                    <Button variant="secondary">Create / Invite User Access</Button>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <DataTable
                columns={[
                    { header: 'User', key: 'userName' },
                    { header: 'Email', key: 'email' },
                    { header: 'Primary', key: 'isPrimary', render: (row) => <StatusBadge status={row.isPrimary ? 'Primary' : 'Secondary'} /> },
                    { header: 'Invited', key: 'invitedAt' },
                    { header: 'Last Login', key: 'lastLogin' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                ]}
                getRowKey={(row) => row.id}
                rows={access}
            />
            <div className="flex flex-wrap gap-2">
                <Button variant="secondary">Link Existing User</Button>
                <Button variant="secondary">Deactivate Access</Button>
                <Button variant="secondary">Unlink Access</Button>
            </div>
        </div>
    );
}
