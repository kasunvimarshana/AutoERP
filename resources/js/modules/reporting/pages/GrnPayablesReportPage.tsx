import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Button, LinkButton } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { formatMoney } from '@/shared/utils/formatMoney';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import { SupplierLookupSelect } from '@/modules/purchase/components/PurchaseLookups';
import { ExportActions } from '../components/ExportActions';
import { ReportDataGrid } from '../components/ReportDataGrid';
import { runGrnPayablesReport } from '../reportingApi';
import type {
    GrnPayablesReportParams,
    GrnPayablesReportResult,
    GrnPayablesSupplierRow,
} from '../reportingTypes';

const REPORT_KEY = 'purchase/grn-payables';
const INITIAL_PARAMS: GrnPayablesReportParams = { page: 1, per_page: 25 };

const INVOICE_PROGRESS_OPTIONS = [
    { value: '', label: 'All invoice progress' },
    { value: 'not_invoiced', label: 'Not invoiced' },
    { value: 'partially_invoiced', label: 'Partially invoiced' },
    { value: 'invoiced', label: 'Invoiced' },
];

const EXPOSURE_OPTIONS = [
    { value: '', label: 'All exposure states' },
    { value: 'open', label: 'Amount outstanding' },
    { value: 'settled', label: 'No remaining exposure' },
    { value: 'credit', label: 'Supplier credit' },
];

export default function GrnPayablesReportPage() {
    const [params, setParams] = useState<GrnPayablesReportParams>(INITIAL_PARAMS);
    const [draft, setDraft] = useState<GrnPayablesReportParams>(INITIAL_PARAMS);
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [result, setResult] = useState<GrnPayablesReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (controller.signal.aborted) return;
            setLoading(true);
            setError(null);
        });

        runGrnPayablesReport(params, controller.signal)
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
        setSupplier(null);
        setDraft(INITIAL_PARAMS);
        setParams(INITIAL_PARAMS);
    };

    const sort = (column: string) => {
        setParams((current) => {
            const next: GrnPayablesReportParams = {
                ...current,
                page: 1,
                sort: column,
                direction: current.sort === column && current.direction !== 'asc' ? 'asc' : 'desc',
            };
            setDraft(next);
            return next;
        });
    };

    if (loading && !result) return <LoadingState label="Building GRN payables report..." />;

    const currency = result?.currency_code || 'LKR';

    return (
        <>
            <ContentHeader
                title="GRN Payables & GRNI"
                description="Expected supplier exposure from posted GRNs, linked invoices, settlement, return credits, and the GRNI ledger."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} />

            <form onSubmit={apply}>
                <Panel title="Filters">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Input
                            label="Search GRN or supplier"
                            value={draft.search ?? ''}
                            onChange={(event) => setDraft((current) => ({ ...current, search: event.target.value || undefined }))}
                        />
                        <Input
                            label="Received from"
                            type="date"
                            value={draft.date_from ?? ''}
                            max={draft.date_to}
                            onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value || undefined }))}
                        />
                        <Input
                            label="Received to"
                            type="date"
                            value={draft.date_to ?? ''}
                            min={draft.date_from}
                            onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value || undefined }))}
                        />
                        <SupplierLookupSelect
                            value={supplier}
                            onChange={(value) => {
                                setSupplier(value);
                                setDraft((current) => ({ ...current, supplier_id: value?.id ?? null }));
                            }}
                            loadOnOpen
                            minSearchLength={0}
                        />
                        <Select
                            label="Invoice progress"
                            value={draft.invoice_progress ?? ''}
                            options={INVOICE_PROGRESS_OPTIONS}
                            onChange={(event) => setDraft((current) => ({
                                ...current,
                                invoice_progress: event.target.value as GrnPayablesReportParams['invoice_progress'] || undefined,
                            }))}
                        />
                        <Select
                            label="Exposure"
                            value={draft.exposure_status ?? ''}
                            options={EXPOSURE_OPTIONS}
                            onChange={(event) => setDraft((current) => ({
                                ...current,
                                exposure_status: event.target.value as GrnPayablesReportParams['exposure_status'] || undefined,
                            }))}
                        />
                    </div>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button type="submit" loading={loading}>Apply filters</Button>
                        <Button type="button" variant="secondary" onClick={reset}>Reset</Button>
                    </div>
                </Panel>
            </form>

            {result ? (
                <div className="mt-5 space-y-5">
                    <ExposureHero result={result} currency={currency} />
                    <WorkflowOverview result={result} currency={currency} />
                    <SupplierBreakdown rows={result.suppliers} currency={currency} />

                    <section>
                        <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-bold text-slate-950">GRN reconciliation details</h2>
                                <p className="mt-1 text-sm text-slate-500">
                                    {loading ? 'Refreshing...' : `${result.meta?.total ?? 0} posted GRNs match the current filters.`}
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

                    <BasisNote basis={result.basis} />
                </div>
            ) : null}
        </>
    );
}

function ExposureHero({ result, currency }: { result: GrnPayablesReportResult; currency: string }) {
    const summary = result.summary;

    return (
        <section className="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-7 text-white shadow-xl shadow-slate-200 sm:px-8">
            <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-cyan-400/15 blur-3xl" />
            <div className="absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-amber-400/10 blur-3xl" />
            <div className="relative grid gap-7 xl:grid-cols-[1.15fr_1fr] xl:items-end">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-300">Projected supplier exposure</p>
                    <p className="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">
                        {formatMoney(summary.projected_exposure, currency)}
                    </p>
                    <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                        Expected uninvoiced GRN value plus finalized invoice balances, less posted unallocated return credits.
                    </p>
                </div>
                <div className="grid grid-cols-2 gap-3">
                    <HeroMetric label="Expected uninvoiced" value={formatMoney(summary.uninvoiced_amount, currency)} />
                    <HeroMetric label="AP outstanding" value={formatMoney(summary.ap_outstanding, currency)} />
                    <HeroMetric label="GRNI liability" value={formatMoney(summary.grni_balance, currency)} />
                    <HeroMetric label="Accounting liability" value={formatMoney(summary.accounting_liability, currency)} />
                </div>
            </div>
        </section>
    );
}

function WorkflowOverview({ result, currency }: { result: GrnPayablesReportResult; currency: string }) {
    const summary = result.summary;

    return (
        <section>
            <SectionHeading title="Invoice and settlement flow" description="Current processing state for the posted GRNs in scope." />
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <FlowCard tone="amber" label="Not invoiced" count={summary.not_invoiced_count} amount={summary.not_invoiced_amount} currency={currency} />
                <FlowCard tone="sky" label="Partially invoiced" count={summary.partially_invoiced_count} amount={summary.partially_invoiced_amount} currency={currency} secondaryLabel="Remaining expected value" />
                <FlowCard tone="emerald" label="Fully invoiced" count={summary.invoiced_count} amount={summary.invoiced_ap_outstanding} currency={currency} secondaryLabel="Current AP outstanding" />
                <FlowCard tone="rose" label="Open return credits" count={summary.open_return_credit_count} amount={summary.open_return_credit} currency={currency} secondaryLabel="Reduces projected exposure" />
            </div>
        </section>
    );
}

function SupplierBreakdown({ rows, currency }: { rows: GrnPayablesSupplierRow[]; currency: string }) {
    return (
        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="border-b border-slate-100 px-5 py-4">
                <h2 className="text-lg font-bold text-slate-950">Supplier exposure</h2>
                <p className="mt-1 text-sm text-slate-500">Top suppliers by projected exposure for the selected GRNs.</p>
            </div>
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th className="px-5 py-3">Supplier</th>
                            <th className="px-4 py-3 text-right">GRNs</th>
                            <th className="px-4 py-3 text-right">Uninvoiced</th>
                            <th className="px-4 py-3 text-right">AP outstanding</th>
                            <th className="px-4 py-3 text-right">Return credits</th>
                            <th className="px-5 py-3 text-right">Projected exposure</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.length > 0 ? rows.map((row) => (
                            <tr key={row.supplier}>
                                <td className="whitespace-nowrap px-5 py-3 font-medium text-slate-900">{row.supplier || 'Supplier unavailable'}</td>
                                <td className="px-4 py-3 text-right tabular-nums text-slate-600">{row.grn_count}</td>
                                <MoneyCell value={row.uninvoiced_amount} currency={currency} />
                                <MoneyCell value={row.ap_outstanding} currency={currency} />
                                <MoneyCell value={row.open_return_credit} currency={currency} />
                                <td className="whitespace-nowrap px-5 py-3 text-right font-bold tabular-nums text-slate-950">
                                    {formatMoney(row.projected_exposure, currency)}
                                </td>
                            </tr>
                        )) : (
                            <tr><td colSpan={6} className="px-5 py-8 text-center text-slate-500">No supplier exposure in the selected scope.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </section>
    );
}

function BasisNote({ basis }: { basis: GrnPayablesReportResult['basis'] }) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
            <h2 className="font-bold text-slate-900">How the figures are calculated</h2>
            <dl className="mt-3 grid gap-3 text-sm text-slate-600 md:grid-cols-2 xl:grid-cols-4">
                <BasisItem label="Projected exposure">{basis.projected_exposure}</BasisItem>
                <BasisItem label="Accounting liability">{basis.accounting_liability}</BasisItem>
                <BasisItem label="Shared invoices">{basis.invoice_allocation}</BasisItem>
                <BasisItem label="Date scope">{basis.scope}</BasisItem>
            </dl>
        </section>
    );
}

function FlowCard({ tone, label, count, amount, currency, secondaryLabel = 'Expected value' }: {
    tone: 'amber' | 'sky' | 'emerald' | 'rose';
    label: string;
    count: number;
    amount: string;
    currency: string;
    secondaryLabel?: string;
}) {
    const tones = { amber: 'bg-amber-400', sky: 'bg-sky-500', emerald: 'bg-emerald-500', rose: 'bg-rose-500' };

    return (
        <article className="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className={`absolute inset-y-5 left-0 w-1 rounded-r-full ${tones[tone]}`} />
            <div className="pl-2">
                <div className="flex items-start justify-between gap-3">
                    <h3 className="font-bold text-slate-950">{label}</h3>
                    <span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{count} GRNs</span>
                </div>
                <p className="mt-4 text-xl font-bold tabular-nums text-slate-950">{formatMoney(amount, currency)}</p>
                <p className="mt-1 text-xs text-slate-500">{secondaryLabel}</p>
            </div>
        </article>
    );
}

function MoneyCell({ value, currency }: { value: string; currency: string }) {
    return <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-slate-700">{formatMoney(value, currency)}</td>;
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

function BasisItem({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <dt className="font-semibold text-slate-800">{label}</dt>
            <dd className="mt-1 leading-5">{children}</dd>
        </div>
    );
}
