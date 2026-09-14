import { Link } from 'react-router-dom';
import { formatMoney } from '@/shared/utils/formatMoney';
import type { DashboardMetricBucket, DashboardSummary } from './dashboardTypes';

export function RevenueTrendChart({ rows, currency }: { rows: DashboardSummary['revenue_trend']; currency: string }) {
    const maximum = Math.max(...rows.map((row) => Number(row.value)), 0);

    return (
        <ChartPanel title="Revenue trend" description="Finalized outbound invoice revenue in the selected period.">
            <div className="flex min-h-56 items-end gap-3 overflow-x-auto pt-8" role="img" aria-label="Revenue trend bar chart">
                {rows.map((row) => {
                    const height = maximum > 0 ? Math.max(5, (Number(row.value) / maximum) * 100) : 2;
                    return (
                        <div key={row.key} className="flex min-w-16 flex-1 flex-col items-center gap-2">
                            <span className="text-[11px] font-semibold tabular-nums text-slate-600">{compactMoney(row.value, currency)}</span>
                            <div className="flex h-36 w-full items-end rounded-lg bg-slate-100 px-2">
                                <div className="w-full rounded-t-md bg-gradient-to-t from-blue-700 to-cyan-400" style={{ height: `${height}%` }} />
                            </div>
                            <span className="text-center text-xs text-slate-500">{row.label}</span>
                        </div>
                    );
                })}
            </div>
        </ChartPanel>
    );
}

export function CashFlowChart({ rows, currency }: { rows: DashboardSummary['cash_flow']; currency: string }) {
    const maximum = Math.max(...rows.flatMap((row) => [Number(row.inbound), Number(row.outbound)]), 0);

    return (
        <ChartPanel title="Cash in vs cash out" description="Approved and posted payments in the selected period.">
            <div className="mb-4 flex gap-4 text-xs font-medium text-slate-600">
                <Legend color="bg-emerald-500" label="Cash in" />
                <Legend color="bg-amber-500" label="Cash out" />
            </div>
            <div className="space-y-4">
                {rows.map((row) => (
                    <div key={row.key} className="grid grid-cols-[4.5rem_1fr] items-center gap-3">
                        <span className="text-xs font-medium text-slate-500">{row.label}</span>
                        <div className="space-y-1.5">
                            <AmountBar value={row.inbound} maximum={maximum} color="bg-emerald-500" label={`Cash in ${formatMoney(row.inbound, currency)}`} />
                            <AmountBar value={row.outbound} maximum={maximum} color="bg-amber-500" label={`Cash out ${formatMoney(row.outbound, currency)}`} />
                        </div>
                    </div>
                ))}
            </div>
        </ChartPanel>
    );
}

export function ServiceStatusChart({ rows }: { rows: DashboardSummary['service_jobs'] }) {
    const total = rows.reduce((sum, row) => sum + row.count, 0);
    const palette = ['bg-slate-400', 'bg-cyan-500', 'bg-blue-600', 'bg-violet-500', 'bg-amber-500', 'bg-orange-500', 'bg-emerald-500', 'bg-rose-500'];

    return (
        <ChartPanel title="Service job status" description="Jobs created inside the selected period.">
            <div className="space-y-3">
                {rows.map((row, index) => (
                    <Link key={row.status} to={row.url} className="group grid grid-cols-[7rem_1fr_2.5rem] items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                        <span className="truncate text-xs font-medium text-slate-600 group-hover:text-blue-700">{row.label}</span>
                        <div className="h-2.5 overflow-hidden rounded-full bg-slate-100">
                            <div className={`h-full rounded-full ${palette[index % palette.length]}`} style={{ width: `${total > 0 ? Math.max(2, (row.count / total) * 100) : 0}%` }} />
                        </div>
                        <span className="text-right text-sm font-bold tabular-nums text-slate-900">{row.count}</span>
                    </Link>
                ))}
            </div>
        </ChartPanel>
    );
}

export function InventoryHealth({ health }: { health: DashboardSummary['inventory_health'] }) {
    const metrics = [
        ['In stock', health.in_stock_items, 'text-emerald-700', '/inventory?tab=availability'],
        ['Low stock', health.low_stock_items, 'text-amber-700', '/inventory?tab=availability'],
        ['Out of stock', health.out_of_stock_items, 'text-rose-700', '/inventory?tab=availability'],
        ['Expiring batches', health.expiring_batches, 'text-violet-700', '/inventory?tab=tracking'],
    ] as const;

    return (
        <ChartPanel title="Inventory health" description="Current availability and batches expiring within 30 days.">
            <div className="grid grid-cols-2 gap-3">
                {metrics.map(([label, value, color, url]) => (
                    <Link key={label} to={url} className="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-300 hover:bg-blue-50">
                        <p className={`text-2xl font-bold tabular-nums ${color}`}>{value}</p>
                        <p className="mt-1 text-xs font-medium text-slate-600">{label}</p>
                    </Link>
                ))}
            </div>
        </ChartPanel>
    );
}

export function AgingChart({ title, rows, currency, url }: { title: string; rows: DashboardMetricBucket[]; currency: string; url: string }) {
    const maximum = Math.max(...rows.map((row) => Number(row.amount)), 0);

    return (
        <ChartPanel title={title} description="Current outstanding balance grouped by due-date age.">
            <Link to={url} className="block space-y-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                {rows.map((row) => (
                    <div key={row.key}>
                        <div className="mb-1 flex items-center justify-between gap-3 text-xs">
                            <span className="font-medium text-slate-600">{row.label} <span className="text-slate-400">({row.count})</span></span>
                            <span className="font-semibold tabular-nums text-slate-900">{formatMoney(row.amount, currency)}</span>
                        </div>
                        <AmountBar value={row.amount} maximum={maximum} color={row.key === 'current' ? 'bg-sky-500' : 'bg-rose-500'} label={`${row.label}: ${formatMoney(row.amount, currency)}`} />
                    </div>
                ))}
            </Link>
        </ChartPanel>
    );
}

function ChartPanel({ title, description, children }: { title: string; description: string; children: React.ReactNode }) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 className="text-base font-bold text-slate-950">{title}</h2>
            <p className="mt-1 text-sm text-slate-500">{description}</p>
            <div className="mt-5">{children}</div>
        </section>
    );
}

function AmountBar({ value, maximum, color, label }: { value: string; maximum: number; color: string; label: string }) {
    const width = maximum > 0 ? Math.max(Number(value) > 0 ? 2 : 0, (Number(value) / maximum) * 100) : 0;
    return (
        <div className="h-2.5 overflow-hidden rounded-full bg-slate-100" title={label} aria-label={label}>
            <div className={`h-full rounded-full ${color}`} style={{ width: `${Math.min(100, width)}%` }} />
        </div>
    );
}

function Legend({ color, label }: { color: string; label: string }) {
    return <span className="inline-flex items-center gap-2"><span className={`h-2.5 w-2.5 rounded-full ${color}`} />{label}</span>;
}

function compactMoney(value: string, currency: string): string {
    const amount = Number(value);
    if (!Number.isFinite(amount)) return formatMoney(value, currency);
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency || 'LKR',
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(amount);
}
