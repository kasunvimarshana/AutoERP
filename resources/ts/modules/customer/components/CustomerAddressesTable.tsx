import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import type { CustomerAddress } from '../types/customer.types';

export function CustomerAddressesTable({ addresses }: { addresses: CustomerAddress[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Type', key: 'type' },
                { header: 'Address', key: 'line1', render: (row) => [row.line1, row.line2, row.city, row.country].filter(Boolean).join(', ') },
                { header: 'Primary', key: 'isPrimary', render: (row) => <StatusBadge status={row.isPrimary ? 'Yes' : 'No'} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={addresses}
        />
    );
}
