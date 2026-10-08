import { useEffect, useState, type FormEvent } from 'react';
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
import type {
    VehicleServiceSalesSummaryCombo,
    VehicleServiceSalesSummaryCostLine,
    VehicleServiceSalesSummaryJob,
    VehicleServiceSalesSummaryParams,
    VehicleServiceSalesSummaryResult,
} from '../reportingTypes';

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
                description="See what each job earned, what stock it used, and its profit or loss."
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
                        <div className="md:col-span-2"><ItemLookupSelect label="Item used" value={draftItem} onChange={setDraftItem} /></div>
                        <div className="flex items-end gap-2 md:col-span-4 md:justify-end">
                            <Button type="button" variant="secondary" onClick={reset}>Reset</Button>
                            <Button type="submit" loading={loading}>Apply filters</Button>
                        </div>
                    </form>
                    <p className="mt-3 text-sm text-slate-500">Sales follow invoice date. Collected amounts follow payment date. Item filters show matching jobs with their full item breakdown.</p>
                </Panel>

                {loading ? <LoadingState label="Loading service job sales summary..." /> : result ? <>
                    <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Sales and profit totals">
                        <Metric label="Revenue" value={formatMoney(result.summary.revenue)} tone="blue" />
                        <Metric label="Stock cost" value={formatMoney(result.summary.stock_cost)} tone="violet" />
                        <Metric label="Commission" value={formatMoney(result.summary.commission)} tone="amber" />
                        <Metric label={result.summary.profit.startsWith('-') ? 'Net loss' : 'Net profit'} value={formatMoney(result.summary.profit)} tone={result.summary.profit.startsWith('-') ? 'red' : 'green'} detail={`${formatQuantity(result.summary.margin)}% margin`} />
                    </section>

                    <div className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                        <span>{formatQuantity(result.summary.job_count)} jobs in this period</span>
                        <span>Collected: <strong className="font-semibold text-slate-900">{formatMoney(result.summary.collected)}</strong></span>
                    </div>

                    <section className="grid gap-4 lg:grid-cols-2" aria-label="Stock and combo performance">
                        <CategoryPanel
                            title="Stock items"
                            tone="stock"
                            count={`${formatQuantity(result.summary.stock.item_count)} item types`}
                            quantity={`${formatQuantity(result.summary.stock.quantity)} units used`}
                            revenue={result.summary.stock.revenue}
                            cost={result.summary.stock.cost}
                            profit={result.summary.stock.profit}
                        />
                        <ComboPanel result={result} />
                    </section>
                    {Number(result.summary.other_service_revenue) !== 0 && (
                        <p className="text-sm text-slate-500">Other service and labour sales included in total revenue: <strong className="font-semibold text-slate-700">{formatMoney(result.summary.other_service_revenue)}</strong></p>
                    )}

                    <Panel title="Profit by service job">
                        <p className="-mt-2 mb-4 text-sm text-slate-500">Expand a job to see stock items, combos, and the items inside each combo. Job profit is after direct costs and commission.</p>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead>
                                    <tr className="text-left text-xs font-semibold text-slate-500">
                                        <th scope="col" className="py-3 pr-3">Job</th>
                                        <th scope="col" className="py-3 px-3">Customer / vehicle</th>
                                        <th scope="col" className="py-3 px-3 text-right">Revenue</th>
                                        <th scope="col" className="py-3 px-3 text-right">Stock cost</th>
                                        <th scope="col" className="py-3 px-3 text-right">Commission</th>
                                        <th scope="col" className="py-3 pl-3 text-right">Profit / loss</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {result.jobs.map((job) => <JobRow key={job.id} job={job} />)}
                                    {result.jobs.length === 0 && <tr><td colSpan={6} className="py-12 text-center"><div className="font-semibold text-slate-800">No invoiced jobs in this period</div><p className="mt-1 text-slate-500">Try a wider date range or clear the item filter.</p></td></tr>}
                                </tbody>
                            </table>
                        </div>
                    </Panel>
                </> : null}
            </div>
        </>
    );
}

function Metric({ label, value, tone, detail }: { label: string; value: string; tone: 'blue' | 'violet' | 'amber' | 'green' | 'red'; detail?: string }) {
    const accents = { blue: 'bg-blue-600', violet: 'bg-violet-600', amber: 'bg-amber-500', green: 'bg-emerald-600', red: 'bg-rose-600' };
    const valueTone = tone === 'green' ? 'text-emerald-800' : tone === 'red' ? 'text-rose-800' : 'text-slate-950';
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4">
            <div className={`mb-3 h-1 w-9 rounded-full ${accents[tone]}`} />
            <div className="text-sm text-slate-600">{label}</div>
            <div className={`mt-1 text-2xl font-bold tabular-nums tracking-tight ${valueTone}`}>{value}</div>
            {detail && <div className="mt-1 text-xs text-slate-500">{detail}</div>}
        </div>
    );
}

function CategoryPanel({ title, tone, count, quantity, revenue, cost, profit }: {
    title: string;
    tone: 'stock' | 'combo';
    count: string;
    quantity: string;
    revenue: string;
    cost: string;
    profit: string;
}) {
    const accent = tone === 'stock' ? 'border-l-blue-600' : 'border-l-violet-600';
    return (
        <Panel className={`border-l-4 ${accent}`} title={title}>
            <div className="mb-4 flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-500"><span>{count}</span><span>{quantity}</span></div>
            <div className="grid grid-cols-3 gap-3 border-t border-slate-100 pt-3">
                <Amount label="Sales" value={revenue} />
                <Amount label="Cost" value={cost} />
                <Amount label="Profit before commission" value={profit} emphasized />
            </div>
        </Panel>
    );
}

function ComboPanel({ result }: { result: VehicleServiceSalesSummaryResult }) {
    const combo = result.summary.combo;
    return (
        <Panel className="border-l-4 border-l-violet-600" title="Combo items">
            <div className="mb-4 flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-500">
                <span>{formatQuantity(combo.combo_count)} combo lines</span>
                <span>{formatQuantity(combo.quantity)} combos used</span>
                <span>{formatQuantity(combo.component_stock_quantity)} component stock units</span>
            </div>
            <div className="grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 sm:grid-cols-4">
                <Amount label="Combo sales" value={combo.revenue} />
                <Amount label="Stock component cost" value={combo.stock_cost} />
                <Amount label="All component cost" value={combo.component_cost} />
                <Amount label="Profit before commission" value={combo.profit} emphasized />
            </div>
        </Panel>
    );
}

function Amount({ label, value, emphasized = false }: { label: string; value: string; emphasized?: boolean }) {
    return (
        <div>
            <div className="text-xs text-slate-500">{label}</div>
            <div className={`mt-1 tabular-nums ${emphasized ? 'font-semibold text-emerald-800' : 'font-medium text-slate-800'}`}>{formatMoney(value)}</div>
        </div>
    );
}

function JobRow({ job }: { job: VehicleServiceSalesSummaryJob }) {
    const [expanded, setExpanded] = useState(false);
    const loss = job.profit.startsWith('-');
    return (
        <>
            <tr className="align-middle">
                <td className="py-3 pr-3">
                    <button type="button" className="flex items-center gap-2 text-left font-semibold text-blue-700 hover:text-blue-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" aria-expanded={expanded} onClick={() => setExpanded((value) => !value)}>
                        <span aria-hidden="true" className="w-4 text-slate-400">{expanded ? '−' : '+'}</span>{job.job_number}
                    </button>
                    <div className="pl-6 pt-0.5 text-xs text-slate-500">{job.date}</div>
                </td>
                <td className="min-w-48 py-3 px-3">
                    <div className="font-medium text-slate-900">{job.customer.name}</div>
                    <div className="mt-0.5 text-xs text-slate-500">{job.vehicle.name}</div>
                </td>
                <td className="py-3 px-3 text-right tabular-nums text-slate-800">{formatMoney(job.revenue)}</td>
                <td className="py-3 px-3 text-right tabular-nums text-slate-700">{formatMoney(job.used_stock_cost)}</td>
                <td className="py-3 px-3 text-right tabular-nums text-slate-700">{formatMoney(job.commission)}</td>
                <td className={`py-3 pl-3 text-right font-semibold tabular-nums ${loss ? 'text-rose-700' : 'text-emerald-800'}`}>
                    {formatMoney(job.profit)}<div className="text-xs font-normal">{formatQuantity(job.margin)}%</div>
                </td>
            </tr>
            {expanded && <tr><td colSpan={6} className="bg-slate-50 px-4 py-4 sm:px-8"><JobDetails job={job} /></td></tr>}
        </>
    );
}

function JobDetails({ job }: { job: VehicleServiceSalesSummaryJob }) {
    return (
        <div className="space-y-4">
            <DetailLines title="Stock items" rows={job.stock_items} empty="No standalone stock items were used." />
            {job.combos.map((combo) => <ComboDetails key={combo.line_id} combo={combo} />)}
            <DetailLines title="Other service and labour items" rows={job.other_items} empty="No other service or labour items." />
            <div className="flex flex-wrap justify-end gap-x-6 gap-y-1 border-t border-slate-200 pt-3 text-sm">
                <span className="text-slate-600">Direct cost <strong className="text-slate-900">{formatMoney(job.direct_cost)}</strong></span>
                <span className="text-slate-600">Commission <strong className="text-slate-900">{formatMoney(job.commission)}</strong></span>
                <span className="text-slate-600">Net profit / loss <strong className={job.profit.startsWith('-') ? 'text-rose-700' : 'text-emerald-800'}>{formatMoney(job.profit)}</strong></span>
            </div>
        </div>
    );
}

function DetailLines({ title, rows, empty }: { title: string; rows: VehicleServiceSalesSummaryCostLine[]; empty: string }) {
    return (
        <section>
            <h3 className="mb-2 font-semibold text-slate-800">{title}</h3>
            {rows.length === 0 ? <p className="text-sm text-slate-500">{empty}</p> : <div className="divide-y divide-slate-200 rounded-md border border-slate-200 bg-white">{rows.map((row) => <div key={row.line_id} className="grid gap-2 px-3 py-2 text-sm sm:grid-cols-[minmax(0,1fr)_auto_auto_auto] sm:items-center"><ItemName item={row.item} /><span className="text-slate-600">{formatQuantity(row.quantity)} used</span><span className="tabular-nums text-slate-600">Sales {formatMoney(row.sales_amount)}</span><span className="tabular-nums font-medium text-slate-900">Cost {formatMoney(row.cost)}</span></div>)}</div>}
        </section>
    );
}

function ComboDetails({ combo }: { combo: VehicleServiceSalesSummaryCombo }) {
    return (
        <section className="rounded-md border border-violet-200 bg-white">
            <div className="flex flex-wrap items-baseline justify-between gap-2 border-b border-violet-100 bg-violet-50 px-3 py-2">
                <div><span className="font-semibold text-slate-900">{combo.item.name}</span><span className="ml-2 text-xs text-slate-500">{combo.item.code} · {formatQuantity(combo.quantity)} used</span></div>
                <div className="text-sm tabular-nums text-slate-700">Sales {formatMoney(combo.sales_amount)} · components {formatMoney(combo.component_cost)} · profit before commission {formatMoney(combo.profit)}</div>
            </div>
            <ul className="divide-y divide-slate-100">
                {combo.components.map((component, index) => <li key={`${component.item.id}-${index}`} className="grid gap-1 px-3 py-2 text-sm sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center">
                    <ItemName item={component.item} />
                    <span className="text-xs text-slate-500">{component.kind === 'stock' ? 'Stock' : 'Labour / service'} · {formatQuantity(component.quantity)} used</span>
                    <span className="tabular-nums text-slate-700">Cost {formatMoney(component.cost)}</span>
                </li>)}
            </ul>
            {combo.components.length === 0 && <p className="px-3 py-3 text-sm text-slate-500">No combo components recorded.</p>}
        </section>
    );
}

function ItemName({ item }: { item: { name: string; code: string } }) {
    return <div className="min-w-0"><span className="font-medium text-slate-900">{item.name}</span><span className="ml-2 text-xs text-slate-500">{item.code}</span></div>;
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
