import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import type { SupplierContact } from '../types/supplier.types';

export function SupplierContactsTable({ contacts }: { contacts: SupplierContact[] }) {
    if (!contacts.length) {
        return <EmptyState description="Add procurement, accounts, or operations contacts after the supplier is saved." title="No supplier contacts" />;
    }

    return (
        <DataTable
            columns={[
                { header: 'Name', key: 'name' },
                { header: 'Designation', key: 'designation' },
                { header: 'Email', key: 'email' },
                { header: 'Phone', key: 'phone' },
                { header: 'Primary', key: 'isPrimary', render: (row) => <StatusBadge status={row.isPrimary ? 'Primary' : 'Secondary'} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={contacts}
        />
    );
}
