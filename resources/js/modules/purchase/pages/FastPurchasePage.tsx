import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { useSearchParams } from 'react-router-dom';
import { fieldError, hasNestedFieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { NamedResource } from '@/shared/types/common';
import { compareDecimalStrings, isPositiveDecimal } from '@/shared/utils/decimal';
import {
    createFastPurchase,
    getFastPurchaseContext,
    getPurchaseSupplierContext,
    getPurchaseWarehouseLocations,
    previewFastPurchase,
    type FastPurchasePayload,
    type FastPurchaseResult,
} from '../purchaseApi';
import { todayDate } from '../purchaseFormUtils';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { PurchaseTabs, type PurchaseTabItem } from '../components/PurchaseTabs';
import { CurrencyLookupSelect, SupplierLookupSelect, WarehouseLocationLookupSelect, WarehouseLookupSelect } from '../components/PurchaseLookups';
import { PurchaseHeaderAdjustmentEditor, type EditableHeaderAdjustment } from '../components/PurchaseHeaderAdjustmentEditor';
import { blankFastPurchaseLine, FastPurchaseLines, type FastPurchaseLineRow } from '../components/FastPurchaseLines';
import { FastPurchaseSummary } from '../components/FastPurchaseSummary';
import {
    blankPaymentMethodRow,
    paymentRowsTotal,
    PurchasePaymentMethodsEditor,
    type PurchasePaymentMethodRow,
} from '../components/PurchasePaymentMethodsEditor';

const paymentTerms = [
    { value: 'due_on_receipt', label: 'Due on receipt' },
    { value: 'net_7', label: 'Net 7' },
    { value: 'net_15', label: 'Net 15' },
    { value: 'net_30', label: 'Net 30' },
];

type FastPurchasePreset = 'expense_only' | 'purchase_receive' | 'purchase_receive_invoice' | 'purchase_receive_invoice_pay';
type FastPurchaseTab = 'details' | 'lines' | 'adjustments' | 'payment' | 'impact' | 'attachments';

const presets: Array<{ value: FastPurchasePreset; label: string; description: string }> = [
    { value: 'expense_only', label: 'Expense Only', description: 'Invoice non-stock supplier costs.' },
    { value: 'purchase_receive', label: 'Purchase + Receive', description: 'Post a goods receipt only.' },
    { value: 'purchase_receive_invoice', label: 'Purchase + Receive + Invoice', description: 'Receive stock and post the supplier invoice.' },
    { value: 'purchase_receive_invoice_pay', label: 'Purchase + Receive + Invoice + Pay', description: 'Create receipt, invoice, and supplier payment.' },
];

const tabIds: FastPurchaseTab[] = ['details', 'lines', 'adjustments', 'payment', 'impact', 'attachments'];

export default function FastPurchasePage() {
    const context = useApi((signal) => getFastPurchaseContext(signal), []);
    const defaults = context.data?.defaults;
    const [searchParams] = useSearchParams();
    const activeTab = tabIds.includes(searchParams.get('tab') as FastPurchaseTab)
        ? searchParams.get('tab') as FastPurchaseTab
        : 'details';

    const [supplier, setSupplierState] = useState<NamedResource | null>(null);
    const [purchaseDate, setPurchaseDate] = useState(todayDate());
    const [warehouse, setWarehouseState] = useState<NamedResource | null>(null);
    const [warehouseLocation, setWarehouseLocationState] = useState<NamedResource | null>(null);
    const [currency, setCurrencyState] = useState<NamedResource | null>(null);
    const [exchangeRate, setExchangeRate] = useState('1.000000');
    const [terms, setTerms] = useState('due_on_receipt');
    const [supplierReference, setSupplierReference] = useState('');
    const [notes, setNotes] = useState('');
    const [preset, setPreset] = useState<FastPurchasePreset>('purchase_receive_invoice');
    const [lines, setLines] = useState<FastPurchaseLineRow[]>([blankFastPurchaseLine()]);
    const [adjustments, setAdjustments] = useState<EditableHeaderAdjustment[]>([]);
    const [paymentRows, setPaymentRows] = useState<PurchasePaymentMethodRow[]>([blankPaymentMethodRow()]);
    const [preview, setPreview] = useState<FastPurchaseResult | null>(null);
    const [result, setResult] = useState<FastPurchaseResult | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [previewing, setPreviewing] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const purchaseDateTouched = useRef(false);
    const currencyTouched = useRef(false);
    const warehouseTouched = useRef(false);
    const locationTouched = useRef(false);
    const exchangeRateTouched = useRef(false);
    const lastPreviewKey = useRef<string | null>(null);
    const previewController = useRef<AbortController | null>(null);

    const receiveStock = preset !== 'expense_only';
    const createInvoice = preset !== 'purchase_receive';
    const recordPayment = preset === 'purchase_receive_invoice_pay';
    const paymentTotal = paymentRowsTotal(paymentRows);
    const currentError = error ?? context.error;
    const errorFor = useCallback((field: string) => fieldError(currentError, field), [currentError]);

    useEffect(() => {
        if (!defaults) return;

        if (!purchaseDateTouched.current) setPurchaseDate(defaults.purchase_date ?? todayDate());
        if (!exchangeRateTouched.current) setExchangeRate(defaults.exchange_rate ?? '1.000000');
        if (!currencyTouched.current && defaults.currency) setCurrencyState(defaults.currency);
        if (!warehouseTouched.current && defaults.warehouse) setWarehouseState(defaults.warehouse);
        if (!locationTouched.current && defaults.warehouse_location) setWarehouseLocationState(defaults.warehouse_location);
    }, [defaults]);

    useEffect(() => {
        if (!supplier?.id) return;

        const controller = new AbortController();
        void getPurchaseSupplierContext(supplier.id, controller.signal)
            .then((supplierContext) => {
                if (controller.signal.aborted) return;
                if (!currencyTouched.current && supplierContext.currency) {
                    setCurrencyState(supplierContext.currency);
                }
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [supplier?.id]);

    useEffect(() => {
        if (!warehouse?.id) return;

        const controller = new AbortController();
        void getPurchaseWarehouseLocations(warehouse.id, controller.signal)
            .then((locations) => {
                if (controller.signal.aborted || locationTouched.current) return;
                const defaultLocation = (locations as Array<NamedResource & { is_default?: boolean }>).find((location) => location.is_default) ?? null;
                setWarehouseLocationState(defaultLocation);
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [warehouse?.id]);

    const setSupplier = (next: NamedResource | null) => {
        if (supplier?.id && next?.id && supplier.id !== next.id && lines.some((line) => line.item?.id)) {
            const confirmed = window.confirm('Changing the supplier may refresh line UOM, price, and tax defaults.');
            if (!confirmed) return;
        }
        setSupplierState(next);
    };

    const setWarehouse = (next: NamedResource | null) => {
        if (warehouse?.id === next?.id) return;
        warehouseTouched.current = true;
        locationTouched.current = false;
        setWarehouseState(next);
        setWarehouseLocationState(null);
    };

    const setWarehouseLocation = (next: NamedResource | null) => {
        locationTouched.current = true;
        setWarehouseLocationState(next);
    };

    const setCurrency = (next: NamedResource | null) => {
        currencyTouched.current = true;
        setCurrencyState(next);
    };

    const buildPayload = useCallback((paymentRowsOverride = paymentRows): FastPurchasePayload => ({
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
            item_variant_id: line.item_variant?.id,
            description: line.description || undefined,
            uom_id: line.uom?.id,
            quantity: line.quantity || '0.000000',
            unit_cost: line.unit_cost || undefined,
            discount_amount: line.discount_amount || undefined,
            tax_group_id: line.tax_group_id ? Number(line.tax_group_id) : undefined,
        })),
        adjustments: adjustments.map((adjustment) => ({
            name: adjustment.name,
            adjustment_type: adjustment.adjustment_type,
            effect: adjustment.effect,
            calculation_type: adjustment.calculation_type,
            calculation_base: adjustment.calculation_base,
            rate: adjustment.rate,
            amount: adjustment.amount,
            allocation_method: adjustment.allocation_method,
            cost_treatment: adjustment.cost_treatment,
            tax_treatment: adjustment.tax_treatment,
            mapping_source: adjustment.mapping_source,
            override_reason: adjustment.override_reason || undefined,
            description: adjustment.description || undefined,
        })),
        payment: recordPayment ? {
            amount: paymentRowsTotal(paymentRowsOverride),
            lines: paymentRowsOverride.map((row) => ({
                amount: row.amount || '0.000000',
                payment_method_id: row.payment_method_id ? Number(row.payment_method_id) : undefined,
                source_account_id: row.source_account_id ? Number(row.source_account_id) : undefined,
                reference: row.reference || undefined,
                instrument_number: row.instrument_number || undefined,
                instrument_date: row.instrument_date || undefined,
                external_bank_name: row.external_bank_name || undefined,
                external_bank_branch: row.external_bank_branch || undefined,
            })),
        } : undefined,
    }), [
        adjustments,
        createInvoice,
        currency?.id,
        exchangeRate,
        lines,
        notes,
        paymentRows,
        preset,
        purchaseDate,
        receiveStock,
        recordPayment,
        supplier?.id,
        supplierReference,
        terms,
        warehouse?.id,
        warehouseLocation?.id,
    ]);

    const currentPayload = useMemo(() => buildPayload(), [buildPayload]);
    const payloadKey = useMemo(() => JSON.stringify(currentPayload), [currentPayload]);
    const previewStale = Boolean(preview && !result && lastPreviewKey.current !== payloadKey);

    const canSubmit = useMemo(() => {
        const hasLine = lines.some((line) => line.item?.id);
        const paymentReady = !recordPayment || (
            isPositiveDecimal(paymentTotal)
            && paymentRows.every((row) => isPositiveDecimal(row.amount) && row.payment_method_id && row.source_account_id)
        );

        return Boolean(
            supplier?.id
            && supplierReference.trim()
            && purchaseDate
            && hasLine
            && (!receiveStock || warehouse?.id)
            && paymentReady
            && !submitting
        );
    }, [lines, paymentRows, paymentTotal, purchaseDate, receiveStock, recordPayment, submitting, supplier?.id, supplierReference, warehouse?.id]);

    const dirty = Boolean(
        supplier
        || supplierReference
        || notes
        || adjustments.length > 0
        || lines.some((line) => line.item || line.unit_cost || line.discount_amount !== '0.000000')
        || paymentRows.some((row) => row.amount || row.reference || row.payment_method_id || row.source_account_id)
    );
    useUnsavedChanges(dirty && !result && !submitting);

    const runPreview = async () => {
        if (previewing) return;
        previewController.current?.abort();
        const controller = new AbortController();
        previewController.current = controller;
        setPreviewing(true);
        setError(null);
        try {
            const next = await previewFastPurchase(currentPayload, controller.signal);
            if (controller.signal.aborted) return;
            setPreview(next);
            setResult(null);
            lastPreviewKey.current = payloadKey;
        } catch (requestError) {
            if (!controller.signal.aborted) setError(toApiError(requestError));
        } finally {
            if (!controller.signal.aborted) setPreviewing(false);
        }
    };

    const submit = async () => {
        if (submitting || !canSubmit || previewStale) return;
        setSubmitting(true);
        setError(null);
        try {
            const created = await createFastPurchase(currentPayload);
            setResult(created);
            setPreview(created);
            lastPreviewKey.current = payloadKey;
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    const createAnother = () => {
        setSupplierState(null);
        setSupplierReference('');
        setNotes('');
        setLines([blankFastPurchaseLine()]);
        setAdjustments([]);
        setPaymentRows([blankPaymentMethodRow()]);
        setPreview(null);
        setResult(null);
        setError(null);
        lastPreviewKey.current = null;
    };

    const tabs: PurchaseTabItem[] = [
        { id: 'details', label: 'Purchase Details', error: hasAnyError(currentError, ['supplier_id', 'purchase_date', 'warehouse_id', 'warehouse_location_id', 'currency_id', 'exchange_rate']) },
        { id: 'lines', label: 'Lines', count: lines.filter((line) => line.item?.id).length, error: hasNestedFieldError(currentError, 'lines') },
        { id: 'adjustments', label: 'Adjustments', count: adjustments.length, error: hasNestedFieldError(currentError, 'adjustments') },
        { id: 'payment', label: 'Payment', count: recordPayment ? paymentRows.length : undefined, error: hasNestedFieldError(currentError, 'payment') },
        { id: 'impact', label: 'Impact Summary' },
        { id: 'attachments', label: 'Attachments' },
    ];

    const header = (
        <PurchasePageHeader
            title="Fast Purchase"
            description="Quickly record supplier purchases while keeping receipt, invoice, payment, inventory, tax, and finance posting rules authoritative."
            status={<span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{preset.replaceAll('_', ' ')}</span>}
            actions={<>
                <Button type="button" variant="secondary" onClick={createAnother} disabled={submitting || previewing}>Cancel</Button>
                {!result && <Button type="button" variant="secondary" loading={previewing} disabled={!canSubmit} onClick={() => void runPreview()}>Preview</Button>}
                {!result && <Button type="submit" loading={submitting} disabled={!canSubmit || previewStale}>Create Fast Purchase</Button>}
            </>}
        />
    );

    return (
        <form onSubmit={(event) => { event.preventDefault(); void submit(); }}>
            <PurchaseDocumentShell
                header={header}
                tabs={<PurchaseTabs tabs={tabs} activeTab={activeTab} />}
                summary={<FastPurchaseSummary
                    preview={preview}
                    result={result}
                    submitting={submitting}
                    previewing={previewing}
                    canSubmit={canSubmit}
                    stale={previewStale}
                    onPreview={() => void runPreview()}
                    onCreateAnother={createAnother}
                />}
            >
                <ErrorAlert error={currentError} />
                {result ? <CompletedSummary result={result} /> : <>
                    {activeTab === 'details' && <Section title="Purchase Details">
                        <div className="grid gap-4 md:grid-cols-2">
                            <SupplierLookupSelect value={supplier} onChange={setSupplier} error={errorFor('supplier_id')} />
                            <Input label="Supplier reference" value={supplierReference} error={errorFor('supplier_reference')} onChange={(event) => setSupplierReference(event.target.value)} />
                            <CurrencyLookupSelect value={currency} onChange={setCurrency} error={errorFor('currency_id')} />
                            <DecimalInput
                                label="Exchange rate"
                                value={exchangeRate}
                                hint={defaults?.exchange_rate_source}
                                error={errorFor('exchange_rate')}
                                onChange={(event) => {
                                    exchangeRateTouched.current = true;
                                    setExchangeRate(event.target.value);
                                }}
                            />
                            {receiveStock && <WarehouseLookupSelect value={warehouse} onChange={setWarehouse} error={errorFor('warehouse_id')} />}
                            {receiveStock && <WarehouseLocationLookupSelect warehouseId={warehouse?.id} value={warehouseLocation} onChange={setWarehouseLocation} error={errorFor('warehouse_location_id')} />}
                            <Input
                                label="Purchase date"
                                type="date"
                                value={purchaseDate}
                                error={errorFor('purchase_date')}
                                onChange={(event) => {
                                    purchaseDateTouched.current = true;
                                    setPurchaseDate(event.target.value);
                                }}
                            />
                            <Select label="Payment terms" value={terms} options={paymentTerms} onChange={(event) => setTerms(event.target.value)} />
                            <div className="md:col-span-2">
                                <Textarea label="Notes" value={notes} error={errorFor('notes')} onChange={(event) => setNotes(event.target.value)} />
                            </div>
                        </div>
                        <div className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            {presets.map((option) => (
                                <label key={option.value} className={`rounded-lg border p-3 text-sm ${preset === option.value ? 'border-sky-300 bg-sky-50 text-sky-900' : 'border-slate-200 bg-white text-slate-700'}`}>
                                    <span className="flex items-center gap-2 font-semibold">
                                        <input type="radio" name="fast_purchase_preset" checked={preset === option.value} onChange={() => setPreset(option.value)} />
                                        {option.label}
                                    </span>
                                    <span className="mt-1 block text-xs text-slate-500">{option.description}</span>
                                </label>
                            ))}
                        </div>
                    </Section>}

                    {activeTab === 'lines' && <Section title="Lines">
                        <FastPurchaseLines
                            rows={lines}
                            context={context.data}
                            supplierId={supplier?.id}
                            currencyId={currency?.id}
                            warehouseId={warehouse?.id}
                            errorFor={errorFor}
                            onChange={setLines}
                        />
                    </Section>}

                    {activeTab === 'adjustments' && <Section title="Adjustments">
                        <PurchaseHeaderAdjustmentEditor adjustments={adjustments} onChange={setAdjustments} errorFor={errorFor} />
                    </Section>}

                    {activeTab === 'payment' && <Section title="Payment">
                        {recordPayment ? <>
                            <PurchasePaymentMethodsEditor
                                rows={paymentRows}
                                methods={context.data?.payment_methods ?? []}
                                accounts={context.data?.payment_accounts ?? []}
                                errorFor={errorFor}
                                onChange={setPaymentRows}
                            />
                            <div className={`mt-3 rounded-lg px-3 py-2 text-sm font-medium ${compareDecimalStrings(paymentTotal, preview?.summary.grand_total ?? paymentTotal) > 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-50 text-slate-700'}`}>
                                Payment rows total: {paymentTotal}
                            </div>
                        </> : <EmptyState title="Payment is not part of the selected preset." />}
                    </Section>}

                    {activeTab === 'impact' && <Section title="Impact Summary">
                        <ImpactList preset={preset} />
                    </Section>}

                    {activeTab === 'attachments' && <Section title="Attachments">
                        <EmptyState title="Attach supplier documents after the fast purchase is created." />
                    </Section>}
                </>}
            </PurchaseDocumentShell>
        </form>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h2 className="mb-4 text-base font-semibold text-slate-950">{title}</h2>
            {children}
        </section>
    );
}

function EmptyState({ title }: { title: string }) {
    return <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm font-medium text-slate-600">{title}</div>;
}

function ImpactList({ preset }: { preset: FastPurchasePreset }) {
    const receiveStock = preset !== 'expense_only';
    const createInvoice = preset !== 'purchase_receive';
    const recordPayment = preset === 'purchase_receive_invoice_pay';
    const rows = [
        receiveStock ? 'Goods Receipt' : null,
        createInvoice ? 'Supplier Invoice' : null,
        recordPayment ? 'Payment' : null,
        receiveStock ? 'Inventory Movement' : null,
        createInvoice || receiveStock ? 'Tax Posting' : null,
        createInvoice || recordPayment ? 'Finance Entries' : null,
    ].filter(Boolean);

    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
            <div className="font-semibold text-slate-900">This transaction will create</div>
            <ul className="mt-2 space-y-1 text-slate-700">
                {rows.map((label) => <li key={label}>✓ {label}</li>)}
            </ul>
        </div>
    );
}

function CompletedSummary({ result }: { result: FastPurchaseResult }) {
    const documents = [
        result.documents.goods_receipt,
        result.documents.supplier_invoice,
        result.documents.supplier_payment,
    ].filter(Boolean);

    return (
        <Section title="Completed">
            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                Fast purchase completed successfully.
            </div>
            {documents.length > 0 && (
                <div className="mt-4 grid gap-3 md:grid-cols-3">
                    {documents.map((document) => document && (
                        <a key={document.url} href={document.url} className="rounded-lg border border-slate-200 bg-white p-3 text-sm font-semibold text-sky-700 hover:bg-sky-50">
                            {document.number}
                        </a>
                    ))}
                </div>
            )}
        </Section>
    );
}

function hasAnyError(error: ApiError | null, fields: string[]): boolean {
    return fields.some((field) => Boolean(fieldError(error, field)));
}
