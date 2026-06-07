import { lazy, Suspense, type ReactNode } from 'react';
import { Tabs } from '@/shared/components/Tabs';
import { LoadingState } from '@/shared/components/LoadingState';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import type { PurchaseOrder } from '../purchaseApi';

type Tab = 'summary' | 'lines' | 'adjustments';

const LazyLines = lazy(async () => ({ default: ({ order }: { order: PurchaseOrder }) => (
    <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    {['Line', 'Item', 'UOM', 'Ordered', 'Received', 'Invoiced', 'Returned', 'Remaining', 'Unit price', 'Total', 'Status'].map((header) => <th key={header} className="px-4 py-3 font-semibold">{header}</th>)}
                </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
                {(order.lines ?? []).map((line) => (
                    <tr key={line.id ?? line.line_number}>
                        <td className="px-4 py-3">{line.line_number}</td>
                        <td className="px-4 py-3">{line.item?.name ?? '-'}</td>
                        <td className="px-4 py-3">{line.uom?.code ?? line.uom?.name ?? '-'}</td>
                        <td className="px-4 py-3 tabular-nums">{line.ordered_quantity}</td>
                        <td className="px-4 py-3 tabular-nums">{line.received_quantity ?? '0.000000'}</td>
                        <td className="px-4 py-3 tabular-nums">{line.invoiced_quantity ?? '0.000000'}</td>
                        <td className="px-4 py-3 tabular-nums">{line.returned_quantity ?? '0.000000'}</td>
                        <td className="px-4 py-3 tabular-nums">{line.remaining_quantity ?? '0.000000'}</td>
                        <td className="px-4 py-3 tabular-nums">{line.unit_price}</td>
                        <td className="px-4 py-3 tabular-nums">{line.line_total ?? '-'}</td>
                        <td className="px-4 py-3">{line.status ?? '-'}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    </div>
) }));

const LazyAdjustments = lazy(async () => ({ default: ({ order }: { order: PurchaseOrder }) => (
    <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>{['Name', 'Type', 'Effect', 'Calculation', 'Amount', 'Allocated', 'Remaining'].map((header) => <th key={header} className="px-4 py-3 font-semibold">{header}</th>)}</tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
                {(order.adjustments ?? []).map((adjustment) => (
                    <tr key={adjustment.id ?? adjustment.name}>
                        <td className="px-4 py-3">{adjustment.name}</td>
                        <td className="px-4 py-3">{adjustment.adjustment_type}</td>
                        <td className="px-4 py-3">{adjustment.effect}</td>
                        <td className="px-4 py-3">{adjustment.calculation_type}</td>
                        <td className="px-4 py-3 tabular-nums">{adjustment.amount}</td>
                        <td className="px-4 py-3 tabular-nums">{adjustment.allocated_amount ?? '0.000000'}</td>
                        <td className="px-4 py-3 tabular-nums">{adjustment.remaining_amount ?? adjustment.amount}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    </div>
) }));

export function PurchaseOrderTabs({ order, summary }: { order: PurchaseOrder; summary: ReactNode }) {
    const state = useOnDemandTab<Tab>('summary');
    const tabs = [
        ['summary', 'Summary'],
        ['lines', 'Lines'],
        ['adjustments', 'Adjustments'],
    ].map(([id, label]) => ({ id: id as Tab, label }));

    return (
        <>
            <Tabs tabs={tabs} active={state.activeTab} onChange={state.openTab} />
            <div className="p-5">
                <Suspense fallback={<LoadingState />}>
                    {state.activeTab === 'summary' && summary}
                    {state.activeTab === 'lines' && <LazyLines order={order} />}
                    {state.activeTab === 'adjustments' && <LazyAdjustments order={order} />}
                </Suspense>
            </div>
        </>
    );
}
