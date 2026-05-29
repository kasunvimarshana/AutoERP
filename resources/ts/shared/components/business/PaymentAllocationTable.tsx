import { DataTable } from '../data/DataTable';

const rows = [
    { amount: '$120.00', document: 'INV-1001', status: 'Preview' },
    { amount: '$80.00', document: 'INV-1002', status: 'Preview' },
];

export function PaymentAllocationTable() {
    return (
        <DataTable
            columns={[
                { header: 'Document', key: 'document' },
                { header: 'Amount', key: 'amount' },
                { header: 'Status', key: 'status' },
            ]}
            getRowKey={(row) => row.document}
            rows={rows}
        />
    );
}
