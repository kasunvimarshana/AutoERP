import { useEffect, useState, type FormEvent } from 'react';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { formatMoney } from '@/shared/utils/formatMoney';
import { ExportActions } from '../components/ExportActions';
import { ReportDataGrid } from '../components/ReportDataGrid';
import { runExpenseReport } from '../reportingApi';
import type {
    ExpenseBreakdownRow,
    ExpenseReportParams,
    ExpenseReportResult,
    ExpenseTrendRow,
} from '../reportingTypes';

const REPORT_KEY = 'expenses';
const INITIAL_PARAMS: ExpenseReportParams = { ...currentMonthRange(), page: 1, per_page: 25 };

export default function ExpenseReportPage() {
    const [params, setParams] = useState<ExpenseReportParams>(INITIAL_PARAMS);
    const [draft, setDraft] = useState<ExpenseReportParams>(INITIAL_PARAMS);
    const [result, setResult] = useState<ExpenseReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (controller.signal.aborted) return;
            setLoading(true);
            setError(null);
        });
        runExpenseReport(params, controller.signal)
            .then((data) => {
                if (!controller.signal.aborted) setResult(data);
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });

        return () => controller.abort();
    }, [params]);

    const apply = (event: FormEvent) => {
        event.preventDefault();
        setParams({ ...draft, page: 1 });
    };
    const reset = () => {
        setDraft(INITIAL_PARAMS);
        setParams(INITIAL_PARAMS);
    };
    const sort = (column: string) => {
        setParams((current) => {
            const next: ExpenseReportParams = {
                ...current,
                page: 1,
                sort: column,
                direction: current.sort === column && current.direction !== 'asc' ? 'asc' : 'desc',
            };
            setDraft(next);
            return next;
        });
    };

    if (loading && !result) return <LoadingState label="Tracing expense activity..." />;

    const currency = result?.currency_code || 'LKR';

    return (
        <>
            <ContentHeader
                title="Overall expense report"
                description="See every Profit and Loss expense in one place, including paid expenses and consumable usage."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} />

            <form onSubmit={apply}>
                <Panel title="Report scope">
                    <div className="grid gap-4 md:grid-cols-3">
                        <Input
                            label="From"
                            type="date"
                            required
                            value={draft.date_from}
                            max={draft.date_to}
                            onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value }))}
                        />
                        <Input
                            label="To"
                            type="date"
                            required
                            value={draft.date_to}
                            min={draft.date_from}
                            onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value }))}
                        />
                        <Select
                            label="Source"
                            placeholder="All expense sources"
                            value={draft.source ?? ''}
                            options={[{ value: 'paid', label: 'Paid expenses' }, { value: 'consumable', label: 'Consumable usage' }, { value: 'other', label: 'Other P&L expenses' }]}
                            onChange={(event) => setDraft((current) => ({
                                ...current,
                                source: event.target.value as ExpenseReportParams['source'] || undefined,
                                expense_type_id: event.target.value === 'consumable' || event.target.value === 'other' ? undefined : current.expense_type_id,
                                payment_method_id: event.target.value === 'consumable' || event.target.value === 'other' ? undefined : current.payment_method_id,
                                item_id: event.target.value === 'paid' || event.target.value === 'other' ? undefined : current.item_id,
                            }))}
                        />
                    </div>
                    <details className="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <summary className="cursor-pointer text-sm font-medium text-slate-700">More filters</summary>
                        <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Input
                            label="Search"
                            placeholder="Document, type, item, account, or reference"
                            value={draft.search ?? ''}
                            onChange={(event) => setDraft((current) => ({ ...current, search: event.target.value || undefined }))}
                        />
                        <Select
                            label="Expense type (paid only)"
                            placeholder="All expense types"
                            value={draft.expense_type_id ?? ''}
                            options={(result?.filter_options.expense_types ?? []).map((option) => ({ value: option.id, label: option.name }))}
                            onChange={(event) => setDraft((current) => ({ ...current, expense_type_id: event.target.value ? Number(event.target.value) : undefined, source: event.target.value ? 'paid' : current.source, item_id: event.target.value ? undefined : current.item_id }))}
                        />
                        <Select
                            label="Paid through (paid only)"
                            placeholder="All payment methods"
                            value={draft.payment_method_id ?? ''}
                            options={(result?.filter_options.payment_methods ?? []).map((option) => ({ value: option.id, label: option.name }))}
                            onChange={(event) => setDraft((current) => ({ ...current, payment_method_id: event.target.value ? Number(event.target.value) : undefined, source: event.target.value ? 'paid' : current.source, item_id: event.target.value ? undefined : current.item_id }))}
                        />
                        <Select
                            label="Consumable item"
                            placeholder="All consumable items"
                            value={draft.item_id ?? ''}
                            options={(result?.filter_options.items ?? []).map((option) => ({ value: option.id, label: option.name }))}
                            onChange={(event) => setDraft((current) => ({ ...current, item_id: event.target.value ? Number(event.target.value) : undefined, source: event.target.value ? 'consumable' : current.source, expense_type_id: event.target.value ? undefined : current.expense_type_id, payment_method_id: event.target.value ? undefined : current.payment_method_id }))}
                        />
                        <Select
                            label="Activity"
                            placeholder="Postings and reversals"
                            value={draft.event_type ?? ''}
                            options={[{ value: 'posted', label: 'Postings' }, { value: 'reversal', label: 'Reversals' }]}
                            onChange={(event) => setDraft((current) => ({ ...current, event_type: event.target.value as ExpenseReportParams['event_type'] || undefined }))}
                        />
                        </div>
                    </details>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button type="submit" loading={loading}>Apply filters</Button>
                        <Button type="button" variant="secondary" onClick={reset}>Reset</Button>
                    </div>
                </Panel>
            </form>

            {result ? (
                <div className="mt-5 space-y-5">
                    <ExpenseStory result={result} currency={currency} />

                    <section className="grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
                        <BreakdownPanel title="Paid expense types" description="Saved expense type on each paid expense." rows={result.by_expense_type} currency={currency} empty="No paid expense types in this period." />
                        <ConsumableBreakdown rows={result.by_consumable_item} currency={currency} />
                        <BreakdownPanel title="Other expense accounts" description="Other P&L entries, including cost of goods sold." rows={result.by_other_account} currency={currency} empty="No other P&L expenses in this period." />
                    </section>

                    <section className="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                        <TrendPanel rows={result.trend} currency={currency} />
                        <PaymentBreakdown rows={result.by_payment_method} currency={currency} />
                    </section>

                    <section>
                        <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-bold text-slate-950">Expense activity</h2>
                                <p className="mt-1 text-sm text-slate-500">
                                    {loading ? 'Refreshing...' : `${result.meta?.total ?? 0} ledger entries match the current filters.`}
                                </p>
                            </div>
                            <ExportActions reportKey={REPORT_KEY} params={params} />
                        </div>
                        <ReportDataGrid
                            columns={result.report.columns}
                            rows={result.data}
                            sort={params.sort ?? result.report.default_sort}
                            direction={params.direction ?? result.report.default_direction}
                            onSort={sort}
                        />
                        <Pagination meta={result.meta} onPageChange={(page) => setParams((current) => ({ ...current, page }))} />
                    </section>

                    <p className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600">
                        {result.basis}
                    </p>
                </div>
            ) : null}
        </>
    );
}

function ExpenseStory({ result, currency }: { result: ExpenseReportResult; currency: string }) {
    const summary = result.summary;
    const sourceAmount = (source: string) => result.by_source.find((row) => row.source === source)?.net_amount ?? '0';

    return (
        <section className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div className="grid bg-slate-950 text-white lg:grid-cols-[1.25fr_0.75fr]">
                <div className="px-6 py-7 sm:px-8">
                    <p className="text-sm font-medium text-sky-300">Net expense · Profit and Loss</p>
                    <p className={`mt-2 text-4xl font-bold tracking-tight sm:text-5xl ${Number(summary.net_amount) < 0 ? 'text-emerald-300' : 'text-white'}`}>
                        {formatMoney(summary.net_amount, currency)}
                    </p>
                    <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-300">
                        {summary.event_count === 0
                            ? 'No expense entries were posted in this period.'
                            : `${summary.posted_count} expense entries and ${summary.reversal_count} reversals affected this period.`}
                    </p>
                </div>
                <dl className="grid grid-cols-2 border-t border-white/10 lg:border-l lg:border-t-0">
                    <StoryMetric label="Posted" value={formatMoney(summary.posted_amount, currency)} />
                    <StoryMetric label="Reversed" value={formatMoney(summary.reversed_amount, currency)} />
                    <StoryMetric label="Postings" value={String(summary.posted_count)} />
                    <StoryMetric label="Reversals" value={String(summary.reversal_count)} />
                </dl>
            </div>
            <div className="grid gap-px bg-slate-200 sm:grid-cols-3">
                <SourceMetric label="Paid expenses" value={sourceAmount('paid')} currency={currency} />
                <SourceMetric label="Consumable usage" value={sourceAmount('consumable')} currency={currency} />
                <SourceMetric label="Other P&L expenses" value={sourceAmount('other')} currency={currency} />
            </div>
        </section>
    );
}

function SourceMetric({ label, value, currency }: { label: string; value: string; currency: string }) {
    return (
        <div className="bg-white px-6 py-5">
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-1 text-xl font-semibold tabular-nums text-slate-950">{formatMoney(value, currency)}</p>
        </div>
    );
}

function BreakdownPanel({ title, description, rows, currency, empty }: { title: string; description: string; rows: ExpenseBreakdownRow[]; currency: string; empty: string }) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 className="font-bold text-slate-950">{title}</h2>
            <p className="mt-1 text-sm text-slate-500">{description}</p>
            <div className="mt-4 divide-y divide-slate-100 border-t border-slate-100">
                {rows.length === 0 ? <p className="py-8 text-center text-sm text-slate-500">{empty}</p> : rows.map((row) => (
                    <div key={row.id} className="flex items-center justify-between gap-3 py-3 text-sm">
                        <span className="min-w-0 truncate text-slate-700">{row.name}</span>
                        <span className="shrink-0 font-semibold tabular-nums text-slate-900">{formatMoney(row.net_amount, currency)}</span>
                    </div>
                ))}
            </div>
        </section>
    );
}

function ConsumableBreakdown({ rows, currency }: { rows: ExpenseReportResult['by_consumable_item']; currency: string }) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 className="font-bold text-slate-950">Consumables by item</h2>
            <p className="mt-1 text-sm text-slate-500">Used quantity and inventory cost recognized as expense.</p>
            <div className="mt-4 divide-y divide-slate-100 border-t border-slate-100">
                {rows.length === 0 ? <p className="py-8 text-center text-sm text-slate-500">No valued consumable usage in this period.</p> : rows.map((row) => (
                    <div key={row.id} className="flex items-center justify-between gap-3 py-3 text-sm">
                        <span className="min-w-0 truncate text-slate-700">{row.name} <span className="text-slate-400">· {Number(row.quantity)} used</span></span>
                        <span className="shrink-0 font-semibold tabular-nums text-slate-900">{formatMoney(row.net_amount, currency)}</span>
                    </div>
                ))}
            </div>
        </section>
    );
}

function TrendPanel({ rows, currency }: { rows: ExpenseTrendRow[]; currency: string }) {
    const maximum = Math.max(0, ...rows.map((row) => Math.max(Number(row.posted_amount), Number(row.reversed_amount))));

    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 className="font-bold text-slate-950">Activity over time</h2>
            <p className="mt-1 text-sm text-slate-500">Daily postings in blue and reversals in red.</p>
            <div className="mt-5 max-h-72 space-y-3 overflow-y-auto pr-1">
                {rows.length === 0 ? <p className="py-8 text-center text-sm text-slate-500">No dated activity in this period.</p> : rows.map((row) => (
                    <div key={row.date} className="grid grid-cols-[5.5rem_1fr_auto] items-center gap-3 text-xs">
                        <span className="text-slate-500">{formatDate(row.date)}</span>
                        <div className="space-y-1">
                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div className="h-full rounded-full bg-sky-600" style={{ width: `${barWidth(row.posted_amount, maximum)}%` }} />
                            </div>
                            {Number(row.reversed_amount) > 0 ? (
                                <div className="h-1.5 overflow-hidden rounded-full bg-rose-50">
                                    <div className="h-full rounded-full bg-rose-500" style={{ width: `${barWidth(row.reversed_amount, maximum)}%` }} />
                                </div>
                            ) : null}
                        </div>
                        <span className="font-semibold tabular-nums text-slate-800">{formatMoney(row.net_amount, currency)}</span>
                    </div>
                ))}
            </div>
        </section>
    );
}

function PaymentBreakdown({ rows, currency }: { rows: ExpenseBreakdownRow[]; currency: string }) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 className="font-bold text-slate-950">How paid expenses were paid</h2>
            <p className="mt-1 text-sm text-slate-500">Paid expenses only. Consumable usage has no payment method.</p>
            <div className="mt-4 divide-y divide-slate-100 border-t border-slate-100">
                {rows.length === 0 ? <p className="py-8 text-center text-sm text-slate-500">No paid expenses in this period.</p> : rows.map((row) => (
                    <div key={row.id} className="flex items-center justify-between gap-4 py-3 text-sm">
                        <div className="min-w-0">
                            <p className="truncate font-medium text-slate-800">{row.name}</p>
                            <p className="mt-0.5 text-xs text-slate-400">{row.transaction_count} expenses</p>
                        </div>
                        <span className="shrink-0 font-semibold tabular-nums text-slate-900">{formatMoney(row.net_amount, currency)}</span>
                    </div>
                ))}
            </div>
        </section>
    );
}

function StoryMetric({ label, value }: { label: string; value: string }) {
    return (
        <div className="border-b border-r border-white/10 px-5 py-5">
            <dt className="text-xs text-slate-400">{label}</dt>
            <dd className="mt-1 font-semibold tabular-nums text-white">{value}</dd>
        </div>
    );
}

function barWidth(value: string, maximum: number): number {
    if (maximum <= 0) return 0;
    return Math.max(2, (Math.abs(Number(value)) / maximum) * 100);
}

function formatDate(value: string): string {
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' }).format(new Date(year, month - 1, day));
}

function currentMonthRange(): Pick<ExpenseReportParams, 'date_from' | 'date_to'> {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return { date_from: `${year}-${month}-01`, date_to: `${year}-${month}-${day}` };
}
