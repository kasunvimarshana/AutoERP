import { useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import type { NamedResource } from '@/shared/types/common';
import { listInvoices, type Invoice } from '@/modules/invoice/invoiceApi';
import { preparePurchasePayment } from '../purchaseApi';
import { CurrencyLookupSelect, SupplierLookupSelect } from './PurchaseLookups';

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

function balanceOf(invoice: Invoice): string {
    return String(invoice.balance_due ?? invoice.balance?.balance_due ?? invoice.grand_total ?? '0.000000');
}

export function PurchasePaymentPreparationForm() {
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [paymentDate, setPaymentDate] = useState(today());
    const [referenceNumber, setReferenceNumber] = useState('');
    const [amount, setAmount] = useState('0.000000');
    const [invoices, setInvoices] = useState<Invoice[]>([]);
    const [allocations, setAllocations] = useState<Record<number, string>>({});
    const [prepared, setPrepared] = useState<Record<string, unknown> | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const errorFor = (field: string) => fieldError(error, field);

    const loadInvoices = async () => {
        if (!supplier) return;
        setBusy(true);
        setError(null);
        try {
            const response = await listInvoices({ invoice_type: 'purchase', direction: 'inbound', party_id: supplier.id, per_page: 50 });
            setInvoices(response.data);
            setAllocations(Object.fromEntries(response.data.map((invoice) => [invoice.id, balanceOf(invoice)])));
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    const prepare = async () => {
        setBusy(true);
        setError(null);
        setPrepared(null);
        try {
            setPrepared(await preparePurchasePayment({
                payment_date: paymentDate,
                amount: amount || '0.000000',
                supplier_type: 'supplier',
                supplier_id: supplier?.id,
                currency_id: currency?.id,
                reference_number: referenceNumber || undefined,
                allocations: Object.entries(allocations).filter(([, value]) => value && value !== '0' && value !== '0.000000').map(([invoiceId, allocatedAmount]) => ({
                    invoice_id: Number(invoiceId),
                    allocated_amount: allocatedAmount,
                    allocation_date: paymentDate,
                })),
            }));
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="space-y-5">
            <ErrorAlert error={error} />
            {prepared && <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Payment preparation returned successfully.</div>}
            <Panel title="Payment header">
                <div className="grid gap-4 md:grid-cols-4">
                    <SupplierLookupSelect value={supplier} onChange={(value) => { setSupplier(value); setInvoices([]); setAllocations({}); }} error={errorFor('supplier_id')} />
                    <CurrencyLookupSelect value={currency} onChange={setCurrency} error={errorFor('currency_id')} />
                    <Input label="Payment date" type="date" value={paymentDate} error={errorFor('payment_date')} onChange={(event) => setPaymentDate(event.target.value)} />
                    <Input label="Reference" value={referenceNumber} error={errorFor('reference_number')} onChange={(event) => setReferenceNumber(event.target.value)} />
                    <Input label="Payment amount" type="number" min="0.000001" step="0.000001" value={amount} error={errorFor('amount')} onChange={(event) => setAmount(event.target.value)} />
                    <div className="md:pt-7"><Button type="button" variant="secondary" loading={busy} disabled={!supplier} onClick={loadInvoices}>Load invoices</Button></div>
                </div>
            </Panel>
            <Panel title="Invoice allocations">
                <div className="overflow-x-auto rounded-lg border border-slate-200">
                    <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>{['Invoice', 'Date', 'Status', 'Balance', 'Allocation'].map((header) => <th key={header} className="px-4 py-3 font-semibold">{header}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {invoices.map((invoice, index) => <tr key={invoice.id}>
                                <td className="px-4 py-3">{invoice.invoice_number ?? 'Invoice'}</td>
                                <td className="px-4 py-3">{invoice.invoice_date ?? '-'}</td>
                                <td className="px-4 py-3">{invoice.status ?? '-'}</td>
                                <td className="px-4 py-3 tabular-nums">{balanceOf(invoice)}</td>
                                <td className="min-w-44 px-4 py-3"><Input type="number" min="0" step="0.000001" value={allocations[invoice.id] ?? ''} error={errorFor(`allocations.${index}.allocated_amount`)} onChange={(event) => setAllocations({ ...allocations, [invoice.id]: event.target.value })} /></td>
                            </tr>)}
                        </tbody>
                    </table>
                    {invoices.length === 0 && <div className="px-4 py-10 text-center text-sm text-slate-500">Select a supplier and load unpaid or partial invoices.</div>}
                </div>
            </Panel>
            <div className="flex justify-end"><Button type="button" loading={busy} onClick={prepare}>Prepare payment</Button></div>
        </div>
    );
}
