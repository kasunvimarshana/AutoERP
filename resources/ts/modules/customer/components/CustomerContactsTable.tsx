import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import type { CustomerContact } from '../types/customer.types';

export function CustomerContactsTable({ contacts }: { contacts: CustomerContact[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Name', key: 'name' },
                { header: 'Role', key: 'role' },
                { header: 'Email', key: 'email' },
                { header: 'Phone', key: 'phone' },
                { header: 'Primary', key: 'isPrimary', render: (row) => <StatusBadge status={row.isPrimary ? 'Yes' : 'No'} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={contacts}
        />
    );
}
