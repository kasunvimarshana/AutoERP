import { Link } from 'react-router-dom';
import { Button, LinkButton } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import type { VehicleServiceJob } from '../vehicleServiceTypes';

export default function VehicleServicePaymentTab({ job }: { job: VehicleServiceJob }) {
    const payableInvoices = (job.invoice_links ?? []).filter((link) => Boolean(link.can_receive_payment));

    return (
        <div className="space-y-5">
            <div className="flex justify-end">
                {payableInvoices.length > 0
                    ? <LinkButton to={`/vehicle-service/jobs/${job.id}/payment`}>Receive payment</LinkButton>
                    : <Button type="button" disabled>Receive payment</Button>}
            </div>
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <DetailGrid items={[
                    { label: 'Linked invoices', value: String((job.invoice_links ?? []).length) },
                    { label: 'Payable posted invoices', value: String(payableInvoices.length) },
                    {
                        label: 'Outstanding balance',
                        value: payableInvoices
                            .map((link) => `${link.invoice_number ?? 'Invoice'}: ${link.balance_due}`)
                            .join(', ') || '0.000000',
                    },
                ]} />
            </div>
            <DataTable
                rows={job.payment_links ?? []}
                rowKey={(link) => link.id}
                emptyMessage="No linked payments. Payments remain owned by the Payment module."
                columns={[
                    {
                        key: 'payment',
                        header: 'Payment',
                        render: (link) => (
                            <Link className="text-sky-700 hover:underline" to={`/payments/${link.payment_id}`}>
                                {link.payment_number ?? 'Payment'}
                            </Link>
                        ),
                    },
                    {
                        key: 'invoice',
                        header: 'Invoice',
                        render: (link) => link.invoice_id
                            ? <Link className="text-sky-700 hover:underline" to={`/invoices/${link.invoice_id}`}>{link.invoice_number ?? 'Invoice'}</Link>
                            : '-',
                    },
                    { key: 'method', header: 'Method', render: (link) => link.payment_method?.name ?? '-' },
                    { key: 'amount', header: 'Allocated amount', render: (link) => link.allocated_amount },
                    { key: 'posting', header: 'Posting', render: (link) => link.posting_status ?? '-' },
                    { key: 'allocation', header: 'Allocation', render: (link) => link.allocation_status ?? '-' },
                    { key: 'status', header: 'Link status', render: (link) => link.status },
                ]}
            />
        </div>
    );
}
