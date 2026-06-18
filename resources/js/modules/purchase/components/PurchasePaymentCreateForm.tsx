import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { ApiError, fieldError, toApiError, type ApiError as ApiErrorType } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { NamedResource } from '@/shared/types/common';
import { compareDecimalStrings, isPositiveDecimal, subtractDecimal, sumDecimals } from '@/shared/utils/decimal';
import { formatDate } from '@/shared/utils/formatDate';
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
import { PurchaseTabs, type PurchaseTabItem } from './PurchaseTabs';

type PaymentTab = 'details' | 'allocations' | 'methods';

const paymentTabs: PaymentTab[] = ['details', 'allocations', 'methods'];

function balanceOf(invoice: Invoice): string {
    return String(invoice.balance_due ?? invoice.balance?.remaining_amount ?? invoice.grand_total ?? '0.000000');
}

export function PurchasePaymentCreateForm() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const requestedTab = searchParams.get('tab') as PaymentTab | null;
    const activeTab = requestedTab && paymentTabs.includes(requestedTab) ? requestedTab : 'details';
    const sourceLoaded = useRef(false);
    const [supplier, setSupplierState] = useState<NamedResource | null>(null);
    const [currency, setCurrencyState] = useState<NamedResource | null>(null);
    const [paymentDate, setPaymentDate] = useState(todayDate());
    const [referenceNumber, setReferenceNumber] = useState('');
    const [amount, setAmount] = useState('0.000000');
    const [paymentRows, setPaymentRows] = useState<PurchasePaymentMethodRow[]>([blankPaymentMethodRow()]);
    const [allocations, setAllocations] = useState<Record<number, string>>({});
    const [allocatedInvoices, setAllocatedInvoices] = useState<Record<number, Invoice>>({});
    const [invoiceSearch, setInvoiceSearch] = useState('');
    const [invoicePage, setInvoicePage] = useState(1);
    const [sourceNotice, setSourceNotice] = useState<string | null>(null);
    const [error, setError] = useState<ApiErrorType | null>(null);
    const [busy, setBusy] = useState(false);
    const debouncedInvoiceSearch = useDebounce(invoiceSearch);
    const paymentContext = useApi((signal) => getPurchasePaymentContext(signal), []);
    const invoiceResult = useApi((signal) => listOutstandingSupplierInvoices({
        supplier_id: supplier?.id,
        search: debouncedInvoiceSearch || undefined,
        page: invoicePage,
        per_page: 10,
    }, signal), [supplier?.id, debouncedInvoiceSearch, invoicePage], Boolean(supplier?.id), true);
    const errorFor = (field: string) => fieldError(error ?? paymentContext.error ?? invoiceResult.error, field);

    const allocated = useMemo(() => sumDecimals(Object.values(allocations).filter(isPositiveDecimal)), [allocations]);
    const unallocated = compareDecimalStrings(amount || '0.000000', allocated) > 0 ? subtractDecimal(amount || '0.000000', allocated) : '0.000000';
    const overAllocated = compareDecimalStrings(allocated, amount || '0.000000') > 0 ? subtractDecimal(allocated, amount || '0.000000') : '0.000000';
    const methodTotal = paymentRowsTotal(paymentRows);
    const methodDifference = subtractDecimal(methodTotal, amount || '0.000000');
    const methodsMatch = compareDecimalStrings(methodTotal, amount || '0.000000') === 0;
    const hasAllocatedInvoices = Object.values(allocations).some(isPositiveDecimal);
    const currencyLocked = Object.values(allocatedInvoices).some((invoice) => isPositiveDecimal(allocations[invoice.id] ?? '0.000000') && invoice.currency?.id);
    const dirty = Boolean(supplier || referenceNumber || hasAllocatedInvoices || paymentRows.some((row) => row.amount || row.payment_method_id || row.source_account_id || row.reference));
    useUnsavedChanges(dirty && !busy);

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
                    setAllocations({ [invoice.id]: balance });
                    setAllocatedInvoices({ [invoice.id]: invoice });
                    setAmount(balance);
                    setPaymentRows([blankPaymentMethodRow(balance)]);
                    setSupplierState(invoice.party ?? null);
                    setCurrencyState(invoice.currency ?? null);
                    setSourceNotice(`Loaded supplier invoice ${invoice.invoice_number ?? `#${invoice.id}`}.`);
                })
                .catch((requestError) => setError(toApiError(requestError)))
                .finally(() => setBusy(false));
        } else if (Number.isFinite(purchaseOrderId) && purchaseOrderId > 0) {
            setBusy(true);
            void getPurchaseOrder(purchaseOrderId)
                .then((order) => {
                    setSupplierState(order.supplier ?? null);
                    setCurrencyState(order.currency ?? null);
                    setSourceNotice(`${order.purchase_order_number ?? `Purchase order #${order.id}`} is selected. Create payment from an eligible supplier invoice generated from this purchase flow.`);
                })
                .catch((requestError) => setError(toApiError(requestError)))
                .finally(() => setBusy(false));
        }
    }, [searchParams]);

    const updateAmount = (next: string) => {
        setAmount(next);
        if (paymentRows.length === 1 && (!isPositiveDecimal(paymentRows[0].amount) || compareDecimalStrings(paymentRows[0].amount, amount || '0.000000') === 0)) {
            setPaymentRows([{ ...paymentRows[0], amount: next }]);
        }
    };

    const setSupplier = (next: NamedResource | null) => {
        if (supplier?.id && next?.id !== supplier.id && hasAllocatedInvoices && !window.confirm('Changing supplier clears selected invoice allocations.')) {
            return;
        }
        setSupplierState(next);
        setInvoicePage(1);
        setInvoiceSearch('');
        setSourceNotice(null);
        if (next?.id !== supplier?.id) {
            setAllocations({});
            setAllocatedInvoices({});
            setCurrencyState(null);
        }
    };

    const setCurrency = (next: NamedResource | null) => {
        if (currencyLocked && next?.id !== currency?.id) {
            if (!window.confirm('Changing currency clears invoice allocations that established the current currency.')) return;
            setAllocations({});
            setAllocatedInvoices({});
        }
        setCurrencyState(next);
    };

    const updateAllocation = (invoice: Invoice, nextAmount: string) => {
        const invoiceCurrencyId = invoice.currency?.id;
        const allocatedCurrencyIds = Object.values(allocatedInvoices)
            .filter((row) => row.id !== invoice.id && isPositiveDecimal(allocations[row.id] ?? '0.000000') && row.currency?.id)
            .map((row) => row.currency?.id);
        if (isPositiveDecimal(nextAmount) && invoiceCurrencyId && allocatedCurrencyIds.length > 0 && !allocatedCurrencyIds.includes(invoiceCurrencyId)) {
            if (!window.confirm('This invoice uses a different currency. Clear existing allocations and continue?')) return;
            const nextAllocations = { [invoice.id]: nextAmount };
            setAllocations(nextAllocations);
            setAllocatedInvoices({ [invoice.id]: invoice });
            setCurrencyState(invoice.currency ?? null);
            syncAmountWithAllocations(nextAllocations);
            return;
        }

        const nextAllocations = { ...allocations, [invoice.id]: nextAmount };
        const nextAllocatedInvoices = { ...allocatedInvoices };
        if (isPositiveDecimal(nextAmount)) {
            nextAllocatedInvoices[invoice.id] = invoice;
            if (invoice.currency && (!currency || currency.id !== invoice.currency.id)) {
                setCurrencyState(invoice.currency);
            }
        } else {
            delete nextAllocatedInvoices[invoice.id];
        }
        setAllocations(nextAllocations);
        setAllocatedInvoices(nextAllocatedInvoices);
        syncAmountWithAllocations(nextAllocations);
    };

    const syncAmountWithAllocations = (nextAllocations: Record<number, string>) => {
        const nextAllocated = sumDecimals(Object.values(nextAllocations).filter(isPositiveDecimal));
        if (!isPositiveDecimal(amount) || compareDecimalStrings(amount, allocated) === 0) {
            setAmount(nextAllocated);
            if (paymentRows.length === 1 && (!isPositiveDecimal(paymentRows[0].amount) || compareDecimalStrings(paymentRows[0].amount, amount || '0.000000') === 0)) {
                setPaymentRows([{ ...paymentRows[0], amount: nextAllocated }]);
            }
        }
    };

    const canCreate = Boolean(
        supplier?.id
        && isPositiveDecimal(amount)
        && currency?.id
        && paymentRows.every((row) => isPositiveDecimal(row.amount) && row.payment_method_id && row.source_account_id)
        && methodsMatch
        && compareDecimalStrings(overAllocated, '0.000000') === 0
        && hasAllocatedInvoices
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
            navigate(`/payments/${payment.id}?from=purchase`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    const tabs: PurchaseTabItem[] = [
        { id: 'details', label: 'Payment Details', error: Boolean(errorFor('supplier_id') || errorFor('currency_id') || errorFor('payment_date') || errorFor('amount')) },
        { id: 'allocations', label: 'Invoice Allocations', count: Object.values(allocations).filter(isPositiveDecimal).length },
        { id: 'methods', label: 'Payment Methods', count: paymentRows.length },
    ];

    return (
        <div className="space-y-5">
            <PurchaseTabs tabs={tabs} activeTab={activeTab} />
            <ErrorAlert error={error ?? paymentContext.error ?? invoiceResult.error} />
            {sourceNotice && <div className="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800">{sourceNotice}</div>}
            <PaymentSummary amount={amount} allocated={allocated} unallocated={unallocated} methodTotal={methodTotal} methodDifference={methodDifference} overAllocated={overAllocated} />

            {activeTab === 'details' && (
                <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 className="mb-4 text-base font-semibold text-slate-950">Payment Details</h2>
                    <div className="grid gap-4 md:grid-cols-2">
                        <SupplierLookupSelect value={supplier} onChange={setSupplier} error={errorFor('supplier_id')} />
                        <CurrencyLookupSelect value={currency} onChange={setCurrency} error={errorFor('currency_id')} disabled={currencyLocked} />
                        <Input label="Payment date" type="date" value={paymentDate} error={errorFor('payment_date')} onChange={(event) => setPaymentDate(event.target.value)} />
                        <Input label="Reference" value={referenceNumber} error={errorFor('reference_number')} onChange={(event) => setReferenceNumber(event.target.value)} />
                        <DecimalInput label="Payment total" value={amount} error={errorFor('amount')} onChange={(event) => updateAmount(event.target.value)} />
                        {currencyLocked && <div className="self-end rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Currency is derived from selected supplier invoices.</div>}
                    </div>
                </section>
            )}

            {activeTab === 'allocations' && (
                <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 className="text-base font-semibold text-slate-950">Invoice Allocations</h2>
                            <p className="text-sm text-slate-500">Select eligible posted supplier invoices and enter allocation amounts.</p>
                        </div>
                        <div className="w-full sm:max-w-sm">
                            <Input label="Search invoices" type="search" value={invoiceSearch} onChange={(event) => { setInvoiceSearch(event.target.value); setInvoicePage(1); }} />
                        </div>
                    </div>
                    {!supplier ? (
                        <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm font-medium text-slate-600">Select a supplier to load outstanding invoices.</div>
                    ) : (
                        <>
                            <div className="overflow-hidden rounded-lg border border-slate-200">
                                <div className="hidden overflow-x-auto md:block">
                                    <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                                        <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                            <tr>{['Invoice', 'Date', 'Status', 'Balance', 'Allocation'].map((header) => <th key={header} className="px-4 py-3 font-semibold">{header}</th>)}</tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {(invoiceResult.data?.data ?? []).map((invoice) => (
                                                <AllocationRow key={invoice.id} invoice={invoice} value={allocations[invoice.id] ?? ''} error={errorFor(`allocations.${invoice.id}.allocated_amount`)} onChange={(value) => updateAllocation(invoice, value)} />
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="grid gap-3 p-3 md:hidden">
                                    {(invoiceResult.data?.data ?? []).map((invoice) => (
                                        <article key={invoice.id} className="rounded-lg border border-slate-200 bg-white p-3">
                                            <Link className="font-semibold text-sky-700 hover:underline" to={`/invoices/${invoice.id}?from=purchase`}>{invoice.invoice_number ?? 'Invoice'}</Link>
                                            <dl className="mt-3 grid grid-cols-2 gap-3 text-sm">
                                                <Summary label="Date" value={formatDate(invoice.invoice_date)} />
                                                <Summary label="Status" value={invoice.status ?? '-'} />
                                                <Summary label="Balance" value={<MoneyDisplay value={balanceOf(invoice)} />} />
                                            </dl>
                                            <div className="mt-3">
                                                <DecimalInput label="Allocation" value={allocations[invoice.id] ?? ''} error={errorFor(`allocations.${invoice.id}.allocated_amount`)} onChange={(event) => updateAllocation(invoice, event.target.value)} />
                                            </div>
                                        </article>
                                    ))}
                                </div>
                                {!invoiceResult.loading && (invoiceResult.data?.data ?? []).length === 0 && <div className="px-4 py-10 text-center text-sm text-slate-500">No outstanding supplier invoices found.</div>}
                                {invoiceResult.loading && <div className="px-4 py-10 text-center text-sm text-slate-500">Loading outstanding invoices...</div>}
                            </div>
                            <Pagination meta={invoiceResult.data?.meta} onPageChange={setInvoicePage} />
                        </>
                    )}
                </section>
            )}

            {activeTab === 'methods' && (
                <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 className="mb-4 text-base font-semibold text-slate-950">Payment Methods</h2>
                    <PurchasePaymentMethodsEditor
                        rows={paymentRows}
                        methods={paymentContext.data?.payment_methods ?? []}
                        accounts={paymentContext.data?.payment_accounts ?? []}
                        errorFor={errorFor}
                        onChange={setPaymentRows}
                    />
                    {!methodsMatch && <div className="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">Payment method rows must equal the payment total.</div>}
                </section>
            )}

            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate('/purchase/payments')}>Cancel</Button>
                <Button type="button" loading={busy} disabled={!canCreate} onClick={() => void createPayment()}>Create Payment</Button>
            </div>
        </div>
    );
}

function AllocationRow({ invoice, value, error, onChange }: {
    invoice: Invoice;
    value: string;
    error?: string;
    onChange: (value: string) => void;
}) {
    return (
        <tr>
            <td className="px-4 py-3"><Link className="font-semibold text-sky-700 hover:underline" to={`/invoices/${invoice.id}?from=purchase`}>{invoice.invoice_number ?? 'Invoice'}</Link></td>
            <td className="px-4 py-3">{formatDate(invoice.invoice_date)}</td>
            <td className="px-4 py-3">{invoice.status ? <StatusBadge status={invoice.status} /> : '-'}</td>
            <td className="px-4 py-3"><MoneyDisplay value={balanceOf(invoice)} currency={invoice.currency?.code ?? undefined} /></td>
            <td className="min-w-44 px-4 py-3"><DecimalInput value={value} error={error} onChange={(event) => onChange(event.target.value)} /></td>
        </tr>
    );
}

function PaymentSummary({ amount, allocated, unallocated, methodTotal, methodDifference, overAllocated }: {
    amount: string;
    allocated: string;
    unallocated: string;
    methodTotal: string;
    methodDifference: string;
    overAllocated: string;
}) {
    const rows = [
        ['Payment Total', amount, false],
        ['Invoice Allocated', allocated, false],
        ['Unallocated', unallocated, false],
        ['Payment Method Total', methodTotal, false],
        ['Difference', methodDifference, compareDecimalStrings(methodDifference, '0.000000') !== 0],
        ['Over-allocated', overAllocated, compareDecimalStrings(overAllocated, '0.000000') > 0],
    ] as const;

    return (
        <div className="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            {rows.map(([label, value, problem]) => (
                <div key={label} className={`rounded-lg border p-3 text-sm ${problem ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-slate-200 bg-white text-slate-700'}`}>
                    <span className="block text-xs font-semibold uppercase text-slate-500">{label}</span>
                    <strong className="mt-1 block tabular-nums text-slate-950"><MoneyDisplay value={value} /></strong>
                </div>
            ))}
        </div>
    );
}

function Summary({ label, value }: { label: string; value: ReactNode }) {
    return <div><dt className="text-xs font-semibold uppercase text-slate-500">{label}</dt><dd className="mt-1 text-slate-800">{value}</dd></div>;
}
