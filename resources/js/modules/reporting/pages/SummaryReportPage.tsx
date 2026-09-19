import { startTransition, useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Button, LinkButton } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { formatMoney } from '@/shared/utils/formatMoney';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { runSummaryReport } from '../reportingApi';
import type {
    SummaryDocumentMetrics,
    SummaryPaymentMetrics,
    SummaryReportResult,
} from '../reportingTypes';

interface DateRange {
    date_from: string;
    date_to: string;
}

const INITIAL_DATE_RANGE = currentMonthRange();

export default function SummaryReportPage() {
    const [range, setRange] = useState<DateRange>(INITIAL_DATE_RANGE);
    const [draft, setDraft] = useState<DateRange>(INITIAL_DATE_RANGE);
    const [result, setResult] = useState<SummaryReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (controller.signal.aborted) return;
            setLoading(true);
            setError(null);
        });

        runSummaryReport(range, controller.signal)
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
    }, [range]);

    const applyRange = (event: FormEvent) => {
        event.preventDefault();
        startTransition(() => setRange(draft));
    };

    if (loading && !result) {
        return <LoadingState label="Building summary report..." />;
    }

    const currency = result?.currency_code || 'LKR';
    const netProfit = Number(result?.performance.net_profit ?? 0);

    return (
        <>
            <ContentHeader
                title="Summary Reports"
                description="A period snapshot of finalized trading documents, cash movement, and ledger performance."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />

            <ErrorAlert error={error} />

            <form
                className="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                onSubmit={applyRange}
            >
                <div className="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
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
                    <Button type="submit" loading={loading} className="w-full sm:w-auto">
                        Refresh report
                    </Button>
                </div>
            </form>

            {result ? (
                <div className="space-y-5">
                    <section className="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-7 text-white shadow-xl shadow-slate-200 sm:px-8">
                        <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-cyan-400/15 blur-3xl" />
                        <div className="absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-emerald-400/10 blur-3xl" />
                        <div className="relative grid gap-7 lg:grid-cols-[1.2fr_1fr] lg:items-end">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-300">
                                    Net result
                                </p>
                                <p className={`mt-3 text-4xl font-bold tracking-tight sm:text-5xl ${netProfit < 0 ? 'text-rose-300' : 'text-white'}`}>
                                    {money(result.performance.net_profit, currency)}
                                </p>
                                <p className="mt-3 text-sm text-slate-300">
                                    {formatDate(result.period.date_from)} to {formatDate(result.period.date_to)}
                                </p>
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <HeroMetric label="Ledger income" value={money(result.performance.total_income, currency)} />
                                <HeroMetric label="Total expenses" value={money(result.performance.total_expenses, currency)} />
                            </div>
                        </div>
                    </section>

                    <section>
                        <SectionHeading
                            title="Trading overview"
                            description="Only finalized documents dated inside the selected period are included."
                        />
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <DocumentCard
                                title="Sales"
                                eyebrow="Outbound invoices"
                                tone="emerald"
                                metrics={result.documents.sales}
                                currency={currency}
                            />
                            <DocumentCard
                                title="Purchases"
                                eyebrow="Inbound invoices"
                                tone="blue"
                                metrics={result.documents.purchases}
                                currency={currency}
                            />
                            <DocumentCard
                                title="Sales returns"
                                eyebrow="Outbound credit notes"
                                tone="amber"
                                metrics={result.documents.sales_returns}
                                currency={currency}
                                compact
                            />
                            <DocumentCard
                                title="Purchase returns"
                                eyebrow="Posted supplier returns"
                                tone="rose"
                                metrics={result.documents.purchase_returns}
                                currency={currency}
                                compact
                            />
                        </div>
                        <SalesSettlementStrip
                            settlement={result.sales_settlement}
                            salesTotal={result.documents.sales.grand_total}
                            currency={currency}
                        />
                    </section>

                    <section className="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <SectionHeading
                                title="Profit bridge"
                                description="Income and expenses come from posted General Ledger entries."
                            />
                            <div className="space-y-4">
                                <BridgeRow
                                    label="Total income"
                                    value={result.performance.total_income}
                                    currency={currency}
                                    width={100}
                                    tone="bg-emerald-500"
                                />
                                <BridgeRow
                                    label="Cost of sales"
                                    value={result.performance.cost_of_sales}
                                    currency={currency}
                                    width={ratio(result.performance.cost_of_sales, result.performance.total_income)}
                                    tone="bg-amber-400"
                                />
                                <BridgeRow
                                    label="Other expenses"
                                    value={result.performance.other_expenses}
                                    currency={currency}
                                    width={ratio(result.performance.other_expenses, result.performance.total_income)}
                                    tone="bg-sky-500"
                                />
                                <div className="flex items-center justify-between border-t border-slate-200 pt-4">
                                    <span className="font-semibold text-slate-900">Net profit / loss</span>
                                    <span className={`text-lg font-bold ${netProfit < 0 ? 'text-rose-600' : 'text-emerald-700'}`}>
                                        {money(result.performance.net_profit, currency)}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-dashed border-slate-300 bg-gradient-to-br from-slate-50 to-white p-5 sm:p-6">
                            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Payroll</p>
                            <h2 className="mt-2 text-lg font-bold text-slate-900">Not connected yet</h2>
                            <p className="mt-2 text-sm leading-6 text-slate-600">
                                {result.capabilities.payroll.message}
                            </p>
                            <p className="mt-5 rounded-xl bg-white p-3 text-xs leading-5 text-slate-500 shadow-sm ring-1 ring-slate-200">
                                This is intentionally not shown as zero, because zero would imply payroll data was checked.
                            </p>
                        </div>
                    </section>

                    <section>
                        <SectionHeading
                            title="Cash movement"
                            description="Posted payments are grouped using the payment-method snapshot stored on each transaction."
                        />
                        <div className="grid gap-5 lg:grid-cols-2">
                            <PaymentCard
                                title="Payments received"
                                tone="emerald"
                                metrics={result.payments.received}
                                currency={currency}
                            />
                            <PaymentCard
                                title="Payments sent"
                                tone="blue"
                                metrics={result.payments.sent}
                                currency={currency}
                            />
                        </div>
                    </section>
                </div>
            ) : null}
        </>
    );
}

function SalesSettlementStrip({
    settlement,
    salesTotal,
    currency,
}: {
    settlement: SummaryReportResult['sales_settlement'];
    salesTotal: string;
    currency: string;
}) {
    const supportingItems = [
        Number(settlement.other_paid.amount) !== 0
            ? `Other paid methods ${money(settlement.other_paid.amount, currency)}`
            : null,
        Number(settlement.credits_applied) !== 0
            ? `Credits applied ${money(settlement.credits_applied, currency)}`
            : null,
    ].filter((item): item is string => item !== null);

    return (
        <div className="mt-4 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50/60 shadow-sm">
            <div className="flex flex-col gap-2 border-b border-emerald-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.17em] text-emerald-700">Sales settlement mix</p>
                    <p className="mt-1 text-sm text-slate-600">How the selected sales are currently settled.</p>
                </div>
                <p className="text-sm font-semibold tabular-nums text-slate-700">
                    Sales total {money(salesTotal, currency)}
                </p>
            </div>
            <div className="grid gap-px bg-emerald-100 sm:grid-cols-3">
                <SettlementTile
                    label="Cash"
                    description="Allocated cash receipts"
                    amount={settlement.cash.amount}
                    documentCount={settlement.cash.document_count}
                    currency={currency}
                    accent="emerald"
                />
                <SettlementTile
                    label="Card"
                    description="Allocated card receipts"
                    amount={settlement.card.amount}
                    documentCount={settlement.card.document_count}
                    currency={currency}
                    accent="sky"
                />
                <SettlementTile
                    label="On credit"
                    description="Current balance outstanding"
                    amount={settlement.credit.amount}
                    documentCount={settlement.credit.document_count}
                    currency={currency}
                    accent="amber"
                />
            </div>
            <div className="flex flex-col gap-2 bg-white px-5 py-3 text-xs text-slate-500 lg:flex-row lg:items-center lg:justify-between">
                <p className="max-w-4xl leading-5">{settlement.source_note}</p>
                {supportingItems.length > 0 ? (
                    <p className="shrink-0 font-medium text-slate-600">{supportingItems.join(' · ')}</p>
                ) : null}
            </div>
        </div>
    );
}

function SettlementTile({
    label,
    description,
    amount,
    documentCount,
    currency,
    accent,
}: {
    label: string;
    description: string;
    amount: string;
    documentCount: number;
    currency: string;
    accent: 'emerald' | 'sky' | 'amber';
}) {
    const accentClasses = {
        emerald: 'bg-emerald-500',
        sky: 'bg-sky-500',
        amber: 'bg-amber-500',
    };

    return (
        <article className="relative bg-white px-5 py-5">
            <div className={`absolute inset-y-5 left-0 w-1 rounded-r-full ${accentClasses[accent]}`} />
            <div className="pl-2">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <h3 className="font-bold text-slate-950">{label}</h3>
                        <p className="mt-0.5 text-xs text-slate-500">{description}</p>
                    </div>
                    <span className="rounded-full bg-slate-100 px-2 py-1 text-[0.68rem] font-semibold text-slate-500">
                        {documentCount} sales
                    </span>
                </div>
                <p className="mt-4 text-xl font-bold tabular-nums tracking-tight text-slate-950">
                    {money(amount, currency)}
                </p>
            </div>
        </article>
    );
}

function DocumentCard({
    title,
    eyebrow,
    tone,
    metrics,
    currency,
    compact = false,
}: {
    title: string;
    eyebrow: string;
    tone: 'emerald' | 'blue' | 'amber' | 'rose';
    metrics: SummaryDocumentMetrics;
    currency: string;
    compact?: boolean;
}) {
    const tones = {
        emerald: 'from-emerald-500 to-teal-500',
        blue: 'from-sky-500 to-blue-600',
        amber: 'from-amber-400 to-orange-500',
        rose: 'from-rose-500 to-pink-600',
    };

    return (
        <article className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className={`h-1.5 bg-gradient-to-r ${tones[tone]}`} />
            <div className="p-5">
                <p className="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400">{eyebrow}</p>
                <div className="mt-2 flex items-start justify-between gap-3">
                    <h3 className="text-lg font-bold text-slate-950">{title}</h3>
                    <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                        {metrics.document_count} docs
                    </span>
                </div>
                <p className="mt-5 text-2xl font-bold tracking-tight text-slate-950">
                    {money(metrics.grand_total, currency)}
                </p>
                <dl className="mt-5 space-y-2.5 border-t border-slate-100 pt-4 text-sm">
                    <Metric label="Subtotal" value={money(metrics.subtotal, currency)} />
                    {!compact && <Metric label="Discounts" value={money(metrics.discount_total, currency)} />}
                    {!compact && <Metric label="Tax" value={money(metrics.tax_total, currency)} />}
                    {!compact && <Metric label="Charges" value={money(metrics.charge_total, currency)} />}
                    {metrics.paid_total !== undefined && (
                        <Metric label="Settled" value={money(metrics.paid_total, currency)} strong />
                    )}
                    {metrics.adjustment_total !== undefined && (
                        <Metric label="Adjustments" value={money(metrics.adjustment_total, currency)} />
                    )}
                </dl>
            </div>
        </article>
    );
}

function PaymentCard({
    title,
    tone,
    metrics,
    currency,
}: {
    title: string;
    tone: 'emerald' | 'blue';
    metrics: SummaryPaymentMetrics;
    currency: string;
}) {
    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className={`text-xs font-semibold uppercase tracking-[0.17em] ${tone === 'emerald' ? 'text-emerald-600' : 'text-blue-600'}`}>
                        {metrics.transaction_count} transactions
                    </p>
                    <h3 className="mt-1 text-lg font-bold text-slate-950">{title}</h3>
                </div>
                <p className="text-xl font-bold text-slate-950">{money(metrics.amount, currency)}</p>
            </div>
            <div className="mt-5 divide-y divide-slate-100 border-t border-slate-100">
                {metrics.methods.length > 0 ? metrics.methods.map((method) => (
                    <div key={`${method.type}-${method.name}`} className="flex items-center justify-between gap-4 py-3 text-sm">
                        <div className="min-w-0">
                            <p className="truncate font-medium text-slate-800">{method.name}</p>
                            <p className="text-xs text-slate-400">{method.transaction_count} transactions</p>
                        </div>
                        <span className="shrink-0 font-semibold tabular-nums text-slate-700">
                            {money(method.amount, currency)}
                        </span>
                    </div>
                )) : (
                    <p className="py-7 text-center text-sm text-slate-500">No posted payments in this period.</p>
                )}
            </div>
        </article>
    );
}

function BridgeRow({ label, value, currency, width, tone }: {
    label: string;
    value: string;
    currency: string;
    width: number;
    tone: string;
}) {
    return (
        <div>
            <div className="mb-2 flex items-center justify-between gap-4 text-sm">
                <span className="font-medium text-slate-700">{label}</span>
                <span className="font-semibold tabular-nums text-slate-900">{money(value, currency)}</span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                <div className={`h-full rounded-full ${tone}`} style={{ width: `${width}%` }} />
            </div>
        </div>
    );
}

function HeroMetric({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
            <p className="text-xs text-slate-400">{label}</p>
            <p className="mt-1 truncate font-semibold tabular-nums text-white">{value}</p>
        </div>
    );
}

function SectionHeading({ title, description }: { title: string; description: string }) {
    return (
        <div className="mb-4">
            <h2 className="text-lg font-bold text-slate-950">{title}</h2>
            <p className="mt-1 text-sm text-slate-500">{description}</p>
        </div>
    );
}

function Metric({ label, value, strong = false }: { label: string; value: ReactNode; strong?: boolean }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <dt className="text-slate-500">{label}</dt>
            <dd className={strong ? 'font-semibold tabular-nums text-slate-900' : 'tabular-nums text-slate-700'}>{value}</dd>
        </div>
    );
}

function money(value: string | number | undefined, currency: string): string {
    return formatMoney(value, currency);
}

function ratio(value: string, total: string): number {
    const numerator = Math.abs(Number(value));
    const denominator = Math.abs(Number(total));
    if (!Number.isFinite(numerator) || !Number.isFinite(denominator) || denominator === 0) return 0;

    return Math.min(100, Math.max(3, (numerator / denominator) * 100));
}

function formatDate(value: string): string {
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(year, month - 1, day));
}

function currentMonthRange(): DateRange {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return {
        date_from: `${year}-${month}-01`,
        date_to: `${year}-${month}-${day}`,
    };
}
