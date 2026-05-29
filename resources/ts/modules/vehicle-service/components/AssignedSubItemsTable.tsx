import { DataTable } from '../../../shared/components/data/DataTable';
import type { AssignedSubItem } from '../types/vehicleService.types';

type AssignedSubItemsTableProps = {
    items: AssignedSubItem[];
};

export function AssignedSubItemsTable({ items }: AssignedSubItemsTableProps) {
    return (
        <DataTable
            columns={[
                { header: 'Employee Name', key: 'employeeName', render: (row) => <span className="font-semibold text-slate-600">{row.employeeName}</span> },
                { header: 'Service Item', key: 'serviceItem' },
                { header: 'Sub Item', key: 'subItem' },
                { header: 'Sub Item ID', key: 'subItemId' },
                { header: 'Incentive Amt', key: 'incentiveAmount', render: (row) => <span className="font-bold text-slate-950">{row.incentiveAmount}</span> },
            ]}
            getRowKey={(row) => `${row.employeeName}-${row.subItemId}`}
            rows={items}
        />
    );
}
