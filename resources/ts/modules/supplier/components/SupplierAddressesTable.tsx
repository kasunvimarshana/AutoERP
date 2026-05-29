import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import type { SupplierAddress } from '../types/supplier.types';

export function SupplierAddressesTable({ addresses }: { addresses: SupplierAddress[] }) {
    if (!addresses.length) {
        return <EmptyState description="Registered, billing, and shipping addresses can be added after save." title="No supplier addresses" />;
    }

    return (
        <DataTable
            columns={[
                { header: 'Type', key: 'type' },
                { header: 'Address', key: 'line1' },
                { header: 'City', key: 'city' },
                { header: 'Country', key: 'country' },
                { header: 'Default', key: 'isDefault', render: (row) => <StatusBadge status={row.isDefault ? 'Default' : 'Optional'} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={addresses}
        />
    );
}
