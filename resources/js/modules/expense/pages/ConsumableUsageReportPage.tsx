import { useState, type FormEvent } from 'react';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { lookupApi } from '@/shared/api/lookupApi';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { sumDecimals } from '@/shared/utils/decimal';
import { listConsumableUsage, type ConsumableUsage } from '../consumableUsageApi';

const PAGE_SIZE = 25;

type ReportFilters = { from: string; to: string; item: NamedResource | null };

function initialFilters(): ReportFilters {
    const today = businessDateInputValue();
    return { from: `${today.slice(0, 7)}-01`, to: today, item: null };
}

const columns: DataColumn<ConsumableUsage>[] = [
    { key: 'date', header: 'Date', render: (row) => row.date },
    { key: 'item', header: 'Item', render: (row) => <div className="font-medium text-slate-900">{row.item.name}<div className="text-xs font-normal text-slate-500">{row.number}</div></div> },
    { key: 'warehouse', header: 'Used from', render: (row) => `${row.warehouse.name}${row.location ? ` / ${row.location.name}` : ''}` },
    { key: 'quantity', header: 'Quantity', render: (row) => `${row.quantity} ${row.uom?.name ?? ''}` },
    { key: 'cost', header: 'Expense cost (base currency)', render: (row) => row.total_cost },
    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
];

export default function ConsumableUsageReportPage() {
    const [draft, setDraft] = useState<ReportFilters>(initialFilters);
    const [filters, setFilters] = useState<ReportFilters>(initialFilters);
    const [page, setPage] = useState(1);
    const report = useApi(
        (signal) => listConsumableUsage({
            from: filters.from,
            to: filters.to,
            item_id: filters.item?.id,
            page,
            per_page: PAGE_SIZE,
        }, signal),
        [filters.from, filters.to, filters.item?.id, page],
    );
    const items = report.data?.summary.items ?? [];
    const totalCost = sumDecimals(items.map((row) => row.cost));

    function applyFilters(event: FormEvent) {
        event.preventDefault();
        if (!draft.from || !draft.to || draft.from > draft.to) return;
        setFilters(draft);
        setPage(1);
    }

    function resetFilters() {
        const initial = initialFilters();
        setDraft(initial);
        setFilters(initial);
        setPage(1);
    }

    return <>
        <ContentHeader
            title="Consumable usage report"
            description="See what was used and the inventory cost recognized as an operating expense."
            actions={<LinkButton to="/expenses/consumable-usage" variant="secondary">Record usage</LinkButton>}
        />
        <ErrorAlert error={report.error} />

        <form onSubmit={applyFilters}>
            <Panel title="Report period">
                <div className="grid gap-4 md:grid-cols-3">
                    <Input label="From" type="date" required value={draft.from} max={draft.to} onChange={(event) => setDraft((current) => ({ ...current, from: event.target.value }))} />
                    <Input label="To" type="date" required value={draft.to} min={draft.from} onChange={(event) => setDraft((current) => ({ ...current, to: event.target.value }))} />
                    <LookupSelect label="Item" value={draft.item} onChange={(item) => setDraft((current) => ({ ...current, item }))} search={lookupApi.consumableItems} placeholder="All consumable items" />
                </div>
                <div className="mt-4 flex gap-2">
                    <Button type="submit">Apply filters</Button>
                    <Button type="button" variant="secondary" onClick={resetFilters}>Reset</Button>
                </div>
            </Panel>
        </form>

        {report.loading ? <LoadingState label="Loading usage report..." /> : report.error ? null : <div className="mt-5 space-y-5">
            <Panel title="Item totals">
                <div className="mb-4 flex flex-wrap items-baseline justify-between gap-2 border-b border-slate-200 pb-4">
                    <p className="text-sm text-slate-600">Posted usage from {filters.from} to {filters.to}</p>
                    <p className="text-lg font-bold tabular-nums text-slate-950">Total expense cost (base currency): {totalCost}</p>
                </div>
                {items.length === 0 ? <p className="text-sm text-slate-600">No posted usage matches these filters.</p> : <div className="overflow-x-auto">
                    <table className="w-full min-w-[32rem] text-left text-sm">
                        <thead><tr className="border-b border-slate-200 text-slate-600"><th className="pb-2 font-semibold">Item</th><th className="pb-2 text-right font-semibold">Used quantity</th><th className="pb-2 text-right font-semibold">Expense cost (base currency)</th></tr></thead>
                        <tbody>{items.map((row) => <tr key={row.item.id} className="border-b border-slate-100 last:border-0"><td className="py-3 font-medium text-slate-900">{row.item.name}<span className="ml-2 text-xs font-normal text-slate-500">{row.item.code}</span></td><td className="py-3 text-right tabular-nums">{row.quantity} {row.uom ?? ''}</td><td className="py-3 text-right tabular-nums">{row.cost}</td></tr>)}</tbody>
                    </table>
                </div>}
            </Panel>

            <Panel title="Usage entries">
                <p className="mb-4 text-sm text-slate-600">{report.data?.meta?.total ?? 0} entries match this period. Reversed entries remain visible here and are excluded from item totals.</p>
                {report.loading ? <LoadingState /> : <DataTable rows={report.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage="No usage entries match these filters." />}
                <Pagination meta={report.data?.meta} onPageChange={setPage} />
            </Panel>
        </div>}
    </>;
}
