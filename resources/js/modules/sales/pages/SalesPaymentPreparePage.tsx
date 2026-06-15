import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { prepareSalesPayment } from '../salesApi';
import { CustomerLookupSelect, SalesInvoiceLookupSelect } from '../components/SalesLookups';

interface AllocationDraft {
    invoice: NamedResource;
    amount: string;
}

export default function SalesPaymentPreparePage() {
    const [customer, setCustomer] = useState<NamedResource | null>(null);
    const [invoice, setInvoice] = useState<NamedResource | null>(null);
    const [allocationAmount, setAllocationAmount] = useState('0.000000');
    const [allocations, setAllocations] = useState<AllocationDraft[]>([]);
    const [paymentDate, setPaymentDate] = useState(businessDateInputValue());
    const [amount, setAmount] = useState('0.000000');
    const [reference, setReference] = useState('');
    const [prepared, setPrepared] = useState<Record<string, unknown> | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const columns: DataColumn<AllocationDraft>[] = [
        { key: 'invoice', header: 'Invoice', render: (row) => row.invoice.code ?? row.invoice.name },
        { key: 'amount', header: 'Allocation', render: (row) => row.amount },
        { key: 'actions', header: 'Actions', render: (row) => <Button type="button" variant="danger" onClick={() => setAllocations((current) => current.filter((item) => item.invoice.id !== row.invoice.id))}>Remove</Button> },
    ];

    return (
        <>
            <ContentHeader title="Prepare customer receipt" description="Sales prepares a Payment DTO only; Payment remains the persistence owner." />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                if (submitting) return;
                setSubmitting(true);
                setError(null);
                try {
                    setPrepared(await prepareSalesPayment({
                        payment_date: paymentDate,
                        amount,
                        customer_id: customer?.id,
                        reference_number: reference || undefined,
                        allocations: allocations.map((row) => ({
                            invoice_id: row.invoice.id,
                            allocated_amount: row.amount,
                            allocation_date: paymentDate,
                        })),
                    }));
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}>
                <ErrorAlert error={error} />
                <Panel title="Receipt details">
                    <div className="grid gap-4 md:grid-cols-4">
                        <CustomerLookupSelect value={customer} onChange={setCustomer} />
                        <Input type="date" label="Payment date" value={paymentDate} onChange={(event) => setPaymentDate(event.target.value)} />
                        <DecimalInput label="Receipt amount" value={amount} onChange={(event) => setAmount(event.target.value)} />
                        <Input label="Reference" value={reference} onChange={(event) => setReference(event.target.value)} />
                    </div>
                </Panel>
                <Panel title="Invoice allocations">
                    <div className="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px_auto]">
                        <SalesInvoiceLookupSelect value={invoice} onChange={setInvoice} />
                        <DecimalInput label="Allocate amount" value={allocationAmount} onChange={(event) => setAllocationAmount(event.target.value)} />
                        <div className="self-end"><Button type="button" variant="secondary" disabled={!invoice} onClick={() => {
                            if (!invoice) return;
                            setAllocations((current) => [...current.filter((row) => row.invoice.id !== invoice.id), { invoice, amount: allocationAmount }]);
                            setInvoice(null);
                            setAllocationAmount('0.000000');
                        }}>Add allocation</Button></div>
                    </div>
                    <div className="mt-4"><DataTable rows={allocations} columns={columns} rowKey={(row) => row.invoice.id} emptyMessage="No invoice allocations added. Unallocated receipts are also supported." /></div>
                </Panel>
                {prepared && <Panel title="Prepared Payment DTO"><p className="text-sm text-slate-700">Type: <strong>{String(prepared.paymentType ?? prepared.payment_type ?? 'customer receipt')}</strong></p><p className="text-sm text-slate-700">Direction: <strong>{String(prepared.direction ?? 'inbound')}</strong></p></Panel>}
                <div className="flex justify-end"><Button type="submit" loading={submitting}>Prepare receipt</Button></div>
            </form>
        </>
    );
}
