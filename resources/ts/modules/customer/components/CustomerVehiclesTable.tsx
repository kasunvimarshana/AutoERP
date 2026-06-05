import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import type { CustomerVehicle } from '../types/customer.types';

export function CustomerVehiclesTable({ vehicles }: { vehicles: CustomerVehicle[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Plate', key: 'plateNumber' },
                { header: 'Vehicle', key: 'make', render: (row) => `${row.year} ${row.make} ${row.model}` },
                { header: 'VIN', key: 'vin' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={vehicles}
        />
    );
}
