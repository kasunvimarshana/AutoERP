import { useEffect, useState } from 'react';
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
import { createPurchaseInvoice, getGoodsReceipt, getInvoiceableGoodsReceiptLines, getInvoiceablePurchaseOrderLines, getPurchaseOrder, previewPurchaseInvoice, type GoodsReceiptLine, type PurchaseInvoicePayload, type PurchaseOrderLine } from '../purchaseApi';
import { decimalOr, todayDate } from '../purchaseFormUtils';
import { CurrencyLookupSelect, GoodsReceiptLookupSelect, PurchaseOrderLookupSelect, SupplierLookupSelect } from '../components/PurchaseLookups';
import { PurchaseInvoicePreview } from '../components/PurchaseInvoicePreview';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import {
    PurchaseInvoiceLineTable,
    type EditablePurchaseInvoiceLine,
    type PurchaseInvoiceSourceType,
} from '../components/PurchaseInvoiceLineTable';

export default function PurchaseInvoiceCreatePage() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const [sourceType, setSourceType] = useState<PurchaseInvoiceSourceType>('goods_receipt_note');
    const [source, setSource] = useState<NamedResource | null>(null);
    const [sources, setSources] = useState<Array<{ type: PurchaseInvoiceSourceType; id: number; label: string }>>([]);
    const [lines, setLines] = useState<EditablePurchaseInvoiceLine[]>([]);
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

    const appendSource = async (type: PurchaseInvoiceSourceType, sourceId: number, sourceLabel: string) => {
        if (sources.some((current) => current.type === type && current.id === sourceId)) return;
        setBusy(true);
        setError(null);
        try {
            if (type === 'purchase_order') {
                const rows = await getInvoiceablePurchaseOrderLines(sourceId);
                setLines((current) => [...current, ...rows.map((row: PurchaseOrderLine) => ({
                    sourceType: type,
                    sourceId,
                    sourceLabel,
                    lineId: row.id ?? 0,
                    include: false,
                    itemName: row.item?.name ?? '-',
                    sourceQty: row.ordered_quantity,
                    previouslyInvoiced: row.invoiced_quantity ?? '0.000000',
                    remainingQty: row.remaining_invoiceable_quantity ?? row.remaining_quantity ?? '0.000000',
                    quantity: '',
                }))]);
            } else {
                const rows = await getInvoiceableGoodsReceiptLines(sourceId);
                setLines((current) => [...current, ...rows.map((row: GoodsReceiptLine) => ({
                    sourceType: type,
                    sourceId,
                    sourceLabel,
                    lineId: row.id ?? 0,
                    include: false,
                    itemName: row.item?.name ?? '-',
                    sourceQty: row.accepted_quantity,
                    previouslyInvoiced: row.invoiced_quantity ?? '0.000000',
                    remainingQty: row.remaining_invoiceable_quantity ?? row.remaining_quantity ?? '0.000000',
                    quantity: '',
                }))]);
            }
            setSources((current) => [...current, { type, id: sourceId, label: sourceLabel }]);
            setSource(null);
            setPreview(null);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    const addSource = async () => {
        if (!source?.id) return;
        await appendSource(sourceType, source.id, source.name);
    };

    useEffect(() => {
        const poId = Number(searchParams.get('purchase_order_id'));
        const grnId = Number(searchParams.get('goods_receipt_id') ?? searchParams.get('grn_id'));
        if (Number.isFinite(poId) && poId > 0 && sources.length === 0 && !source) {
            void getPurchaseOrder(poId)
                .then((order) => {
                    setSourceType('purchase_order');
                    const label = order.purchase_order_number ?? `Purchase Order #${order.id}`;
                    setSource({ id: order.id, code: order.purchase_order_number, name: label });
                    if (order.supplier) setSupplier(order.supplier);
                    if (order.currency) setCurrency(order.currency);
                    void appendSource('purchase_order', order.id, label);
                })
                .catch((requestError) => setError(toApiError(requestError)));
        } else if (Number.isFinite(grnId) && grnId > 0 && sources.length === 0 && !source) {
            void getGoodsReceipt(grnId)
                .then((grn) => {
                    setSourceType('goods_receipt_note');
                    const label = grn.grn_number ?? `Goods Receipt #${grn.id}`;
                    setSource({ id: grn.id, code: grn.grn_number, name: label });
                    if (grn.supplier) setSupplier(grn.supplier);
                    void appendSource('goods_receipt_note', grn.id, label);
                })
                .catch((requestError) => setError(toApiError(requestError)));
        }
        // Query source is consumed only while the form has no selected source.
    }, [searchParams, source, sources.length]);

    const removeSource = (item: { type: PurchaseInvoiceSourceType; id: number }) => {
        setSources((current) => current.filter((sourceItem) => sourceItem.type !== item.type || sourceItem.id !== item.id));
        setLines((current) => current.filter((line) => line.sourceType !== item.type || line.sourceId !== item.id));
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
                line_quantities: Object.fromEntries(lines.filter((line) => line.sourceType === item.type && line.sourceId === item.id && line.include && line.quantity !== '').map((line) => [line.lineId, decimalOr(line.quantity)])),
            }))
            .filter((item) => Object.keys(item.line_quantities).length > 0),
    });

    const runPreview = async () => {
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
                        if (value?.id !== supplier?.id) {
                            setSources([]);
                            setLines([]);
                        }
                        setPreview(null);
                    }} error={errorFor('supplier_id')} />
                    <CurrencyLookupSelect value={currency} onChange={(value) => {
                        if (sources.length > 0 && value?.id !== currency?.id && !window.confirm('Changing currency clears selected invoice sources.')) return;
                        setCurrency(value);
                        if (value?.id !== currency?.id) {
                            setSources([]);
                            setLines([]);
                        }
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
                    {sourceType === 'goods_receipt_note' ? <GoodsReceiptLookupSelect value={source} onChange={setSource} /> : <PurchaseOrderLookupSelect value={source} onChange={setSource} />}
                    <Button type="button" variant="secondary" loading={busy} onClick={() => void addSource()}>Add source</Button>
                </div>
                {sources.length > 0 && <div className="mt-3 flex flex-wrap gap-2 text-sm">{sources.map((item) => <span key={`${item.type}-${item.id}`} className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-slate-700">{item.label}<button type="button" className="font-semibold text-rose-600" onClick={() => removeSource(item)}>Remove</button></span>)}</div>}
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
                <Button type="button" variant="secondary" loading={busy} onClick={runPreview}>Preview</Button>
                <Button type="button" loading={busy} onClick={async () => {
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
                }}>Create invoice</Button>
            </div>
        </PurchaseDocumentShell>
    );
}
