import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { cn } from '../../utils/cn';
import { Button } from '../ui/Button';
import { Spinner } from '../ui/Spinner';

export function PageHeader({ actions, eyebrow, subtitle, title }: { actions?: ReactNode; eyebrow?: string; subtitle?: string; title: string }) {
    return (
        <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0">
                {eyebrow ? <p className="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">{eyebrow}</p> : null}
                <h1 className="mt-1 truncate text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{title}</h1>
                {subtitle ? <p className="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{subtitle}</p> : null}
            </div>
            {actions ? <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div> : null}
        </header>
    );
}

export function Breadcrumb({ items }: { items: Array<{ label: string; to?: string }> }) {
    return (
        <nav aria-label="Breadcrumb" className="flex flex-wrap items-center gap-1 text-xs font-semibold text-slate-400">
            {items.map((item, index) => (
                <span className="flex items-center gap-1" key={`${item.label}-${index}`}>
                    {index > 0 ? <span>/</span> : null}
                    {item.to ? <Link className="hover:text-blue-600" to={item.to}>{item.label}</Link> : <span className="text-slate-600">{item.label}</span>}
                </span>
            ))}
        </nav>
    );
}

export function PrimaryLink({ children, to }: { children: ReactNode; to: string }) {
    return <Link className="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300" to={to}>{children}</Link>;
}

export function SecondaryLink({ children, to }: { children: ReactNode; to: string }) {
    return <Link className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" to={to}>{children}</Link>;
}

export function FilterCard({ children, className }: { children: ReactNode; className?: string }) {
    return <div className={cn('grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40', className)}>{children}</div>;
}

export function TableCard({ children }: { children: ReactNode }) {
    return <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/40">{children}</div>;
}

export function EmptyState({ action, description = 'Adjust the current filters or create a new record.', title }: { action?: ReactNode; description?: string; title: string }) {
    return (
        <div className="flex min-h-56 flex-col items-center justify-center px-6 py-12 text-center">
            <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M4 5h16v14H4zM8 9h8M8 13h5" /></svg>
            </span>
            <p className="mt-4 font-semibold text-slate-800">{title}</p>
            <p className="mt-1 max-w-md text-sm leading-6 text-slate-500">{description}</p>
            {action ? <div className="mt-4">{action}</div> : null}
        </div>
    );
}

export function LoadingState({ label = 'Loading' }: { label?: string }) {
    return <div className="flex min-h-56 items-center justify-center p-12 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">{label}</span></div>;
}

const statusStyles: Record<string, string> = {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    cancelled: 'bg-rose-50 text-rose-700 ring-rose-600/15',
    closed: 'bg-slate-100 text-slate-700 ring-slate-500/15',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    confirmed: 'bg-indigo-50 text-indigo-700 ring-indigo-600/15',
    'credit exceeded': 'bg-red-50 text-red-700 ring-red-600/15',
    credited: 'bg-violet-50 text-violet-700 ring-violet-600/15',
    draft: 'bg-slate-100 text-slate-700 ring-slate-500/15',
    healthy: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    'high risk': 'bg-orange-50 text-orange-700 ring-orange-600/15',
    inactive: 'bg-slate-100 text-slate-600 ring-slate-500/15',
    in_progress: 'bg-amber-50 text-amber-700 ring-amber-600/15',
    invoiced: 'bg-cyan-50 text-cyan-700 ring-cyan-600/15',
    issued: 'bg-blue-50 text-blue-700 ring-blue-600/15',
    open: 'bg-blue-50 text-blue-700 ring-blue-600/15',
    paid: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    partially_paid: 'bg-amber-50 text-amber-700 ring-amber-600/15',
    partially_received: 'bg-amber-50 text-amber-700 ring-amber-600/15',
    posted: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    received: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    'watch closely': 'bg-amber-50 text-amber-700 ring-amber-600/15',
};

export function StatusBadge({ value }: { value?: string | null }) {
    const normalized = value?.toLowerCase() || 'unknown';
    return <span className={cn('inline-flex rounded-full px-2.5 py-1 text-xs font-bold capitalize ring-1 ring-inset', statusStyles[normalized] ?? 'bg-blue-50 text-blue-700 ring-blue-600/15')}>{normalized.replaceAll('_', ' ')}</span>;
}

export function MoneyDisplay({ className, value }: { className?: string; value?: number | string | null }) {
    const amount = Number(value || 0);
    return <span className={cn('tabular-nums', className)}>{Number.isFinite(amount) ? amount.toLocaleString(undefined, { maximumFractionDigits: 4, minimumFractionDigits: 2 }) : '0.00'}</span>;
}

export function DateDisplay({ value }: { value?: string | null }) {
    if (!value) return <span className="text-slate-400">Not set</span>;
    const date = new Date(`${value.slice(0, 10)}T00:00:00`);
    return <time dateTime={value}>{Number.isNaN(date.getTime()) ? value : date.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })}</time>;
}

export function FormSection({ children, description, title }: { children: ReactNode; description?: string; title: string }) {
    return (
        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40 sm:p-6">
            <div className="border-b border-slate-100 pb-4">
                <h2 className="text-base font-bold text-slate-950">{title}</h2>
                {description ? <p className="mt-1 text-sm leading-6 text-slate-500">{description}</p> : null}
            </div>
            <div className="mt-5">{children}</div>
        </section>
    );
}

export function StatCard({ label, value }: { label: string; value: ReactNode }) {
    return <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40"><p className="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">{label}</p><p className="mt-2 text-2xl font-bold tracking-tight text-slate-950">{value}</p></div>;
}

export function SummaryCard({ children, title }: { children: ReactNode; title: string }) {
    return <aside className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40"><h2 className="text-sm font-bold uppercase tracking-[0.12em] text-slate-400">{title}</h2><div className="mt-4 space-y-4">{children}</div></aside>;
}

export function SummaryRow({ label, value }: { label: string; value: ReactNode }) {
    return <div className="flex items-start justify-between gap-4 text-sm"><span className="text-slate-500">{label}</span><span className="text-right font-semibold text-slate-900">{value}</span></div>;
}

export function CreditUtilization({ availableCredit, creditLimit, currentBalance }: { availableCredit?: number | string | null; creditLimit?: number | string | null; currentBalance?: number | string | null }) {
    const limit = Number(creditLimit || 0);
    const balance = Number(currentBalance || 0);
    const available = availableCredit === null || availableCredit === undefined ? limit - balance : Number(availableCredit);
    const utilization = limit > 0 ? Math.max(0, Math.round((balance / limit) * 100)) : 0;
    const state = utilization >= 100 ? 'Credit exceeded' : utilization >= 90 ? 'High risk' : utilization >= 80 ? 'Watch closely' : 'Healthy';
    const bar = utilization >= 100 ? 'bg-red-500' : utilization >= 90 ? 'bg-orange-500' : utilization >= 80 ? 'bg-amber-500' : 'bg-emerald-500';

    return (
        <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
            <div className="flex items-center justify-between gap-3">
                <p className="text-xs font-bold uppercase tracking-wide text-slate-400">Credit exposure</p>
                <StatusBadge value={state} />
            </div>
            <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                <div className={cn('h-full rounded-full transition-all', bar)} style={{ width: `${Math.min(100, utilization)}%` }} />
            </div>
            <div className="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                <div><p className="text-xs text-slate-400">Limit</p><MoneyDisplay className="font-bold" value={limit} /></div>
                <div><p className="text-xs text-slate-400">Outstanding</p><MoneyDisplay className="font-bold" value={balance} /></div>
                <div><p className="text-xs text-slate-400">Available</p><MoneyDisplay className="font-bold" value={available} /></div>
            </div>
        </div>
    );
}

export function Pagination({ current, last, loading, onPage, total }: { current: number; last: number; loading?: boolean; onPage: (page: number) => void; total?: number }) {
    return (
        <div className="flex flex-col gap-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <span>{total === undefined ? null : `${total} total records`}</span>
            <div className="flex items-center gap-2">
                <Button disabled={current <= 1 || loading} onClick={() => onPage(current - 1)} variant="secondary">Previous</Button>
                <span className="px-2 text-xs font-semibold text-slate-500">Page {current} of {Math.max(1, last)}</span>
                <Button disabled={current >= last || loading} onClick={() => onPage(current + 1)} variant="secondary">Next</Button>
            </div>
        </div>
    );
}

export function TotalsSummaryCard({ grandTotal, rows }: { grandTotal: number | string; rows: Array<{ label: string; value: number | string }> }) {
    return (
        <div className="ml-auto max-w-lg rounded-xl bg-slate-950 p-5 text-white shadow-lg shadow-slate-300/50">
            <div className="space-y-2 text-sm">
                {rows.map((row) => <div className="flex justify-between gap-6" key={row.label}><span className="text-slate-400">{row.label}</span><MoneyDisplay className="font-semibold text-slate-100" value={row.value} /></div>)}
            </div>
            <div className="mt-4 flex items-end justify-between border-t border-slate-700 pt-4">
                <span className="font-semibold">Grand total</span>
                <MoneyDisplay className="text-2xl font-bold" value={grandTotal} />
            </div>
        </div>
    );
}
