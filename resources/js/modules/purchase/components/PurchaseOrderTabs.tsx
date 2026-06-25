import { lazy, Suspense, type ReactNode } from 'react';
import { Link } from 'react-router-dom';

import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Tabs } from '@/shared/components/Tabs';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { formatDate } from '@/shared/utils/formatDate';
import type { PurchaseHeaderAdjustment, PurchaseOrder, PurchaseOrderLine, PurchaseRelatedDocument } from '../purchaseApi';

type Tab = 'overview' | 'lines' | 'related' | 'adjustments' | 'activity';

const LazyLines = lazy(async () => ({ default: ({ order }: { order: PurchaseOrder }) => <LinesTable order={order} /> }));
const LazyAdjustments = lazy(async () => ({ default: ({ order }: { order: PurchaseOrder }) => <AdjustmentTable order={order} /> }));

const tabs = [
    ['overview', 'Overview'],
    ['lines', 'Lines'],
    ['related', 'Related Documents'],
    ['adjustments', 'Adjustments'],
    ['activity', 'Activity'],
].map(([id, label]) => ({ id: id as Tab, label }));

export function PurchaseOrderTabs({ order, summary }: { order: PurchaseOrder; summary: ReactNode }) {
    const state = useOnDemandTab<Tab>('overview');

    return (
        <>
            <Tabs tabs={tabs} active={state.activeTab} onChange={state.openTab} />
            <div className="p-5">
                <Suspense fallback={<LoadingState />}>
                    {state.activeTab === 'overview' && (
                        <div className="space-y-5">
                            {summary}
                            <WorkflowProgress order={order} />
                        </div>
                    )}
                    {state.activeTab === 'lines' && <LazyLines order={order} />}
                    {state.activeTab === 'related' && <RelatedDocumentTimeline order={order} />}
                    {state.activeTab === 'adjustments' && <LazyAdjustments order={order} />}
                    {state.activeTab === 'activity' && <PurchaseTimeline order={order} />}
                </Suspense>
            </div>
        </>
    );
}

function LinesTable({ order }: { order: PurchaseOrder }) {
    const columns: DataColumn<PurchaseOrderLine>[] = [
        { key: 'line', header: 'Line', render: (line) => line.line_number ?? '-' },
        { key: 'item', header: 'Item', render: (line) => line.item?.name ?? line.description ?? '-' },
        { key: 'variant', header: 'Variant', render: (line) => line.item_variant?.code ?? line.item_variant?.name ?? '-' },
        { key: 'uom', header: 'UOM', render: (line) => line.uom?.code ?? line.uom?.name ?? '-' },
        { key: 'ordered', header: 'Ordered', render: (line) => <QuantityDisplay value={line.ordered_quantity} precision={6} /> },
        { key: 'received', header: 'Received', render: (line) => <QuantityDisplay value={line.received_quantity} precision={6} /> },
        { key: 'invoiced', header: 'Invoiced', render: (line) => <QuantityDisplay value={line.invoiced_quantity} precision={6} /> },
        { key: 'returned', header: 'Returned', render: (line) => <QuantityDisplay value={line.returned_quantity} precision={6} /> },
        { key: 'remaining', header: 'Remaining', render: (line) => <QuantityDisplay value={line.remaining_quantity} precision={6} /> },
        { key: 'unit_price', header: 'Unit price', render: (line) => <MoneyDisplay value={line.unit_price} currency={order.currency?.code ?? undefined} /> },
        { key: 'total', header: 'Total', render: (line) => <MoneyDisplay value={line.line_total} currency={order.currency?.code ?? undefined} /> },
        { key: 'status', header: 'Status', render: (line) => line.status ? <StatusBadge status={line.status} /> : '-' },
    ];

    return <DataTable rows={order.lines ?? []} columns={columns} rowKey={(line) => line.id ?? line.line_number ?? line.description ?? 'line'} emptyMessage="No order lines were found for this purchase order." />;
}

function AdjustmentTable({ order }: { order: PurchaseOrder }) {
    const columns: DataColumn<PurchaseHeaderAdjustment>[] = [
        { key: 'name', header: 'Name', render: (adjustment) => adjustment.name },
        { key: 'type', header: 'Type', render: (adjustment) => adjustment.adjustment_type },
        { key: 'effect', header: 'Effect', render: (adjustment) => adjustment.effect },
        { key: 'calculation', header: 'Calculation', render: (adjustment) => adjustment.calculation_type },
        { key: 'mapping', header: 'Finance Mapping', render: (adjustment) => adjustment.finance_mapping?.cost_treatment ?? adjustment.cost_treatment ?? '-' },
        { key: 'amount', header: 'Amount', render: (adjustment) => <MoneyDisplay value={adjustment.amount} currency={order.currency?.code ?? undefined} /> },
        { key: 'allocated', header: 'Allocated', render: (adjustment) => <MoneyDisplay value={adjustment.allocated_amount} currency={order.currency?.code ?? undefined} /> },
        { key: 'remaining', header: 'Remaining', render: (adjustment) => <MoneyDisplay value={adjustment.remaining_amount ?? adjustment.amount} currency={order.currency?.code ?? undefined} /> },
    ];

    return <DataTable rows={order.adjustments ?? []} columns={columns} rowKey={(adjustment) => adjustment.id ?? adjustment.name} emptyMessage="No header adjustments were added." />;
}

function RelatedDocumentTimeline({ order }: { order: PurchaseOrder }) {
    const related = order.related_documents;
    const rows: PurchaseRelatedDocument[] = [
        ...(related?.goods_receipts ?? []),
        ...(related?.supplier_invoices ?? []),
        ...(related?.payments ?? []),
        ...(related?.returns ?? []),
        ...(related?.debit_notes ?? []),
    ].sort((left, right) => (left.date ?? '').localeCompare(right.date ?? ''));
    const columns: DataColumn<PurchaseRelatedDocument>[] = [
        { key: 'type', header: 'Type', render: (row) => row.type.replaceAll('_', ' ') },
        { key: 'document', header: 'Document', render: (row) => row.url ? <Link className="font-semibold text-sky-700 hover:underline" to={row.url}>{row.number ?? `#${row.id}`}</Link> : row.number ?? `#${row.id}` },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.date) },
        { key: 'status', header: 'Status', render: (row) => row.status ? <StatusBadge status={row.status} /> : '-' },
    ];

    return <DataTable rows={rows} columns={columns} rowKey={(row) => `${row.type}-${row.id}`} emptyMessage="No related documents are linked to this purchase order yet." />;
}

function WorkflowProgress({ order }: { order: PurchaseOrder }) {
    const steps = [
        ['PO', order.workflow_status ?? order.status],
        ['GRN', order.receipt_status],
        ['Supplier Invoice', order.invoice_status],
        ['Payment', order.related_documents?.payments?.length ? 'linked' : 'not_paid'],
        ['Purchase Return', order.return_status],
    ];

    return (
        <div className="grid gap-3 md:grid-cols-5">
            {steps.map(([label, value]) => (
                <div key={label} className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p className="text-xs font-semibold uppercase tracking-normal text-slate-500">{label}</p>
                    <p className="mt-2 text-sm font-semibold text-slate-800">{String(value ?? '-').replaceAll('_', ' ')}</p>
                </div>
            ))}
        </div>
    );
}

function PurchaseTimeline({ order }: { order: PurchaseOrder }) {
    const events = [
        ['Purchase order created', order.purchase_order_date],
        ['Approved', order.approved_at],
        ['Expected delivery', order.expected_delivery_date],
        ['Closed', order.closed_at],
    ].filter((event): event is [string, string] => Boolean(event[1]));

    return (
        <ol className="space-y-3">
            {events.map(([label, date]) => (
                <li key={`${label}-${date}`} className="rounded-lg border border-slate-200 bg-white p-4">
                    <p className="text-sm font-semibold text-slate-900">{label}</p>
                    <p className="mt-1 text-sm text-slate-500">{formatDate(date)}</p>
                </li>
            ))}
            {events.length === 0 && <li className="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No timeline events are available.</li>}
        </ol>
    );
}
