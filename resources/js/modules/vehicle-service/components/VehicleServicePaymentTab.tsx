import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { compareDecimalStrings } from '@/shared/utils/decimal';
import type { VehicleServiceJob } from '../vehicleServiceTypes';

export default function VehicleServicePaymentTab({ job }: { job: VehicleServiceJob }) {
    const outstanding = (job.invoice_links ?? []).filter((link) => compareDecimalStrings(link.balance_due ?? '0', '0') > 0 && link.status === 'active');
    return (
        <div className="space-y-5">
            <div className="flex justify-end">
                {outstanding.length > 0
                    ? <Link to={`/vehicle-service/jobs/${job.id}/payment`}><Button>Receive payment</Button></Link>
                    : <Button type="button" disabled>Receive payment</Button>}
            </div>
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <DetailGrid items={[
                    { label: 'Linked invoices', value: String((job.invoice_links ?? []).length) },
                    { label: 'Outstanding invoices', value: String(outstanding.length) },
                    { label: 'Outstanding balance', value: outstanding.map((link) => `${link.invoice_number ?? 'Invoice'}: ${link.balance_due}`).join(', ') || '0.000000' },
                ]} />
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
