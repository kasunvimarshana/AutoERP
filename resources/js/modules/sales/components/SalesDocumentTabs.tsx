import { lazy, Suspense, useState, type ReactNode } from 'react';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { LoadingState } from '@/shared/components/LoadingState';
import type { SalesHeaderAdjustment, SalesLine, SalesOrder, SalesQuotation } from '../salesTypes';

type Document = SalesQuotation | SalesOrder;

const LazyLines = lazy(async () => ({ default: LinesTab }));
const LazyAdjustments = lazy(async () => ({ default: AdjustmentsTab }));
const LazyWorkflow = lazy(async () => ({ default: WorkflowTab }));

export function SalesDocumentTabs({ document, summary }: { document: Document; summary: ReactNode }) {
    const [active, setActive] = useState<'summary' | 'lines' | 'adjustments' | 'workflow'>('summary');
    const tabs = ['summary', 'lines', 'adjustments', 'workflow'] as const;

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap gap-2 border-b border-slate-200">
                {tabs.map((tab) => <button key={tab} type="button" className={`border-b-2 px-3 py-2 text-sm font-semibold ${active === tab ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500'}`} onClick={() => setActive(tab)}>{tab.replaceAll('_', ' ')}</button>)}
            </div>
            {active === 'summary' ? summary : (
                <Suspense fallback={<LoadingState />}>
                    {active === 'lines' && <LazyLines document={document} />}
                    {active === 'adjustments' && <LazyAdjustments document={document} />}
                    {active === 'workflow' && <LazyWorkflow document={document} />}
                </Suspense>
            )}
        </div>
    );
}

function LinesTab({ document }: { document: Document }) {
    const columns: DataColumn<SalesLine>[] = [
        { key: 'item', header: 'Item', render: (row) => [row.item?.code, row.item?.name].filter(Boolean).join(' - ') || '-' },
        { key: 'quantity', header: 'Qty', render: (row) => row.quantity ?? row.ordered_quantity ?? '-' },
        { key: 'uom', header: 'UOM', render: (row) => row.uom?.code ?? row.uom?.name ?? '-' },
        { key: 'price', header: 'Unit price', render: (row) => row.unit_price },
        { key: 'delivered', header: 'Delivered', render: (row) => row.delivered_quantity ?? '-' },
        { key: 'invoiced', header: 'Invoiced', render: (row) => row.invoiced_quantity ?? '-' },
        { key: 'total', header: 'Total', render: (row) => row.line_total ?? '-' },
    ];
    return <DataTable rows={document.lines ?? []} columns={columns} rowKey={(row) => row.id ?? row.line_number ?? 0} />;
}

function AdjustmentsTab({ document }: { document: Document }) {
    const columns: DataColumn<SalesHeaderAdjustment>[] = [
        { key: 'name', header: 'Name', render: (row) => row.name },
        { key: 'type', header: 'Type', render: (row) => row.adjustment_type.replaceAll('_', ' ') },
        { key: 'effect', header: 'Effect', render: (row) => row.effect },
        { key: 'amount', header: 'Amount', render: (row) => row.amount },
        { key: 'remaining', header: 'Remaining', render: (row) => row.remaining_amount ?? '-' },
    ];
    return <DataTable rows={document.adjustments ?? []} columns={columns} rowKey={(row) => row.id ?? row.name} />;
}

function WorkflowTab({ document }: { document: Document }) {
    const order = 'sales_order_number' in document ? document : null;
    const rows = [
        ['Created', order?.sales_order_date ?? (document as SalesQuotation).quotation_date],
        ['Status', document.status?.replaceAll('_', ' ')],
        ['Allocated', order?.allocated_total],
        ['Delivered', order?.delivered_total],
        ['Invoiced', order?.invoiced_total],
        ['Returned', order?.returned_total],
    ].filter((row) => row[1]);
    return <dl className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2">{rows.map(([label, value]) => <div key={label}><dt className="text-xs font-semibold uppercase text-slate-500">{label}</dt><dd className="mt-1 text-sm font-semibold text-slate-900">{value}</dd></div>)}</dl>;
}
