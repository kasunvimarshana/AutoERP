import { Link } from 'react-router-dom';
import { formatMoney } from '@/shared/utils/formatMoney';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import type { DashboardSummary } from './dashboardTypes';

const RECENT_WEEK_LIMIT = 8;
const PERCENT_SCALE = 100;

export function ServiceSalesCharts({ sales, currency }: { sales: DashboardSummary['service_sales']; currency: string }) {
    return (
        <section aria-label="Service sales insights" className="space-y-3">
            <div className="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 className="text-lg font-bold text-slate-950">Service sales</h2>
                    <p className="text-sm text-slate-500">Invoiced work in the selected period.</p>
                </div>
                <Link to="/reports/vehicle-service/sales-summary" className="text-sm font-semibold text-blue-700 hover:underline">View sales report</Link>
            </div>
            <div className="grid gap-5 xl:grid-cols-3">
                <WeeklyServiceSales rows={sales.by_week} currency={currency} />
                <JobTypeProfit rows={sales.by_job_type} currency={currency} />
                <TopItemsAtReorderRisk rows={sales.top_stock_items} currency={currency} />
            </div>
        </section>
    );
}

function WeeklyServiceSales({ rows, currency }: { rows: DashboardSummary['service_sales']['by_week']; currency: string }) {
    const recent = rows.slice(-RECENT_WEEK_LIMIT);
    const maximumJobs = Math.max(...recent.map((row) => row.job_count), 0);
    const maximumRevenue = Math.max(...recent.map((row) => Number(row.revenue)), 0);

    return (
        <ChartCard title="Weekly service sales" description="Jobs invoiced and revenue by invoice week. Shows the latest weeks in the selected period.">
            {recent.every((row) => row.job_count === 0) ? <EmptyState>No service jobs were invoiced in this period.</EmptyState> :
                <div className="space-y-3">
                    <div className="flex gap-4 text-xs text-slate-600"><Legend color="bg-blue-600" label="Jobs" /><Legend color="bg-emerald-500" label="Revenue" /></div>
                    {recent.map((row) => <div key={row.week_start} className="grid grid-cols-[4.5rem_1fr] items-center gap-2 text-xs">
                        <span className="font-medium text-slate-600">{weekLabel(row.week_start)}</span>
                        <div className="space-y-1">
                            <Bar value={row.job_count} maximum={maximumJobs} color="bg-blue-600" label={`${row.job_count} jobs`} />
                            <Bar value={Number(row.revenue)} maximum={maximumRevenue} color="bg-emerald-500" label={formatMoney(row.revenue, currency)} />
                        </div>
                    </div>)}
                </div>}
        </ChartCard>
    );
}

function JobTypeProfit({ rows, currency }: { rows: DashboardSummary['service_sales']['by_job_type']; currency: string }) {
    const maximum = Math.max(...rows.map((row) => Math.abs(Number(row.profit))), 0);

    return (
        <ChartCard title="Profit by job type" description="Invoiced revenue less direct costs and commission.">
            {rows.length === 0 ? <EmptyState>No invoiced job profit is available in this period.</EmptyState> :
                <div className="space-y-4">
                    {rows.map((row) => <div key={row.type}>
                        <div className="mb-1 flex items-center justify-between gap-2 text-xs">
                            <span className="font-medium text-slate-700">{row.label} <span className="text-slate-400">({row.job_count})</span></span>
                            <span className={`font-semibold tabular-nums ${Number(row.profit) < 0 ? 'text-rose-700' : 'text-emerald-700'}`}>{formatMoney(row.profit, currency)}</span>
                        </div>
                        <Bar value={Math.abs(Number(row.profit))} maximum={maximum} color={Number(row.profit) < 0 ? 'bg-rose-500' : 'bg-emerald-500'} label={`${row.label} profit ${formatMoney(row.profit, currency)}`} />
                    </div>)}
                </div>}
        </ChartCard>
    );
}

function TopItemsAtReorderRisk({ rows, currency }: { rows: DashboardSummary['service_sales']['top_stock_items']; currency: string }) {
    const maximum = Math.max(...rows.map((row) => Number(row.quantity_sold)), 0);

    return (
        <ChartCard title="Top items and stock" description="Most stock units invoiced for service jobs; availability is current.">
            {rows.length === 0 ? <EmptyState>No stock items were invoiced in this period.</EmptyState> :
                <div className="space-y-4">
                    {rows.map((row) => <div key={row.item.id}>
                        <div className="mb-1 flex items-start justify-between gap-2 text-xs">
                            <span className="min-w-0 truncate font-medium text-slate-800" title={row.item.name}>{row.item.name}</span>
                            {row.needs_reorder && <span className="shrink-0 rounded bg-amber-100 px-1.5 py-0.5 font-semibold text-amber-800">Reorder</span>}
                        </div>
                        <Bar value={Number(row.quantity_sold)} maximum={maximum} color="bg-blue-600" label={`${row.item.name}: ${formatQuantity(row.quantity_sold)} units sold`} />
                        <div className="mt-1 flex flex-wrap justify-between gap-x-2 text-xs text-slate-500">
                            <span>{formatQuantity(row.quantity_sold)} sold · {formatMoney(row.revenue, currency)}</span>
                            <span>{formatQuantity(row.quantity_available)} available · {row.reorder_level === null ? 'No reorder level' : `Reorder at ${formatQuantity(row.reorder_level)}`}</span>
                        </div>
                    </div>)}
                    <Link to="/reports/inventory.low-stock" className="inline-block text-xs font-semibold text-blue-700 hover:underline">View low stock report</Link>
                </div>}
        </ChartCard>
    );
}

function ChartCard({ title, description, children }: { title: string; description: string; children: React.ReactNode }) {
    return <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 className="font-bold text-slate-950">{title}</h3>
        <p className="mt-1 text-sm text-slate-500">{description}</p>
        <div className="mt-5">{children}</div>
    </div>;
}

function EmptyState({ children }: { children: React.ReactNode }) {
    return <p className="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{children}</p>;
}

function Legend({ color, label }: { color: string; label: string }) {
    return <span className="inline-flex items-center gap-1.5"><span className={`h-2 w-2 rounded ${color}`} />{label}</span>;
}

function Bar({ value, maximum, color, label }: { value: number; maximum: number; color: string; label: string }) {
    return <div className="h-2.5 overflow-hidden rounded-full bg-slate-100" role="img" aria-label={label}>
        <div className={`h-full rounded-full ${color}`} style={{ width: `${maximum > 0 ? value / maximum * PERCENT_SCALE : 0}%` }} />
    </div>;
}

function weekLabel(value: string): string {
    return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', timeZone: 'UTC' }).format(new Date(`${value}T00:00:00Z`));
}
