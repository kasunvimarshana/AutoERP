import { useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { reportingPermissions } from '@/modules/reporting/reportingPermissions';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { getDashboardSummary } from './dashboardApi';
import type { DashboardDateRange } from './dashboardTypes';
import {
    AgingChart,
    CashFlowChart,
    EmployeePerformance,
    InventoryHealth,
    ProfitabilityOverview,
    RevenueTrendChart,
    ServiceStatusChart,
} from './DashboardVisuals';

const INITIAL_RANGE = datePreset('month');
const REPORTING_MODULE = 'reporting';

export default function DashboardPage() {
    const auth = useAuth();
    const canViewBusinessOverview = hasPermission(auth, reportingPermissions.view)
        && auth.enabledModules?.includes(REPORTING_MODULE) === true;
    const [range, setRange] = useState<DashboardDateRange>(INITIAL_RANGE);
    const [draft, setDraft] = useState<DashboardDateRange>(INITIAL_RANGE);
    const summary = useApi(
        (signal) => getDashboardSummary(range, signal),
        [range.date_from, range.date_to],
        canViewBusinessOverview,
        false,
    );

    if (!canViewBusinessOverview) return <OperationalDashboard />;

    const applyRange = (event: FormEvent) => {
        event.preventDefault();
        setRange(draft);
    };
    const applyPreset = (preset: DatePreset) => {
        const next = datePreset(preset);
        setDraft(next);
        setRange(next);
    };
    const data = summary.data;
    const currency = data?.currency_code || 'LKR';

    return (
        <>
            <ContentHeader
                title="Business overview"
                description="Revenue, cash, workshop activity, inventory, and balances for the selected organization unit."
                actions={<LinkButton to="/vehicle-service/jobs/create">New service job</LinkButton>}
            />

            <form className="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" onSubmit={applyRange}>
                <div className="flex flex-wrap gap-2">
                    {(['today', 'last30', 'month', 'year'] as const).map((preset) => (
                        <button key={preset} type="button" onClick={() => applyPreset(preset)} className="min-h-9 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                            {presetLabel(preset)}
                        </button>
                    ))}
                </div>
                <div className="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                    <Input label="From" type="date" required value={draft.date_from} max={draft.date_to} onChange={(event) => setDraft((current) => ({ ...current, date_from: event.target.value }))} />
                    <Input label="To" type="date" required value={draft.date_to} min={draft.date_from} onChange={(event) => setDraft((current) => ({ ...current, date_to: event.target.value }))} />
                    <Button type="submit" loading={summary.loading}>Refresh dashboard</Button>
                </div>
            </form>

            <ErrorAlert error={summary.error} title="Dashboard could not be loaded" inline />
            {summary.loading && !data ? <LoadingState label="Building business overview..." /> : null}

            {data ? (
                <div className="space-y-5">
                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                        <KpiCard title="Today revenue" value={formatMoney(data.kpis.today_revenue, currency)} note="Finalized invoices today" url="/invoices" tone="emerald" />
                        <KpiCard title="Period revenue" value={formatMoney(data.kpis.period_revenue, currency)} note={`${shortDate(data.period.date_from)} – ${shortDate(data.period.date_to)}`} url="/reports/summary" tone="blue" />
                        <KpiCard title="Receivables" value={formatMoney(data.kpis.receivables, currency)} note="Current customer balance" url="/invoices" tone="amber" />
                        <KpiCard title="Supplier payables" value={formatMoney(data.kpis.payables, currency)} note="Current supplier balance" url="/reports/purchase/grn-payables" tone="rose" />
                        <KpiCard title="Active service jobs" value={String(data.kpis.active_service_jobs)} note="Draft, inspected, in progress" url="/vehicle-service/jobs" tone="violet" />
                        <KpiCard title="Inventory value" value={formatMoney(data.kpis.inventory_value, currency)} note="Current stock valuation" url="/inventory?tab=costing" tone="slate" />
                    </section>

                    <section className="grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
                        <RevenueTrendChart rows={data.revenue_trend} currency={currency} />
                        <ServiceStatusChart rows={data.service_jobs} />
                    </section>

                    <section className="grid gap-5 xl:grid-cols-[1.25fr_0.75fr]">
                        <CashFlowChart rows={data.cash_flow} currency={currency} />
                        <InventoryHealth health={data.inventory_health} />
                    </section>

                    <section className="grid gap-5 lg:grid-cols-2">
                        <AgingChart title="Receivable aging" rows={data.aging.receivables} currency={currency} url="/invoices" />
                        <AgingChart title="Payable aging" rows={data.aging.payables} currency={currency} url="/reports/purchase/grn-payables" />
                    </section>

                    <section className="grid gap-5 xl:grid-cols-[0.9fr_1.1fr]">
                        <ProfitabilityOverview profitability={data.profitability} currency={currency} />
                        <EmployeePerformance
                            rows={data.employee_performance}
                            currency={currency}
                            dateFrom={data.period.date_from}
                            dateTo={data.period.date_to}
                        />
                    </section>

                    <ActionRequired actions={data.actions} />
                </div>
            ) : null}
        </>
    );
}

function KpiCard({ title, value, note, url, tone }: { title: string; value: string; note: string; url: string; tone: keyof typeof KPI_TONES }) {
    return (
        <Link to={url} className="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md">
            <div className={`mb-3 h-1.5 w-10 rounded-full ${KPI_TONES[tone]}`} />
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{title}</p>
            <p className="mt-2 break-words text-xl font-bold tabular-nums text-slate-950">{value}</p>
            <p className="mt-2 text-xs leading-5 text-slate-500 group-hover:text-blue-700">{note}</p>
        </Link>
    );
}

function ActionRequired({ actions }: { actions: Array<{ key: string; label: string; count: number; url: string; tone: 'critical' | 'warning' | 'info' }> }) {
    const tones = {
        critical: 'border-rose-200 bg-rose-50 text-rose-800',
        warning: 'border-amber-200 bg-amber-50 text-amber-800',
        info: 'border-sky-200 bg-sky-50 text-sky-800',
    };
    return (
        <Panel title="Action required" className="rounded-2xl">
            {actions.length === 0 ? (
                <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm font-medium text-emerald-800">All caught up. No current exceptions need attention.</div>
            ) : (
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {actions.map((action) => (
                        <Link key={action.key} to={action.url} className={`flex items-center justify-between rounded-xl border p-4 transition hover:shadow-sm ${tones[action.tone]}`}>
                            <span className="text-sm font-semibold">{action.label}</span>
                            <span className="ml-3 rounded-full bg-white/80 px-2.5 py-1 text-sm font-bold tabular-nums">{action.count}</span>
                        </Link>
                    ))}
                </div>
            )}
        </Panel>
    );
}

function OperationalDashboard() {
    const links = [
        ['Create service job', '/vehicle-service/jobs/create'],
        ['Service Job List', '/vehicle-service/jobs'],
        ['Customer invoices', '/invoices'],
        ['Inventory', '/inventory'],
        ['Purchase orders', '/purchase/orders'],
        ['Employees', '/hr/employees'],
    ];
    return (
        <>
            <ContentHeader title="Task center" description="Quick access to daily operational workflows." actions={<LinkButton to="/vehicle-service/jobs/create">New service job</LinkButton>} />
            <Panel title="Quick starts" className="rounded-2xl">
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {links.map(([label, url]) => <Link key={label} to={url} className="rounded-xl border border-slate-200 p-4 text-sm font-semibold text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">{label}</Link>)}
                </div>
                <p className="mt-4 text-xs text-slate-500">Business charts require the Reporting view permission.</p>
            </Panel>
        </>
    );
}

const KPI_TONES = { emerald: 'bg-emerald-500', blue: 'bg-blue-600', amber: 'bg-amber-500', rose: 'bg-rose-500', violet: 'bg-violet-500', slate: 'bg-slate-600' } as const;
type DatePreset = 'today' | 'last30' | 'month' | 'year';

function datePreset(preset: DatePreset): DashboardDateRange {
    const today = businessDateInputValue();
    const [year, month] = today.split('-');
    if (preset === 'today') return { date_from: today, date_to: today };
    if (preset === 'last30') return { date_from: shiftDate(today, -29), date_to: today };
    if (preset === 'year') return { date_from: `${year}-01-01`, date_to: today };
    return { date_from: `${year}-${month}-01`, date_to: today };
}

function shiftDate(value: string, days: number): string {
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(Date.UTC(year, month - 1, day + days));
    return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}-${String(date.getUTCDate()).padStart(2, '0')}`;
}

function presetLabel(preset: DatePreset): string {
    return ({ today: 'Today', last30: 'Last 30 days', month: 'This month', year: 'This year' } as const)[preset];
}

function shortDate(value: string): string {
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(year, month - 1, day));
}
