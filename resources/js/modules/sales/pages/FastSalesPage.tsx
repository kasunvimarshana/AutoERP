import { useMemo, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { useApi } from '@/shared/hooks/useApi';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { createFastSales, getFastSalesContext, previewFastSales, type FastSalesPayload, type FastSalesResult } from '../salesApi';
import { CustomerLookupSelect, SalesCurrencyLookupSelect, SalesWarehouseLocationLookupSelect, SalesWarehouseLookupSelect } from '../components/SalesLookups';
import { blankFastSalesLine, FastSalesLines, type FastSalesLineRow } from '../components/FastSalesLines';
import { FastSalesSummary } from '../components/FastSalesSummary';

type FastSalesWorkflowMode =
    | 'order_only'
    | 'delivery_only'
    | 'credit_sale'
    | 'cash_sale'
    | 'direct_sale'
    | 'direct_sale_paid';

const paymentTerms = [
    { value: 'due_on_receipt', label: 'Due on receipt' },
    { value: 'net_7', label: 'Net 7' },
    { value: 'net_15', label: 'Net 15' },
    { value: 'net_30', label: 'Net 30' },
];

const workflowOptions: Array<{ value: FastSalesWorkflowMode; label: string; hint: string }> = [
    { value: 'order_only', label: 'Order only', hint: 'Sales order only' },
    { value: 'delivery_only', label: 'Delivery only', hint: 'Delivery plus inventory issue' },
    { value: 'credit_sale', label: 'Credit sale', hint: 'Delivery plus invoice' },
    { value: 'cash_sale', label: 'Cash sale', hint: 'Delivery, invoice, and receipt' },
    { value: 'direct_sale', label: 'Direct service sale', hint: 'Invoice only for non-stock lines' },
    { value: 'direct_sale_paid', label: 'Direct service + receipt', hint: 'Invoice and receipt for non-stock lines' },
];


function workflowNeedsWarehouse(mode: FastSalesWorkflowMode): boolean {
    return ['order_only', 'delivery_only', 'credit_sale', 'cash_sale'].includes(mode);
}

function workflowRecordsReceipt(mode: FastSalesWorkflowMode): boolean {
    return mode === 'cash_sale' || mode === 'direct_sale_paid';
}

function newIdempotencyKey() {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `fast-sales-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

export default function FastSalesPage() {
    const context = useApi((signal) => getFastSalesContext(signal), []);
    const defaults = context.data?.defaults;
    const [customer, setCustomer] = useState<NamedResource | null>(null);
    const [transactionDate, setTransactionDate] = useState(defaults?.transaction_date ?? businessDateInputValue());
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(null);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [exchangeRate, setExchangeRate] = useState(defaults?.exchange_rate ?? '1.000000');
    const [terms, setTerms] = useState('due_on_receipt');
    const [customerReference, setCustomerReference] = useState('');
    const [notes, setNotes] = useState('');
    const [workflowMode, setWorkflowMode] = useState<FastSalesWorkflowMode>('credit_sale');
    const [lines, setLines] = useState<FastSalesLineRow[]>([blankFastSalesLine()]);
    const [paymentAmount, setPaymentAmount] = useState('');
    const [paymentMethodId, setPaymentMethodId] = useState('');
    const [paymentReference, setPaymentReference] = useState('');
    const [instrumentNumber, setInstrumentNumber] = useState('');
    const [instrumentDate, setInstrumentDate] = useState('');
    const [preview, setPreview] = useState<FastSalesResult | null>(null);
    const [result, setResult] = useState<FastSalesResult | null>(null);
    const [idempotencyKey, setIdempotencyKey] = useState(newIdempotencyKey);
    const [error, setError] = useState<ApiError | null>(null);
    const [previewing, setPreviewing] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const createOrderOnly = workflowMode === 'order_only';
    const deliverItemsNow = workflowMode === 'delivery_only' || workflowMode === 'credit_sale' || workflowMode === 'cash_sale';
    const createInvoice = workflowMode === 'credit_sale' || workflowMode === 'cash_sale' || workflowMode === 'direct_sale' || workflowMode === 'direct_sale_paid';
    const recordReceipt = workflowMode === 'cash_sale' || workflowMode === 'direct_sale_paid';
    const needsWarehouse = createOrderOnly || deliverItemsNow;

    const changeWorkflowMode = (nextMode: FastSalesWorkflowMode) => {
        if (!workflowNeedsWarehouse(nextMode)) {
            setWarehouse(null);
            setWarehouseLocation(null);
        }
        if (!workflowRecordsReceipt(nextMode)) {
            setPaymentAmount('');
            setPaymentMethodId('');
            setPaymentReference('');
            setInstrumentNumber('');
            setInstrumentDate('');
        }
        setPreview(null);
        setResult(null);
        setWorkflowMode(nextMode);
    };

    const dirty = Boolean(
        customer
        || customerReference
        || notes
        || lines.some((line) => line.item || line.unit_price || line.discount_amount !== '0.000000'),
    );
    useUnsavedChanges(dirty && !result && !submitting);
    const errorFor = (field: string) => fieldError(error, field);

    const canSubmit = useMemo(() => Boolean(
        customer?.id
        && customerReference.trim()
        && lines.some((line) => line.item?.id)
        && (!needsWarehouse || warehouse?.id)
        && (!recordReceipt || (paymentAmount.trim() && paymentMethodId)),
    ), [customer, customerReference, lines, needsWarehouse, paymentAmount, paymentMethodId, recordReceipt, warehouse]);

    const payload = (): FastSalesPayload => ({
        idempotency_key: idempotencyKey,
        customer_id: customer?.id ?? 0,
        customer_reference: customerReference.trim() || undefined,
        transaction_date: transactionDate,
        warehouse_id: needsWarehouse ? warehouse?.id : undefined,
        warehouse_location_id: needsWarehouse ? warehouseLocation?.id : undefined,
        currency_id: currency?.id,
        exchange_rate: exchangeRate || undefined,
        payment_terms: terms || undefined,
        notes: notes || undefined,
        options: {
            create_sales_order_only: createOrderOnly,
            deliver_items_now: deliverItemsNow,
            create_customer_invoice_now: createInvoice,
            record_customer_receipt_now: recordReceipt,
        },
        lines: lines.filter((line) => line.item?.id).map((line) => ({
            item_id: line.item?.id ?? 0,
            description: line.description || undefined,
            uom_id: line.uom?.id,
            quantity: line.quantity || '0.000000',
            unit_price: line.unit_price || undefined,
            discount_amount: line.discount_amount || undefined,
            tax_group_id: line.tax_group_id ? Number(line.tax_group_id) : undefined,
        })),
        payment: recordReceipt ? {
            amount: paymentAmount || undefined,
            payment_method_id: paymentMethodId ? Number(paymentMethodId) : undefined,
            reference: paymentReference || undefined,
            instrument_number: instrumentNumber || undefined,
            instrument_date: instrumentDate || undefined,
        } : undefined,
    });

    const applyPreviewToLines = (next: FastSalesResult) => {
        setLines((current) => current.map((row, index) => {
            const previewLine = next.lines[index];
            if (!previewLine) return row;

            return {
                ...row,
                unit_price: row.unit_price || previewLine.unit_price,
            };
        }));
    };

    const runPreview = async () => {
        setPreviewing(true);
        setError(null);
        try {
            const next = await previewFastSales(payload());
            setPreview(next);
            applyPreviewToLines(next);
            if (recordReceipt && !paymentAmount) {
                setPaymentAmount(next.summary.grand_total);
            }
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setPreviewing(false);
        }
    };

    return (
        <form
            className="space-y-5"
            onSubmit={async (event) => {
                event.preventDefault();
                if (submitting || !canSubmit) return;
                setSubmitting(true);
                setError(null);
                try {
                    const created = await createFastSales(payload());
                    setResult(created);
                    setPreview(created);
                    setIdempotencyKey(newIdempotencyKey());
                    applyPreviewToLines(created);
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}
        >
            <ContentHeader title="Fast Sales" description="Quick customer sales entry" />
            <ErrorAlert error={context.error ?? error} />
            <div className="flex flex-col gap-5 lg:flex-row lg:items-start">
                <main className="min-w-0 flex-1 space-y-5">
                    <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {workflowOptions.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    className={`rounded-lg border px-4 py-3 text-left transition ${workflowMode === option.value ? 'border-sky-500 bg-sky-50 text-sky-900' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'}`}
                                    onClick={() => changeWorkflowMode(option.value)}
                                >
                                    <div className="text-sm font-semibold">{option.label}</div>
                                    <div className="mt-1 text-xs text-slate-500">{option.hint}</div>
                                </button>
                            ))}
                        </div>
                        <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <CustomerLookupSelect value={customer} onChange={setCustomer} error={errorFor('customer_id')} />
                            <Input label="Transaction date" type="date" value={transactionDate} error={errorFor('transaction_date')} onChange={(event) => setTransactionDate(event.target.value)} />
                            <Input label="Customer reference" value={customerReference} error={errorFor('customer_reference')} onChange={(event) => setCustomerReference(event.target.value)} />
                            <Select label="Payment terms" value={terms} options={paymentTerms} onChange={(event) => setTerms(event.target.value)} />
                            <SalesWarehouseLookupSelect value={warehouse} onChange={(value) => { setWarehouse(value); setWarehouseLocation(null); }} error={errorFor('warehouse_id')} />
                            <SalesWarehouseLocationLookupSelect warehouseId={warehouse?.id} value={warehouseLocation} onChange={setWarehouseLocation} error={errorFor('warehouse_location_id')} />
                            <SalesCurrencyLookupSelect value={currency} onChange={setCurrency} error={errorFor('currency_id')} />
                            <DecimalInput label="Exchange rate" value={exchangeRate} error={errorFor('exchange_rate')} onChange={(event) => setExchangeRate(event.target.value)} />
                        </div>
                    </section>

                    <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <FastSalesLines rows={lines} context={context.data} previewLines={preview?.lines ?? []} errorFor={errorFor} onChange={setLines} />
                    </section>

                    {recordReceipt && (
                        <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <Select
                                    label="Payment method"
                                    value={paymentMethodId}
                                    onChange={(event) => setPaymentMethodId(event.target.value)}
                                    options={(context.data?.payment_methods ?? []).map((method) => ({ value: method.id, label: `${method.code ?? ''} ${method.name ?? ''}`.trim() }))}
                                    error={errorFor('payment.payment_method_id')}
                                />
                                <DecimalInput label="Received amount" value={paymentAmount} onChange={(event) => setPaymentAmount(event.target.value)} error={errorFor('payment.amount')} />
                                <Input label="Reference" value={paymentReference} onChange={(event) => setPaymentReference(event.target.value)} error={errorFor('payment.reference')} />
                                <Input label="Cheque / card no." value={instrumentNumber} onChange={(event) => setInstrumentNumber(event.target.value)} error={errorFor('payment.instrument_number')} />
                                <Input label="Instrument date" type="date" value={instrumentDate} onChange={(event) => setInstrumentDate(event.target.value)} error={errorFor('payment.instrument_date')} />
                            </div>
                        </section>
                    )}

                    <details className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <summary className="cursor-pointer text-sm font-semibold text-slate-800">Advanced</summary>
                        <div className="mt-4">
                            <Textarea label="Notes" value={notes} onChange={(event) => setNotes(event.target.value)} error={errorFor('notes')} />
                        </div>
                    </details>
                </main>
                <FastSalesSummary
                    preview={preview}
                    result={result}
                    submitting={submitting}
                    previewing={previewing}
                    canSubmit={canSubmit}
                    onPreview={() => void runPreview()}
                />
            </div>
        </form>
    );
}
