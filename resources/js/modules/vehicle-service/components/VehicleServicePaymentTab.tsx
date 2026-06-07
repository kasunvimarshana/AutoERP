import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import type { VehicleServiceJob } from '../vehicleServiceTypes';

export default function VehicleServicePaymentTab({ job }: { job: VehicleServiceJob }) {
    return (
        <div className="space-y-5">
            <div className="flex justify-end">
                <Link to={`/vehicle-service/jobs/${job.id}/payment`}><Button>Prepare payment</Button></Link>
            </div>
            <DataTable
                rows={job.payment_links ?? []}
                rowKey={(link) => link.id}
                emptyMessage="No linked payments. Payment creation and allocation remain owned by Payment."
                columns={[
                    { key: 'payment', header: 'Payment', render: (link) => <Link className="text-sky-700 hover:underline" to={`/payments/${link.payment_id}`}>{link.payment_number ?? 'Payment'}</Link> },
                    { key: 'invoice', header: 'Invoice', render: (link) => link.invoice_id ? <Link className="text-sky-700 hover:underline" to={`/invoices/${link.invoice_id}`}>{link.invoice_number ?? 'Invoice'}</Link> : '-' },
                    { key: 'amount', header: 'Allocated amount', render: (link) => link.allocated_amount },
                    { key: 'status', header: 'Status', render: (link) => link.status },
                ]}
            />
        </div>
    );
}
