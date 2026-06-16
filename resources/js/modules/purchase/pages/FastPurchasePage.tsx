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
import { createFastPurchase, getFastPurchaseContext, previewFastPurchase, type FastPurchasePayload, type FastPurchaseResult } from '../purchaseApi';
import { todayDate } from '../purchaseFormUtils';
import { CurrencyLookupSelect, SupplierLookupSelect, WarehouseLocationLookupSelect, WarehouseLookupSelect } from '../components/PurchaseLookups';
import { blankFastPurchaseLine, FastPurchaseLines, type FastPurchaseLineRow } from '../components/FastPurchaseLines';
import { FastPurchaseSummary } from '../components/FastPurchaseSummary';

const paymentTerms = [
    { value: 'due_on_receipt', label: 'Due on receipt' },
    { value: 'net_7', label: 'Net 7' },
    { value: 'net_15', label: 'Net 15' },
    { value: 'net_30', label: 'Net 30' },
];

export default function FastPurchasePage() {
    const context = useApi((signal) => getFastPurchaseContext(signal), []);
    const defaults = context.data?.defaults;
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [purchaseDate, setPurchaseDate] = useState(defaults?.purchase_date ?? todayDate());
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(null);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [exchangeRate, setExchangeRate] = useState(defaults?.exchange_rate ?? '1.000000');
    const [terms, setTerms] = useState('due_on_receipt');
    const [supplierReference, setSupplierReference] = useState('');
    const [notes, setNotes] = useState('');
    const [receiveStock, setReceiveStock] = useState(true);
    const [createInvoice, setCreateInvoice] = useState(true);
    const [recordPayment, setRecordPayment] = useState(false);
    const [lines, setLines] = useState<FastPurchaseLineRow[]>([blankFastPurchaseLine()]);
    const [paymentAmount, setPaymentAmount] = useState('');
    const [paymentMethodId, setPaymentMethodId] = useState('');
    const [paymentAccountId, setPaymentAccountId] = useState('');
    const [paymentReference, setPaymentReference] = useState('');
    const [chequeNumber, setChequeNumber] = useState('');
    const [chequeDate, setChequeDate] = useState('');
    const [preview, setPreview] = useState<FastPurchaseResult | null>(null);
    const [result, setResult] = useState<FastPurchaseResult | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [previewing, setPreviewing] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const dirty = Boolean(supplier || supplierReference || notes || lines.some((line) => line.item || line.unit_cost || line.discount_amount !== '0.000000'));
    useUnsavedChanges(dirty && !result && !submitting);
    const errorFor = (field: string) => fieldError(error, field);

    const canSubmit = useMemo(() => Boolean(
        supplier?.id
        && supplierReference.trim()
        && lines.some((line) => line.item?.id)
        && (!recordPayment || (paymentAmount.trim() && paymentAccountId)),
    ), [lines, paymentAccountId, paymentAmount, recordPayment, supplier, supplierReference]);

    const payload = (): FastPurchasePayload => ({
        supplier_id: supplier?.id ?? 0,
        supplier_reference: supplierReference.trim() || undefined,
        purchase_date: purchaseDate,
        warehouse_id: receiveStock ? warehouse?.id : undefined,
        warehouse_location_id: receiveStock ? warehouseLocation?.id : undefined,
        currency_id: currency?.id,
        exchange_rate: exchangeRate || undefined,
        payment_terms: terms || undefined,
        notes: notes || undefined,
        options: {
            receive_stock_now: receiveStock,
            create_supplier_invoice_now: createInvoice,
            record_payment_now: recordPayment,
        },
        lines: lines.filter((line) => line.item?.id).map((line) => ({
            item_id: line.item?.id ?? 0,
            description: line.description || undefined,
            uom_id: line.uom?.id,
            quantity: line.quantity || '0.000000',
            unit_cost: line.unit_cost || undefined,
            discount_amount: line.discount_amount || undefined,
            tax_group_id: line.tax_group_id ? Number(line.tax_group_id) : undefined,
        })),
        payment: recordPayment ? {
            amount: paymentAmount || undefined,
            payment_method_id: paymentMethodId ? Number(paymentMethodId) : undefined,
            source_account_id: paymentAccountId ? Number(paymentAccountId) : undefined,
            reference: paymentReference || undefined,
            cheque_number: chequeNumber || undefined,
            cheque_date: chequeDate || undefined,
        } : undefined,
    });

    const runPreview = async () => {
        setPreviewing(true);
        setError(null);
        try {
            const next = await previewFastPurchase(payload());
            setPreview(next);
            if (recordPayment && !paymentAmount) {
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
                    const created = await createFastPurchase(payload());
                    setResult(created);
                    setPreview(created);
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}
        >
            <ContentHeader title="Fast Purchase" description="Quick purchase entry" />
            <ErrorAlert error={context.error ?? error} />
            <div className="flex flex-col gap-5 lg:flex-row lg:items-start">
                <main className="min-w-0 flex-1 space-y-5">
                    <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <SupplierLookupSelect value={supplier} onChange={setSupplier} error={errorFor('supplier_id')} />
                            <Input label="Purchase date" type="date" value={purchaseDate} error={errorFor('purchase_date')} onChange={(event) => setPurchaseDate(event.target.value)} />
                            <Input label="Supplier reference" value={supplierReference} error={errorFor('supplier_reference')} onChange={(event) => setSupplierReference(event.target.value)} />
                            <Select label="Payment terms" value={terms} options={paymentTerms} onChange={(event) => setTerms(event.target.value)} />
                            <WarehouseLookupSelect value={warehouse} onChange={(value) => { setWarehouse(value); setWarehouseLocation(null); }} error={errorFor('warehouse_id')} />
                            <WarehouseLocationLookupSelect warehouseId={warehouse?.id} value={warehouseLocation} onChange={setWarehouseLocation} error={errorFor('warehouse_location_id')} />
                            <CurrencyLookupSelect value={currency} onChange={setCurrency} error={errorFor('currency_id')} />
                            <DecimalInput label="Exchange rate" value={exchangeRate} error={errorFor('exchange_rate')} onChange={(event) => setExchangeRate(event.target.value)} />
                        </div>
                        <div className="mt-4 flex flex-wrap gap-3 text-sm">
                            <label className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2">
                                <input type="checkbox" checked={receiveStock} onChange={(event) => setReceiveStock(event.target.checked)} />
                                Receive stock now
                            </label>
                            <label className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2">
                                <input
                                    type="checkbox"
                                    checked={createInvoice}
                                    onChange={(event) => {
                                        setCreateInvoice(event.target.checked);
                                        if (!event.target.checked) setRecordPayment(false);
                                    }}
                                />
                                Create supplier invoice now
                            </label>
                            <label className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2">
                                <input
                                    type="checkbox"
                                    checked={recordPayment}
                                    onChange={(event) => {
                                        setRecordPayment(event.target.checked);
                                        if (event.target.checked) setCreateInvoice(true);
                                    }}
                                />
                                Record supplier payment now
                            </label>
                        </div>
                    </section>

                    <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <FastPurchaseLines rows={lines} context={context.data} errorFor={errorFor} onChange={setLines} />
                    </section>

                    {recordPayment && (
                        <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <Select
                                    label="Payment method"
                                    value={paymentMethodId}
                                    onChange={(event) => setPaymentMethodId(event.target.value)}
                                    options={(context.data?.payment_methods ?? []).map((method) => ({ value: method.id, label: `${method.code ?? ''} ${method.name ?? ''}`.trim() }))}
                                    error={errorFor('payment.payment_method_id')}
                                />
                                <DecimalInput label="Amount" value={paymentAmount} onChange={(event) => setPaymentAmount(event.target.value)} error={errorFor('payment.amount')} />
                                <Select
                                    label="Source account"
                                    value={paymentAccountId}
                                    onChange={(event) => setPaymentAccountId(event.target.value)}
                                    options={(context.data?.payment_accounts ?? []).map((account) => ({ value: account.id, label: `${account.code ?? ''} ${account.name ?? ''}`.trim() }))}
                                    error={errorFor('payment.source_account_id')}
                                />
                                <Input label="Reference" value={paymentReference} onChange={(event) => setPaymentReference(event.target.value)} error={errorFor('payment.reference')} />
                                <Input label="Cheque / card no." value={chequeNumber} onChange={(event) => setChequeNumber(event.target.value)} error={errorFor('payment.cheque_number')} />
                                <Input label="Instrument date" type="date" value={chequeDate} onChange={(event) => setChequeDate(event.target.value)} error={errorFor('payment.cheque_date')} />
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
                <FastPurchaseSummary
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
