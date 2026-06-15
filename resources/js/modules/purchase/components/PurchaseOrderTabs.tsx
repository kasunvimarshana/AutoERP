import { lazy, Suspense, type ReactNode } from 'react';

import { LinkButton } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Tabs } from '@/shared/components/Tabs';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { formatDate } from '@/shared/utils/formatDate';
import type { PurchaseHeaderAdjustment, PurchaseOrder, PurchaseOrderLine } from '../purchaseApi';

type Tab = 'overview' | 'lines' | 'grns' | 'invoices' | 'payments' | 'returns' | 'timeline' | 'adjustments';
type RelatedDocument = Record<string, unknown> & { id?: number; status?: string | null };

const LazyLines = lazy(async () => ({ default: ({ order }: { order: PurchaseOrder }) => <LinesTable order={order} /> }));
const LazyAdjustments = lazy(async () => ({ default: ({ order }: { order: PurchaseOrder }) => <AdjustmentTable order={order} /> }));

const tabs = [
    ['overview', 'Overview'],
    ['lines', 'Lines'],
    ['grns', 'GRNs'],
    ['invoices', 'Invoices'],
    ['payments', 'Payments'],
    ['returns', 'Returns'],
    ['timeline', 'Timeline'],
    ['adjustments', 'Adjustments'],
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
                    {state.activeTab === 'grns' && <RelatedDocuments order={order} type="grn" title="Goods receipts" createHref={`/purchase/goods-receipts/create?purchase_order_id=${order.id}`} />}
                    {state.activeTab === 'invoices' && <RelatedDocuments order={order} type="invoice" title="Supplier invoices" createHref={`/purchase/invoices/create?purchase_order_id=${order.id}`} />}
                    {state.activeTab === 'payments' && <RelatedDocuments order={order} type="payment" title="Payments" createHref={`/purchase/payments/prepare?purchase_order_id=${order.id}`} />}
                    {state.activeTab === 'returns' && <RelatedDocuments order={order} type="return" title="Returns" createHref={`/purchase/returns/create?purchase_order_id=${order.id}`} />}
                    {state.activeTab === 'timeline' && <PurchaseTimeline order={order} />}
                    {state.activeTab === 'adjustments' && <LazyAdjustments order={order} />}
                </Suspense>
            </div>
        </>
    );
}

function LinesTable({ order }: { order: PurchaseOrder }) {
    const columns: DataColumn<PurchaseOrderLine>[] = [
        { key: 'line', header: 'Line', render: (line) => line.line_number ?? '-' },
        { key: 'item', header: 'Item', render: (line) => line.item?.name ?? line.description ?? '-' },
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
        { key: 'amount', header: 'Amount', render: (adjustment) => <MoneyDisplay value={adjustment.amount} currency={order.currency?.code ?? undefined} /> },
        { key: 'allocated', header: 'Allocated', render: (adjustment) => <MoneyDisplay value={adjustment.allocated_amount} currency={order.currency?.code ?? undefined} /> },
        { key: 'remaining', header: 'Remaining', render: (adjustment) => <MoneyDisplay value={adjustment.remaining_amount ?? adjustment.amount} currency={order.currency?.code ?? undefined} /> },
    ];

    return <DataTable rows={order.adjustments ?? []} columns={columns} rowKey={(adjustment) => adjustment.id ?? adjustment.name} emptyMessage="No header adjustments were added." />;
}

function WorkflowProgress({ order }: { order: PurchaseOrder }) {
    const steps = [
        ['Ordered', order.purchase_order_date],
        ['Received', hasPositive(order.received_quantity) ? order.received_quantity : null],
        ['Invoiced', hasPositive(order.invoiced_quantity) ? order.invoiced_quantity : null],
        ['Returned', hasPositive(order.returned_quantity) ? order.returned_quantity : null],
        ['Closed', order.closed_at],
    ];

    return (
        <div className="grid gap-3 md:grid-cols-5">
            {steps.map(([label, value]) => (
                <div key={label} className={`rounded-lg border p-4 ${value ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50'}`}>
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
                    <p className={`mt-2 text-sm font-semibold ${value ? 'text-emerald-800' : 'text-slate-500'}`}>{value ? 'In progress' : 'Pending'}</p>
                </div>
            ))}
        </div>
    );
}

function RelatedDocuments({ order, type, title, createHref }: { order: PurchaseOrder; type: 'grn' | 'invoice' | 'payment' | 'return'; title: string; createHref: string }) {
    const rows = getRelatedDocuments(order, type);
    const columns: DataColumn<RelatedDocument>[] = [
        { key: 'number', header: 'Document', render: (row) => documentNumber(row, type) },
        { key: 'date', header: 'Date', render: (row) => formatDate(String(row.date ?? row.created_at ?? row.received_date ?? row.invoice_date ?? row.payment_date ?? row.return_date ?? '')) },
        { key: 'amount', header: 'Amount', render: (row) => <MoneyDisplay value={String(row.amount ?? row.grand_total ?? row.invoice_total ?? row.allocated_amount ?? '')} currency={order.currency?.code ?? undefined} /> },
        { key: 'status', header: 'Status', render: (row) => row.status ? <StatusBadge status={String(row.status)} /> : '-' },
    ];

    return (
        <div className="space-y-4">
            <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h2 className="font-semibold text-slate-900">{title}</h2>
                    <p className="mt-1 text-sm text-slate-500">Related documents stay visible from the purchase order workspace.</p>
                </div>
                <LinkButton to={createHref} variant="secondary">Create</LinkButton>
            </div>
            <DataTable rows={rows} columns={columns} rowKey={(row) => row.id ?? documentNumber(row, type)} emptyMessage={`No ${title.toLowerCase()} are linked to this purchase order yet.`} />
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

function getRelatedDocuments(order: PurchaseOrder, type: 'grn' | 'invoice' | 'payment' | 'return'): RelatedDocument[] {
    const source = order as PurchaseOrder & Record<string, unknown>;
    const keys = {
        grn: ['goods_receipts', 'grns', 'receipts'],
        invoice: ['invoices', 'purchase_invoices', 'invoice_links'],
        payment: ['payments', 'payment_links'],
        return: ['returns', 'purchase_returns'],
    }[type];

    for (const key of keys) {
        if (Array.isArray(source[key])) return source[key] as RelatedDocument[];
    }
    return [];
}

function documentNumber(row: RelatedDocument, type: 'grn' | 'invoice' | 'payment' | 'return') {
    const keys = {
        grn: ['grn_number', 'number', 'code'],
        invoice: ['invoice_number', 'number', 'code'],
        payment: ['payment_number', 'number', 'code'],
        return: ['return_number', 'number', 'code'],
    }[type];

    for (const key of keys) {
        if (row[key]) return String(row[key]);
    }
    return row.id ? `#${row.id}` : '-';
}

function hasPositive(value?: string | null) {
    return Number(value ?? 0) > 0;
}
