import { useEffect, useState, type FormEvent } from 'react';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Pagination } from '@/shared/components/Pagination';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { listChartRegister } from './runningChartApi';
import { AC_LABELS, CHART_LABELS, COUNT_LABELS, DISTANCE_LABELS, RunningChartStatus, type ChartRegisterFilters, type ChartRegisterRow } from './runningCharts';
import { operationalTimeZone, OPERATIONAL_TIME_STEP_SECONDS, timestampWithOffset } from './vehicleUse';
import { RunningChartHistoryPanel } from './RunningChartHistoryPanel';

export default function RunningChartRegisterPage() {
    const [search, setSearch] = useState(''); const [status, setStatus] = useState<RunningChartStatus | ''>('');
    const [from, setFrom] = useState(''); const [until, setUntil] = useState('');
    const [filters, setFilters] = useState<ChartRegisterFilters>({}); const [page, setPage] = useState(1);
    const [rows, setRows] = useState<ChartRegisterRow[]>([]); const [meta, setMeta] = useState<PaginationMeta>();
    const [loading, setLoading] = useState(true); const [error, setError] = useState<ApiError | null>(null); const [selected, setSelected] = useState<number | null>(null);
    const [history, setHistory] = useState<number | null>(null);
    useEffect(() => {
        const controller = new AbortController();
        listChartRegister(filters, page, controller.signal).then(result => {
            if (!controller.signal.aborted) { setRows(result.data); setMeta(result.meta); setError(null); }
        }).catch(failure => { if (!controller.signal.aborted) setError(toApiError(failure)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [filters, page]);
    function apply(event: FormEvent) {
        event.preventDefault();
        try {
            const next = { search: search.trim() || undefined, chart_status: status || undefined, from: from ? timestampWithOffset(from) : undefined, until: until ? timestampWithOffset(until) : undefined };
            setLoading(true); setError(null); setPage(1); setSelected(null); setHistory(null); setFilters(next);
        } catch (failure) { setError(toApiError(failure)); }
    }
    return <main className="space-y-5 p-4">
        <header><h1 className="text-2xl font-semibold">Running Chart register</h1><p>Review recorded usage, agreement context and corrections.</p></header>
        <form aria-label="Filter Running Charts" onSubmit={apply} className="grid gap-3 rounded-lg border p-4 md:grid-cols-2">
            <Input label="Chart, vehicle, agreement or party" value={search} onChange={event => setSearch(event.target.value)} error={error?.fields.search?.[0]} />
            <label className="block">Chart status<select className="block w-full rounded border p-2" value={status} onChange={event => setStatus(event.target.value as RunningChartStatus | '')}><option value="">All states</option>{Object.values(RunningChartStatus).map(value => <option key={value} value={value}>{CHART_LABELS[value]}</option>)}</select></label>
            <Input label="Period start (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} value={from} onChange={event => setFrom(event.target.value)} error={error?.fields.from?.[0]} />
            <Input label="Period end (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} value={until} onChange={event => setUntil(event.target.value)} error={error?.fields.until?.[0]} />
            <p className="text-sm md:col-span-2">Times use {operationalTimeZone}. Results include charts overlapping the period and show their full recorded quantities. No quantities are prorated.</p>
            <Button type="submit" loading={loading}>Apply filters</Button>
        </form>
        <ErrorAlert error={error} inline />
        {loading ? <p role="status">Loading Running Chart register…</p> : error ? null : <>
            {rows.length === 0 && <p>No Running Charts match these filters.</p>}
            {rows.map(row => <article key={row.id} className="space-y-2 rounded-lg border p-4">
                <div className="flex flex-wrap justify-between gap-2"><h2 className="font-semibold">{row.reference} · {row.vehicle_use.vehicle_label}</h2><span>{CHART_LABELS[row.status]}</span></div>
                <p>Customer: {row.customer_agreement.party_name} · {row.customer_agreement.reference}</p>
                <p>{row.owner_agreement ? `Owner: ${row.owner_agreement.party_name} · ${row.owner_agreement.reference}` : 'Company supply'}</p>
                <p>{row.starts_at} — {row.ends_at}</p><p>Total distance: {row.total_km === null ? 'Not recorded' : `${row.total_km} km`}</p>
                {row.replaces_vehicle && <p>Replaces vehicle {row.replaces_vehicle}</p>}{row.corrects_chart && <p>Corrects chart {row.corrects_chart.reference}</p>}
                <Button variant="secondary" onClick={() => setSelected(selected === row.id ? null : row.id)}>Review {row.reference}</Button>
                {selected === row.id && <div className="space-y-3 border-t pt-3">
                    <dl className="grid gap-2 sm:grid-cols-2">{Object.entries({ ...DISTANCE_LABELS, ...COUNT_LABELS }).map(([key, label]) => <div key={key}><dt>{label}</dt><dd>{row[key as keyof typeof DISTANCE_LABELS | keyof typeof COUNT_LABELS] ?? 'Not recorded'}</dd></div>)}</dl>
                    <p>Air conditioning: {row.ac_mode === null ? 'Not recorded' : AC_LABELS[row.ac_mode]}</p>
                    <p>Driver observation: {row.driver_observation ?? 'Not recorded'}</p><p>Notes: {row.notes ?? 'Not recorded'}</p>
                    <Button variant="secondary" onClick={() => setHistory(history === row.id ? null : row.id)}>Chart history</Button>
                    {history === row.id && <RunningChartHistoryPanel key={row.id} id={row.id} />}
                </div>}
            </article>)}
            <Pagination meta={meta} onPageChange={next => { setLoading(true); setSelected(null); setHistory(null); setPage(next); }} />
        </>}
    </main>;
}
