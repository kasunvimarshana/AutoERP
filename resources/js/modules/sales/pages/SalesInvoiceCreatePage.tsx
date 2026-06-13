import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { readableRelation } from '@/shared/utils/object';
import {
    createSalesInvoice,
    getInvoiceableSalesOrderLines,
    getSalesDelivery,
    previewSalesInvoice,
} from '../salesApi';
import type { SalesInvoicePayload, SalesInvoicePreview } from '../salesTypes';
import { SalesDeliveryLookupSelect, SalesOrderLookupSelect } from '../components/SalesLookups';

interface InvoiceSourceDraft {
    key: string;
    type: 'sales_delivery' | 'sales_order';
    resource: NamedResource;
    lines: Array<{
        id: number;
        item?: NamedResource | null;
        available: string;
        quantity: string;
    }>;
}

const previewFields: Array<{ key: keyof SalesInvoicePreview; label: string }> = [
    { key: 'subtotal', label: 'Subtotal' },
    { key: 'discountTotal', label: 'Discount total' },
    { key: 'taxTotal', label: 'Tax total' },
    { key: 'chargeTotal', label: 'Charge total' },
    { key: 'adjustmentTotal', label: 'Adjustment total' },
    { key: 'grandTotal', label: 'Grand total' },
];

export default function SalesInvoiceCreatePage() {
    const navigate = useNavigate();
    const [sourceType, setSourceType] = useState<'sales_delivery' | 'sales_order'>('sales_delivery');
    const [selectedSource, setSelectedSource] = useState<NamedResource | null>(null);
    const [sources, setSources] = useState<InvoiceSourceDraft[]>([]);
    const [invoiceDate, setInvoiceDate] = useState(new Date().toISOString().slice(0, 10));
    const [dueDate, setDueDate] = useState('');
    const [notes, setNotes] = useState('');
    const [preview, setPreview] = useState<SalesInvoicePreview | null>(null);
    const [sourceLoading, setSourceLoading] = useState(false);
    const [previewing, setPreviewing] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const errorFor = (name: string) => fieldError(error, name);

    const addSource = async () => {
        if (!selectedSource || sourceLoading) return;
        const key = `${sourceType}:${selectedSource.id}`;
        if (sources.some((source) => source.key === key)) return;
        setSourceLoading(true);
        setError(null);
        try {
            const lines = sourceType === 'sales_delivery'
                ? (await getSalesDelivery(selectedSource.id)).lines?.map((line) => ({
                    id: line.id,
                    item: line.item,
                    available: line.remaining_quantity,
                    quantity: line.remaining_quantity,
                })) ?? []
                : (await getInvoiceableSalesOrderLines(selectedSource.id)).map((line) => ({
                    id: line.id,
                    item: line.item,
                    available: line.remaining_invoiceable_quantity ?? line.remaining_quantity ?? '0.000000',
                    quantity: line.remaining_invoiceable_quantity ?? line.remaining_quantity ?? '0.000000',
                }));
            setSources((current) => [...current, { key, type: sourceType, resource: selectedSource, lines }]);
            setSelectedSource(null);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSourceLoading(false);
        }
    };

    const payload = (): SalesInvoicePayload => ({
        invoice_date: invoiceDate,
        due_date: dueDate || undefined,
        notes: notes || undefined,
        sources: sources.map((source) => ({
            source_type: source.type,
            source_id: source.resource.id,
            line_quantities: Object.fromEntries(source.lines.map((line) => [line.id, line.quantity])),
        })),
    });

    const columns: DataColumn<InvoiceSourceDraft>[] = [
        { key: 'source', header: 'Source', render: (row) => `${row.type === 'sales_delivery' ? 'Delivery' : 'Order'}: ${row.resource.code ?? row.resource.name}` },
        { key: 'lines', header: 'Lines', render: (row) => row.lines.length },
        { key: 'quantity', header: 'Selected quantities', render: (row) => row.lines.map((line) => `${readableRelation(line.item)}: ${line.quantity}`).join(', ') },
        { key: 'actions', header: 'Actions', render: (row) => <Button type="button" variant="danger" onClick={() => setSources((current) => current.filter((source) => source.key !== row.key))}>Remove</Button> },
    ];

    return (
        <>
            <ContentHeader title="Create customer invoice" description="Combine delivery or order sources; Invoice owns the resulting invoice and allocations." />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                if (submitting || sources.length === 0) return;
                setSubmitting(true);
                setError(null);
                try {
                    const invoice = await createSalesInvoice(payload());
                    navigate(`/invoices/${invoice.id}`);
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}>
                <ErrorAlert error={error} />
                <Panel title="Invoice header">
                    <div className="grid gap-4 md:grid-cols-3">
                        <Input type="date" label="Invoice date" value={invoiceDate} error={errorFor('invoice_date')} onChange={(event) => setInvoiceDate(event.target.value)} />
                        <Input type="date" label="Due date" value={dueDate} error={errorFor('due_date')} onChange={(event) => setDueDate(event.target.value)} />
                    </div>
                    <div className="mt-4"><Textarea label="Notes" value={notes} error={errorFor('notes')} onChange={(event) => setNotes(event.target.value)} /></div>
                </Panel>
                <Panel title="Sources">
                    <div className="grid gap-4 md:grid-cols-[180px_minmax(0,1fr)_auto]">
                        <Select label="Source type" value={sourceType} options={[{ value: 'sales_delivery', label: 'Sales delivery' }, { value: 'sales_order', label: 'Sales order' }]} onChange={(event) => { setSourceType(event.target.value as typeof sourceType); setSelectedSource(null); }} />
                        {sourceType === 'sales_delivery'
                            ? <SalesDeliveryLookupSelect value={selectedSource} onChange={setSelectedSource} />
                            : <SalesOrderLookupSelect value={selectedSource} onChange={setSelectedSource} />}
                        <div className="self-end"><Button type="button" variant="secondary" loading={sourceLoading} disabled={!selectedSource || sourceLoading} onClick={() => void addSource()}>Add source</Button></div>
                    </div>
                    <div className="mt-4 space-y-4">
                        {sources.map((source) => (
                            <div key={source.key} className="rounded-lg border border-slate-200 p-4">
                                <h3 className="font-semibold text-slate-900">{source.resource.code ?? source.resource.name}</h3>
                                <div className="mt-3 grid gap-3 md:grid-cols-2">
                                    {source.lines.map((line) => (
                                        <DecimalInput
                                            key={line.id}
                                            label={`${readableRelation(line.item)} (max ${line.available})`}
                                            value={line.quantity}
                                            onChange={(event) => setSources((current) => current.map((candidate) => candidate.key === source.key ? { ...candidate, lines: candidate.lines.map((candidateLine) => candidateLine.id === line.id ? { ...candidateLine, quantity: event.target.value } : candidateLine) } : candidate))}
                                        />
                                    ))}
                                </div>
                            </div>
                        ))}
                        <DataTable rows={sources} columns={columns} rowKey={(row) => row.key} emptyMessage="Add one or more delivery or order sources." />
                    </div>
                </Panel>
                {preview && (
                    <Panel title="Backend preview">
                        <dl className="grid gap-3 text-sm sm:grid-cols-3">
                            {previewFields.map((field) => (
                                <div key={field.key}><dt className="text-slate-500">{field.label}</dt><dd className="font-semibold">{preview[field.key] ?? '-'}</dd></div>
                            ))}
                        </dl>
                    </Panel>
                )}
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" loading={previewing} disabled={sources.length === 0 || previewing} onClick={async () => {
                        setPreviewing(true);
                        setError(null);
                        try {
                            setPreview(await previewSalesInvoice(payload()));
                        } catch (requestError) {
                            setError(toApiError(requestError));
                        } finally {
                            setPreviewing(false);
                        }
                    }}>Preview</Button>
                    <Button type="submit" loading={submitting} disabled={sources.length === 0 || sourceLoading || previewing}>Create invoice</Button>
                </div>
            </form>
        </>
    );
}
