import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { ApiError, fieldError, toApiError, type ApiError as ApiErrorType } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { compareDecimalStrings, isPositiveDecimal, subtractDecimal, sumDecimals } from '@/shared/utils/decimal';
import { getInvoice, type Invoice } from '@/modules/invoice/invoiceApi';
import {
    createPurchasePayment,
    getPurchaseOrder,
    getPurchasePaymentContext,
    listOutstandingSupplierInvoices,
} from '../purchaseApi';
import { todayDate } from '../purchaseFormUtils';
import { CurrencyLookupSelect, SupplierLookupSelect } from './PurchaseLookups';
import {
    blankPaymentMethodRow,
    paymentRowsTotal,
    PurchasePaymentMethodsEditor,
    type PurchasePaymentMethodRow,
} from './PurchasePaymentMethodsEditor';

function balanceOf(invoice: Invoice): string {
    return String(invoice.balance_due ?? invoice.balance?.remaining_amount ?? invoice.grand_total ?? '0.000000');
}

export function PurchasePaymentPreparationForm() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const sourceLoaded = useRef(false);
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [paymentDate, setPaymentDate] = useState(todayDate());
    const [referenceNumber, setReferenceNumber] = useState('');
    const [amount, setAmount] = useState('0.000000');
    const [paymentRows, setPaymentRows] = useState<PurchasePaymentMethodRow[]>([blankPaymentMethodRow()]);
    const [invoices, setInvoices] = useState<Invoice[]>([]);
    const [allocations, setAllocations] = useState<Record<number, string>>({});
    const [sourceNotice, setSourceNotice] = useState<string | null>(null);
    const [error, setError] = useState<ApiErrorType | null>(null);
    const [busy, setBusy] = useState(false);
    const context = getPurchasePaymentContext;
    const paymentContext = usePaymentContext(context);
    const errorFor = (field: string) => fieldError(error ?? paymentContext.error, field);

    const allocated = useMemo(() => sumDecimals(Object.values(allocations).filter((value) => value.trim() !== '')), [allocations]);
    const unallocated = compareDecimalStrings(amount || '0.000000', allocated) > 0 ? subtractDecimal(amount || '0.000000', allocated) : '0.000000';
    const overAllocated = compareDecimalStrings(allocated, amount || '0.000000') > 0 ? subtractDecimal(allocated, amount || '0.000000') : '0.000000';
    const methodTotal = paymentRowsTotal(paymentRows);
    const methodsMatch = compareDecimalStrings(methodTotal, amount || '0.000000') === 0;

    useEffect(() => {
        if (sourceLoaded.current) return;
        sourceLoaded.current = true;

        const invoiceId = Number(searchParams.get('invoice_id') ?? searchParams.get('supplier_invoice_id'));
        const purchaseOrderId = Number(searchParams.get('purchase_order_id'));
        if (Number.isFinite(invoiceId) && invoiceId > 0) {
            setBusy(true);
            void getInvoice(invoiceId)
                .then((invoice) => {
                    const balance = balanceOf(invoice);
                    if (!isPositiveDecimal(balance)) {
                        setError(new ApiError('The selected supplier invoice has no payable balance.', 422));
                        return;
                    }
                    setInvoices([invoice]);
                    setAllocations({ [invoice.id]: balance });
                    setAmount(balance);
                    setPaymentRows([blankPaymentMethodRow(balance)]);
                    setSupplier(invoice.party ?? null);
                    setCurrency(invoice.currency ?? null);
                    setSourceNotice(`Loaded supplier invoice ${invoice.invoice_number ?? `#${invoice.id}`}.`);
                })
                .catch((requestError) => setError(toApiError(requestError)))
                .finally(() => setBusy(false));
        } else if (Number.isFinite(purchaseOrderId) && purchaseOrderId > 0) {
            setBusy(true);
            void getPurchaseOrder(purchaseOrderId)
                .then((order) => {
                    setSupplier(order.supplier ?? null);
                    setCurrency(order.currency ?? null);
                    setSourceNotice(`${order.purchase_order_number ?? `Purchase order #${order.id}`} is selected. Pay an outstanding supplier invoice generated from this purchase flow.`);
                    if (order.supplier?.id) {
                        void loadInvoicesFor(order.supplier, amount);
                    }
                })
                .catch((requestError) => setError(toApiError(requestError)))
                .finally(() => setBusy(false));
        }
        // Source query parameters should be consumed once when opening the page.
    }, [searchParams]);

    const loadInvoicesFor = async (selectedSupplier = supplier, paymentAmount = amount) => {
        if (!selectedSupplier) return;
        setBusy(true);
        setError(null);
        try {
            const response = await listOutstandingSupplierInvoices({
                supplier_id: selectedSupplier.id,
                per_page: 50,
            });
            setInvoices(response.data);
            setAllocations(defaultAllocations(response.data, paymentAmount));
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    const updateAmount = (next: string) => {
        setAmount(next);
        if (paymentRows.length === 1 && !isPositiveDecimal(paymentRows[0].amount)) {
            setPaymentRows([{ ...paymentRows[0], amount: next }]);
        }
    };

    const canCreate = Boolean(
        supplier?.id
        && isPositiveDecimal(amount)
        && paymentRows.every((row) => isPositiveDecimal(row.amount) && row.payment_method_id && row.source_account_id)
        && methodsMatch
        && compareDecimalStrings(overAllocated, '0.000000') === 0
        && Object.values(allocations).some(isPositiveDecimal)
        && !busy
    );

    const createPayment = async () => {
        if (!canCreate) return;
        setBusy(true);
        setError(null);
        try {
            const payment = await createPurchasePayment({
                payment_date: paymentDate,
                amount: amount || '0.000000',
                supplier_type: 'supplier',
                supplier_id: supplier?.id,
                currency_id: currency?.id,
                reference_number: referenceNumber || undefined,
                lines: paymentRows.map((row) => ({
                    amount: row.amount,
                    payment_method_id: row.payment_method_id ? Number(row.payment_method_id) : undefined,
                    source_account_id: row.source_account_id ? Number(row.source_account_id) : undefined,
                    reference: row.reference || undefined,
                    instrument_direction: 'issued',
                    external_bank_name: row.external_bank_name || undefined,
                    external_bank_branch: row.external_bank_branch || undefined,
                    instrument_number: row.instrument_number || undefined,
                    instrument_date: row.instrument_date || undefined,
                })),
                allocations: Object.entries(allocations)
                    .filter(([, value]) => isPositiveDecimal(value))
                    .map(([invoiceId, allocatedAmount]) => ({
                        invoice_id: Number(invoiceId),
                        allocated_amount: allocatedAmount,
                        allocation_date: paymentDate,
                    })),
            });
            navigate(`/payments/${payment.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? paymentContext.error} />
            {sourceNotice && <div className="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800">{sourceNotice}</div>}
            <Panel title="Payment Details">
                <div className="grid gap-4 md:grid-cols-2">
                    <SupplierLookupSelect value={supplier} onChange={(value) => { setSupplier(value); setInvoices([]); setAllocations({}); setSourceNotice(null); }} error={errorFor('supplier_id')} />
                    <CurrencyLookupSelect value={currency} onChange={setCurrency} error={errorFor('currency_id')} />
                    <Input label="Payment date" type="date" value={paymentDate} error={errorFor('payment_date')} onChange={(event) => setPaymentDate(event.target.value)} />
                    <Input label="Reference" value={referenceNumber} error={errorFor('reference_number')} onChange={(event) => setReferenceNumber(event.target.value)} />
                    <DecimalInput label="Payment total" value={amount} error={errorFor('amount')} onChange={(event) => updateAmount(event.target.value)} />
                    <div className="md:pt-7"><Button type="button" variant="secondary" loading={busy} disabled={!supplier} onClick={() => void loadInvoicesFor()}>Load outstanding invoices</Button></div>
                </div>
            </Panel>

            <Panel title="Payment Methods">
                <PurchasePaymentMethodsEditor
                    rows={paymentRows}
                    methods={paymentContext.data?.payment_methods ?? []}
                    accounts={paymentContext.data?.payment_accounts ?? []}
                    errorFor={errorFor}
                    onChange={setPaymentRows}
                />
                {!methodsMatch && <div className="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">Payment method rows must equal the payment total.</div>}
            </Panel>

            <Panel title="Invoice Allocations">
                <PaymentAllocationSummary amount={amount} allocated={allocated} unallocated={unallocated} overAllocated={overAllocated} />
                <div className="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                    <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>{['Invoice', 'Date', 'Status', 'Balance', 'Allocation'].map((header) => <th key={header} className="px-4 py-3 font-semibold">{header}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {invoices.map((invoice, index) => (
                                <tr key={invoice.id}>
                                    <td className="px-4 py-3"><Link className="font-semibold text-sky-700 hover:underline" to={`/invoices/${invoice.id}`}>{invoice.invoice_number ?? 'Invoice'}</Link></td>
                                    <td className="px-4 py-3">{invoice.invoice_date ?? '-'}</td>
                                    <td className="px-4 py-3">{invoice.status ?? '-'}</td>
                                    <td className="px-4 py-3 tabular-nums">{balanceOf(invoice)}</td>
                                    <td className="min-w-44 px-4 py-3"><DecimalInput value={allocations[invoice.id] ?? ''} error={errorFor(`allocations.${index}.allocated_amount`)} onChange={(event) => setAllocations({ ...allocations, [invoice.id]: event.target.value })} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {invoices.length === 0 && <div className="px-4 py-10 text-center text-sm text-slate-500">Select a supplier and load outstanding supplier invoices.</div>}
                </div>
            </Panel>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate('/purchase/payments')}>Cancel</Button>
                <Button type="button" loading={busy} disabled={!canCreate} onClick={() => void createPayment()}>Create payment draft</Button>
            </div>
        </div>
    );
}

function usePaymentContext(request: typeof getPurchasePaymentContext) {
    return useApi((signal) => request(signal), []);
}

function defaultAllocations(invoices: Invoice[], amount: string): Record<number, string> {
    let remaining = amount || '0.000000';
    const next: Record<number, string> = {};

    for (const invoice of invoices) {
        if (compareDecimalStrings(remaining, '0.000000') <= 0) break;
        const balance = balanceOf(invoice);
        const allocation = compareDecimalStrings(remaining, balance) > 0 ? balance : remaining;
        if (isPositiveDecimal(allocation)) {
            next[invoice.id] = allocation;
            remaining = subtractDecimal(remaining, allocation);
        }
    }

    return next;
}

function PaymentAllocationSummary({ amount, allocated, unallocated, overAllocated }: {
    amount: string;
    allocated: string;
    unallocated: string;
    overAllocated: string;
}) {
    return (
        <div className="grid gap-3 md:grid-cols-4">
            {[
                ['Payment Total', amount],
                ['Allocated', allocated],
                ['Unallocated', unallocated],
                ['Over-allocated', overAllocated],
            ].map(([label, value]) => (
                <div key={label} className={`rounded-lg border p-3 text-sm ${label === 'Over-allocated' && compareDecimalStrings(value, '0.000000') > 0 ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-slate-200 bg-white text-slate-700'}`}>
                    <span className="block text-xs font-semibold uppercase text-slate-500">{label}</span>
                    <strong className="mt-1 block tabular-nums text-slate-950">{value}</strong>
                </div>
            ))}
        </div>
    );
}
