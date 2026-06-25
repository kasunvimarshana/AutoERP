import { useCallback, useMemo, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import {
    createPurchaseInvoice,
    getGoodsReceipt,
    getInvoiceableGoodsReceiptLines,
    getInvoiceablePurchaseOrderLines,
    getPurchaseOrder,
    previewPurchaseInvoice,
    type GoodsReceiptLine,
    type PurchaseInvoicePayload,
    type PurchaseOrderLine,
} from '../purchaseApi';
import { decimalOr, todayDate } from '../purchaseFormUtils';
import { CurrencyLookupSelect, GoodsReceiptLookupSelect, PurchaseOrderLookupSelect, SupplierLookupSelect } from '../components/PurchaseLookups';
import { PurchaseInvoicePreview } from '../components/PurchaseInvoicePreview';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import {
    PurchaseInvoiceLineTable,
    type EditablePurchaseInvoiceLine,
    type PurchaseInvoiceSourceType,
} from '../components/PurchaseInvoiceLineTable';
import { useDocumentSources } from '../hooks/useDocumentSources';
import { useInitialSourceParam, type InitialSourceParamDefinition, type InitialSourceCommand } from '../hooks/useInitialSourceParam';
import { normalizeSourceId, sourceKey, sourceLineKey } from '../sourceIdentity';

interface InvoiceSourceContext {
    supplier: NamedResource | null;
    currency: NamedResource | null;
}

const initialSourceParams: Array<InitialSourceParamDefinition<PurchaseInvoiceSourceType>> = [
    { sourceType: 'purchase_order', paramNames: ['purchase_order_id'] },
    { sourceType: 'goods_receipt_note', paramNames: ['goods_receipt_id', 'grn_id'] },
];

export default function PurchaseInvoiceCreatePage() {
    const navigate = useNavigate();
    const [searchParams, setSearchParams] = useSearchParams();
    const [sourceType, setSourceType] = useState<PurchaseInvoiceSourceType>('goods_receipt_note');
    const [source, setSource] = useState<NamedResource | null>(null);
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [invoiceDate, setInvoiceDate] = useState(todayDate());
    const [dueDate, setDueDate] = useState('');
    const [invoiceNumber, setInvoiceNumber] = useState('');
    const [exchangeRate, setExchangeRate] = useState('1.000000');
    const [notes, setNotes] = useState('');
    const [preview, setPreview] = useState<Record<string, unknown> | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const errorFor = (field: string) => fieldError(error, field);

    const getLineKey = useCallback((line: EditablePurchaseInvoiceLine) => (
        sourceLineKey(line.sourceType, line.sourceId, line.lineId)
    ), []);
    const getLineSourceKey = useCallback((line: EditablePurchaseInvoiceLine) => (
        sourceKey(line.sourceType, line.sourceId)
    ), []);
    const {
        sources,
        lines,
        hasLoadingSources,
        setLines,
        addSource,
        removeSource,
        clearSources,
        isSourceUnavailable,
        excludeIdsForType,
    } = useDocumentSources<PurchaseInvoiceSourceType, EditablePurchaseInvoiceLine>({
        getLineKey,
        getLineSourceKey,
    });

    const addInvoiceSource = useCallback(async (
        type: PurchaseInvoiceSourceType,
        rawSourceId: number,
        fallbackLabel: string,
    ): Promise<boolean> => {
        const sourceId = normalizeSourceId(rawSourceId);
        if (sourceId === null) return false;

        setError(null);
        setPreview(null);

        try {
            const added = await addSource<InvoiceSourceContext>({
                type,
                id: sourceId,
                fallbackLabel,
                load: async (signal) => {
                    if (type === 'purchase_order') {
                        const [order, rows] = await Promise.all([
                            getPurchaseOrder(sourceId, signal),
                            getInvoiceablePurchaseOrderLines(sourceId, signal),
                        ]);
                        const label = order.purchase_order_number ?? `Purchase Order #${order.id}`;

                        return {
                            source: { type, id: order.id, label },
                            lines: rows
                                .map((row) => invoiceLineFromPurchaseOrder(row, order.id, label))
                                .filter(isInvoiceLine),
                            context: {
                                supplier: order.supplier ?? null,
                                currency: order.currency ?? null,
                            },
                        };
                    }

                    const [grn, rows] = await Promise.all([
                        getGoodsReceipt(sourceId, signal),
                        getInvoiceableGoodsReceiptLines(sourceId, signal),
                    ]);
                    const label = grn.grn_number ?? `Goods Receipt #${grn.id}`;

                    return {
                        source: { type, id: grn.id, label },
                        lines: rows
                            .map((row) => invoiceLineFromGoodsReceipt(row, grn.id, label))
                            .filter(isInvoiceLine),
                        context: {
                            supplier: grn.supplier ?? null,
                            currency: null,
                        },
                    };
                },
                onSuccess: ({ context }) => {
                    if (context?.supplier) {
                        setSupplier((current) => current?.id === context.supplier?.id ? current : context.supplier);
                    }
                    if (context?.currency) {
                        setCurrency((current) => current?.id === context.currency?.id ? current : context.currency);
                    }
                },
            });

            if (added) setSource(null);
            return added;
        } catch (requestError) {
            setError(toApiError(requestError));
            return false;
        }
    }, [addSource]);

    const processInitialSource = useCallback(async (command: InitialSourceCommand<PurchaseInvoiceSourceType>) => {
        setSourceType(command.sourceType);
        await addInvoiceSource(command.sourceType, command.sourceId, fallbackSourceLabel(command.sourceType, command.sourceId));
    }, [addInvoiceSource]);

    useInitialSourceParam({
        searchParams,
        setSearchParams,
        definitions: initialSourceParams,
        isUnavailable: isSourceUnavailable,
        onProcess: processInitialSource,
    });

    const selectedSourceKey = source?.id ? sourceKey(sourceType, source.id) : null;
    const canAddSource = Boolean(selectedSourceKey && !isSourceUnavailable(selectedSourceKey) && !busy && !hasLoadingSources);
    const excludedSourceIds = useMemo(() => excludeIdsForType(sourceType), [excludeIdsForType, sourceType]);

    const clearInvoiceSources = () => {
        clearSources();
        setSource(null);
        setPreview(null);
    };

    const removeInvoiceSource = (item: { type: PurchaseInvoiceSourceType; id: number }) => {
        removeSource(item);
        setPreview(null);
    };

    const payload = (): PurchaseInvoicePayload => ({
        invoice_date: invoiceDate,
        invoice_number: invoiceNumber || undefined,
        supplier_type: 'supplier',
        supplier_id: supplier?.id,
        due_date: dueDate || undefined,
        currency_id: currency?.id,
        exchange_rate: decimalOr(exchangeRate, '1.000000'),
        notes: notes || undefined,
        sources: sources
            .map((item) => ({
                source_type: item.type,
                source_id: item.id,
                line_quantities: Object.fromEntries(lines
                    .filter((line) => line.sourceType === item.type && line.sourceId === item.id && line.include && line.quantity !== '')
                    .map((line) => [line.lineId, decimalOr(line.quantity)])),
            }))
            .filter((item) => Object.keys(item.line_quantities).length > 0),
    });

    const runPreview = async () => {
        if (hasLoadingSources) return;

        setBusy(true);
        setError(null);
        try {
            setPreview(await previewPurchaseInvoice(payload()));
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    const createInvoice = async () => {
        if (hasLoadingSources) return;

        setBusy(true);
        setError(null);
        try {
            const invoice = await createPurchaseInvoice(payload());
            navigate(`/invoices/${String(invoice.id ?? '')}?from=purchase`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    return (
        <PurchaseDocumentShell
            header={<PurchasePageHeader
                title="Create Supplier Invoice"
                description="Create a canonical supplier invoice from eligible Purchase sources."
            />}
        >
            <ErrorAlert error={error} />
            <Panel title="Invoice header">
                <div className="grid gap-4 md:grid-cols-4">
                    <SupplierLookupSelect value={supplier} onChange={(value) => {
                        if (sources.length > 0 && value?.id !== supplier?.id && !window.confirm('Changing supplier clears selected invoice sources.')) return;
                        setSupplier(value);
                        if (value?.id !== supplier?.id) clearInvoiceSources();
                        setPreview(null);
                    }} error={errorFor('supplier_id')} />
                    <CurrencyLookupSelect value={currency} onChange={(value) => {
                        if (sources.length > 0 && value?.id !== currency?.id && !window.confirm('Changing currency clears selected invoice sources.')) return;
                        setCurrency(value);
                        if (value?.id !== currency?.id) clearInvoiceSources();
                        setPreview(null);
                    }} error={errorFor('currency_id')} />
                    <Input label="Invoice date" type="date" value={invoiceDate} error={errorFor('invoice_date')} onChange={(event) => { setInvoiceDate(event.target.value); setPreview(null); }} />
                    <Input label="Due date" type="date" value={dueDate} error={errorFor('due_date')} onChange={(event) => { setDueDate(event.target.value); setPreview(null); }} />
                    <Input label="Invoice number" value={invoiceNumber} error={errorFor('invoice_number')} onChange={(event) => { setInvoiceNumber(event.target.value); setPreview(null); }} />
                    <DecimalInput label="Exchange rate" value={exchangeRate} error={errorFor('exchange_rate')} onChange={(event) => { setExchangeRate(event.target.value); setPreview(null); }} />
                </div>
            </Panel>
            <Panel title="Sources">
                <div className="grid gap-4 md:grid-cols-[180px_minmax(0,1fr)_auto] md:items-end">
                    <Select label="Source type" value={sourceType} options={[{ value: 'goods_receipt_note', label: 'GRN' }, { value: 'purchase_order', label: 'PO' }]} onChange={(event) => { setSourceType(event.target.value as PurchaseInvoiceSourceType); setSource(null); }} />
                    {sourceType === 'goods_receipt_note'
                        ? <GoodsReceiptLookupSelect eligibility="invoiceable" value={source} onChange={setSource} excludeIds={excludedSourceIds} />
                        : <PurchaseOrderLookupSelect eligibility="invoiceable" value={source} onChange={setSource} excludeIds={excludedSourceIds} />}
                    <Button
                        type="button"
                        variant="secondary"
                        loading={hasLoadingSources}
                        disabled={!canAddSource}
                        onClick={() => {
                            if (!source?.id) return;
                            void addInvoiceSource(sourceType, source.id, source.name);
                        }}
                    >
                        Add source
                    </Button>
                </div>
                {sources.length > 0 && (
                    <div className="mt-3 flex flex-wrap gap-2 text-sm">
                        {sources.map((item) => (
                            <span key={`${item.type}-${item.id}`} className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-slate-700">
                                {item.label}
                                <button type="button" className="font-semibold text-rose-600" onClick={() => removeInvoiceSource(item)}>Remove</button>
                            </span>
                        ))}
                    </div>
                )}
            </Panel>
            <Panel title="Invoiceable lines">
                <PurchaseInvoiceLineTable
                    lines={lines}
                    sourceIndex={(line) => sources.findIndex((sourceItem) => (
                        sourceItem.type === line.sourceType && sourceItem.id === line.sourceId
                    ))}
                    onChange={(next) => { setLines(next); setPreview(null); }}
                    errorFor={errorFor}
                />
            </Panel>
            <Panel title="Notes">
                <Textarea label="Notes" value={notes} error={errorFor('notes')} onChange={(event) => { setNotes(event.target.value); setPreview(null); }} />
            </Panel>
            <PurchaseInvoicePreview preview={preview} />
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" loading={busy} disabled={hasLoadingSources} onClick={runPreview}>Preview</Button>
                <Button type="button" loading={busy} disabled={hasLoadingSources} onClick={() => void createInvoice()}>Create invoice</Button>
            </div>
        </PurchaseDocumentShell>
    );
}

function invoiceLineFromPurchaseOrder(
    row: PurchaseOrderLine,
    sourceId: number,
    sourceLabel: string,
): EditablePurchaseInvoiceLine | null {
    const lineId = normalizeSourceId(row.id);
    if (lineId === null) return null;

    return {
        sourceType: 'purchase_order',
        sourceId,
        sourceLabel,
        lineId,
        include: false,
        itemName: row.item?.name ?? '-',
        sourceQty: row.ordered_quantity,
        previouslyInvoiced: row.invoiced_quantity ?? '0.000000',
        remainingQty: row.remaining_invoiceable_quantity ?? row.remaining_quantity ?? '0.000000',
        quantity: '',
    };
}

function invoiceLineFromGoodsReceipt(
    row: GoodsReceiptLine,
    sourceId: number,
    sourceLabel: string,
): EditablePurchaseInvoiceLine | null {
    const lineId = normalizeSourceId(row.id);
    if (lineId === null) return null;

    return {
        sourceType: 'goods_receipt_note',
        sourceId,
        sourceLabel,
        lineId,
        include: false,
        itemName: row.item?.name ?? '-',
        sourceQty: row.accepted_quantity,
        previouslyInvoiced: row.invoiced_quantity ?? '0.000000',
        remainingQty: row.remaining_invoiceable_quantity ?? row.remaining_quantity ?? '0.000000',
        quantity: '',
    };
}

function isInvoiceLine(line: EditablePurchaseInvoiceLine | null): line is EditablePurchaseInvoiceLine {
    return line !== null;
}

function fallbackSourceLabel(type: PurchaseInvoiceSourceType, sourceId: number): string {
    return type === 'purchase_order' ? `Purchase Order #${sourceId}` : `Goods Receipt #${sourceId}`;
}
