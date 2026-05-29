import { DataTable } from '../../../shared/components/data/DataTable';
import type { SubItem } from '../types/vehicleService.types';

type SubItemsTableProps = {
    items: SubItem[];
};

export function SubItemsTable({ items }: SubItemsTableProps) {
    return (
        <DataTable
            dark
            columns={[
                { header: 'Crew ID', key: 'crewId' },
                { header: 'Crew Name', key: 'name' },
                { header: 'Allow', key: 'allow' },
            ]}
            getRowKey={(row) => row.crewId}
            rows={items}
        />
    );
}
