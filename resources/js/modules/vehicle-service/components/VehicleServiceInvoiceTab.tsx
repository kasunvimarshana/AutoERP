import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { listBillableLines } from '../vehicleServiceApi';
import type { VehicleServiceJob, VehicleServiceJobLine } from '../vehicleServiceTypes';

export default function VehicleServiceInvoiceTab({ job }: { job: VehicleServiceJob }) {
    const result = useApi((signal) => listBillableLines(job.id, signal), [job.id]);
    const columns: DataColumn<VehicleServiceJobLine>[] = [
        { key: 'line', header: 'Line', render: (line) => line.line_number },
        { key: 'description', header: 'Description', render: (line) => line.description },
        { key: 'quantity', header: 'Quantity', render: (line) => line.quantity },
        { key: 'price', header: 'Unit price', render: (line) => line.unit_price },
        { key: 'total', header: 'Line total', render: (line) => line.line_total },
    ];

    return (
        <div className="space-y-5">
            <div className="flex justify-end">
                <Link to={`/vehicle-service/jobs/${job.id}/invoice`}><Button>Create invoice</Button></Link>
            </div>
            {result.loading ? <LoadingState /> : <DataTable rows={result.data ?? []} columns={columns} rowKey={(line) => line.id} emptyMessage="No billable lines." />}
            {(job.invoice_links ?? []).length > 0 && (
                <DataTable
                    rows={job.invoice_links ?? []}
                    rowKey={(link) => link.id}
                    columns={[
                        { key: 'invoice', header: 'Invoice', render: (link) => <Link className="text-sky-700 hover:underline" to={`/invoices/${link.invoice_id}`}>{link.invoice_number ?? 'Invoice'}</Link> },
                        { key: 'total', header: 'Total', render: (link) => link.invoice_total },
                        { key: 'balance', header: 'Balance', render: (link) => link.balance_due ?? '-' },
                        { key: 'status', header: 'Status', render: (link) => link.status },
                    ]}
                />
            )}
        </div>
    );
}
