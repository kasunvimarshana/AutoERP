import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { formatMoney } from '@/shared/utils/formatMoney';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { ItemLookupSelect } from '@/modules/item/components/ItemLookupSelect';
import type { ItemSummary } from '@/modules/item/itemTypes';
import { runVehicleServiceSalesSummaryReport } from '../reportingApi';
import type { VehicleServiceSalesSummaryParams, VehicleServiceSalesSummaryResult, VehicleServiceSalesSummaryRow } from '../reportingTypes';

type DatePreset = 'today' | 'week' | 'month' | 'custom';

export default function VehicleServiceSalesSummaryReportPage() {
    const [draftItem, setDraftItem] = useState<ItemSummary | null>(null);
    const [item, setItem] = useState<ItemSummary | null>(null);
    const [preset, setPreset] = useState<DatePreset>('today');
    const [filters, setFilters] = useState<VehicleServiceSalesSummaryParams>(rangeFor('today'));
    const [draft, setDraft] = useState(filters);
    const [result, setResult] = useState<VehicleServiceSalesSummaryResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError(null);
        setResult(null);
        void runVehicleServiceSalesSummaryReport({ ...filters, item_id: item?.id }, controller.signal)
            .then((response) => { if (!controller.signal.aborted) setResult(response); })
            .catch((requestError) => { if (!controller.signal.aborted) setError(toApiError(requestError)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [filters, item]);

    const rankedBySales = useMemo(() => [...(result?.data ?? [])].sort((left, right) => Number(right.sales_amount) - Number(left.sales_amount)), [result]);
    const topSalesItem = rankedBySales[0] ?? null;
    const topQuantityItem = useMemo(() => [...(result?.data ?? [])].sort((left, right) => Number(right.quantity) - Number(left.quantity))[0] ?? null, [result]);

    const apply = (event: FormEvent) => {
        event.preventDefault();
        setPreset('custom');
        setItem(draftItem);
        setFilters(draft);
    };

    const selectPreset = (next: Exclude<DatePreset, 'custom'>) => {
        const nextRange = rangeFor(next);
        setPreset(next);
        setDraft(nextRange);
        setFilters(nextRange);
        setItem(draftItem);
    };

    const reset = () => {
        const nextRange = rangeFor('today');
        setDraftItem(null);
        setItem(null);
        setPreset('today');
        setDraft(nextRange);
        setFilters(nextRange);
    };

    return (
        <>
            <ContentHeader
                title="Service Job Sales Summary"
                description="See which service items are used most and how much sales and collection each one brings."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} title="Could not load service job sales summary" />
            <div className="space-y-5">
                <Panel title="Report period">
                    <div className="mb-4 flex flex-wrap gap-2" role="group" aria-label="Date range">
                        <Button type="button" variant={preset === 'today' ? 'primary' : 'secondary'} onClick={() => selectPreset('today')}>Today</Button>
                        <Button type="button" variant={preset === 'week' ? 'primary' : 'secondary'} onClick={() => selectPreset('week')}>This week</Button>
                        <Button type="button" variant={preset === 'month' ? 'primary' : 'secondary'} onClick={() => selectPreset('month')}>This month</Button>
                    </div>
                    <form className="grid gap-4 md:grid-cols-4" onSubmit={apply}>
                        <Input label="From" type="date" value={draft.date_from ?? ''} onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value }))} />
                        <Input label="To" type="date" value={draft.date_to ?? ''} onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value }))} />
                        <div className="md:col-span-2"><ItemLookupSelect label="Service item" value={draftItem} onChange={setDraftItem} /></div>
                        <div className="flex items-end gap-2 md:col-span-4 md:justify-end">
                            <Button type="button" variant="secondary" onClick={reset}>Reset</Button>
                            <Button type="submit" loading={loading}>Apply filters</Button>
                        </div>
                    </form>
                    <p className="mt-3 text-sm text-slate-500">Jobs and quantity count invoiced service lines. Sales follow invoice date; collected follows payment date and is distributed across invoice items in proportion to their line totals. Combo child lines are excluded so a combo is not counted twice.</p>
                </Panel>

                {loading ? <LoadingState label="Loading service sales summary..." /> : result ? <>
                    <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Service sales totals">
                        <Metric label="Sales · invoice date" value={formatMoney(result?.summary.sales_amount ?? '0')} tone="blue" />
                        <Metric label="Collected · payment date" value={formatMoney(result?.summary.collected_amount ?? '0')} tone="teal" />
                        <Metric label="Units invoiced" value={formatQuantity(result?.summary.quantity ?? '0')} tone="violet" />
                        <Metric label="Service jobs" value={formatQuantity(result?.summary.job_count ?? 0)} tone="amber" />
                    </section>

                    {result?.data.length ? <section className="grid gap-4 lg:grid-cols-2" aria-label="Item highlights">
                        <Highlight title="Most units sold" row={topQuantityItem} metric={topQuantityItem ? `${formatQuantity(topQuantityItem.quantity)} units` : '—'} tone="blue" />
                        <Highlight title="Highest sales" row={topSalesItem} metric={topSalesItem ? formatMoney(topSalesItem.sales_amount) : '—'} tone="teal" />
                    </section> : null}

                    <Panel title="Item performance">
                        <p className="-mt-2 mb-4 text-sm text-slate-500">One row per invoiced item. Sales share shows each item’s contribution to sales in this period.</p>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead>
                                    <tr className="text-left text-xs font-semibold text-slate-500">
                                        <th scope="col" className="py-3 pr-3">Rank</th>
                                        <th scope="col" className="py-3 px-3">Service item</th>
                                        <th scope="col" className="py-3 px-3 text-right">Jobs</th>
                                        <th scope="col" className="py-3 px-3 text-right">Quantity</th>
                                        <th scope="col" className="py-3 px-3 text-right">Sales</th>
                                        <th scope="col" className="py-3 px-3 text-right">Collected</th>
                                        <th scope="col" className="py-3 pl-3 text-right">Sales share</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rankedBySales.map((row, index) => <ItemRow key={row.item.id} row={row} rank={index + 1} totalSales={result?.summary.sales_amount ?? '0'} />)}
                                    {result?.data.length === 0 && <tr><td colSpan={7} className="py-12 text-center"><div className="font-semibold text-slate-800">No invoiced service items in this period</div><p className="mt-1 text-slate-500">Try a wider date range or clear the item filter.</p></td></tr>}
                                </tbody>
                            </table>
                        </div>
                    </Panel>
                </> : null}
            </div>
        </>
    );
}

function Metric({ label, value, tone }: { label: string; value: string; tone: 'blue' | 'teal' | 'violet' | 'amber' }) {
    const accents = { blue: 'bg-blue-600', teal: 'bg-teal-600', violet: 'bg-violet-600', amber: 'bg-amber-500' };
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className={`mb-3 h-1 w-9 rounded-full ${accents[tone]}`} />
            <div className="text-sm text-slate-600">{label}</div>
            <div className="mt-1 text-2xl font-bold tabular-nums tracking-tight text-slate-950">{value}</div>
        </div>
    );
}

function Highlight({ title, row, metric, tone }: { title: string; row: VehicleServiceSalesSummaryRow | null; metric: string; tone: 'blue' | 'teal' }) {
    const accent = tone === 'blue' ? 'border-l-blue-600' : 'border-l-teal-600';
    return (
        <div className={`rounded-xl border border-slate-200 border-l-4 ${accent} bg-white px-5 py-4 shadow-sm`}>
            <div className="text-sm font-medium text-slate-500">{title}</div>
            <div className="mt-2 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <div className="text-lg font-bold text-slate-950">{row?.item.name ?? 'No sales recorded'}</div>
                <div className="text-lg font-bold tabular-nums text-slate-800">{metric}</div>
            </div>
            {row && <div className="mt-1 text-sm text-slate-500">Used in {formatQuantity(row.job_count)} service jobs</div>}
        </div>
    );
}

function ItemRow({ row, rank, totalSales }: { row: VehicleServiceSalesSummaryRow; rank: number; totalSales: string }) {
    const share = Number(totalSales) > 0 ? Math.min(100, (Number(row.sales_amount) / Number(totalSales)) * 100) : 0;
    return (
        <tr className="align-middle">
            <td className="py-3 pr-3 font-semibold tabular-nums text-slate-400">{String(rank).padStart(2, '0')}</td>
            <td className="min-w-48 py-3 px-3">
                <div className="font-semibold text-slate-900">{row.item.name}</div>
                <div className="mt-0.5 text-xs text-slate-500">{row.item.code}</div>
            </td>
            <td className="py-3 px-3 text-right tabular-nums text-slate-700">{formatQuantity(row.job_count)}</td>
            <td className="py-3 px-3 text-right tabular-nums text-slate-700">{formatQuantity(row.quantity)}</td>
            <td className="py-3 px-3 text-right font-semibold tabular-nums text-slate-950">{formatMoney(row.sales_amount)}</td>
            <td className="py-3 px-3 text-right tabular-nums text-teal-700">{formatMoney(row.collected_amount)}</td>
            <td className="min-w-36 py-3 pl-3 text-right">
                <div className="font-medium tabular-nums text-slate-700">{share.toFixed(1)}%</div>
                <div className="ml-auto mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-slate-100"><div className="h-full rounded-full bg-blue-600" style={{ width: `${share}%` }} /></div>
            </td>
        </tr>
    );
}

function rangeFor(preset: Exclude<DatePreset, 'custom'>): VehicleServiceSalesSummaryParams {
    const today = new Date();
    const end = toDateInput(today);
    if (preset === 'today') return { date_from: end, date_to: end };
    if (preset === 'month') return { date_from: toDateInput(new Date(today.getFullYear(), today.getMonth(), 1)), date_to: end };
    const start = new Date(today);
    const dayOfWeek = (start.getDay() + 6) % 7;
    start.setDate(start.getDate() - dayOfWeek);
    return { date_from: toDateInput(start), date_to: end };
}

function toDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
