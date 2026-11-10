import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { formatBusinessDateTime, toHourFloor } from '@/shared/utils/businessDate';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { exportRunningChartReport, listChartRegister } from './runningChartApi';
import { AC_LABELS, CHART_LABELS, COUNT_LABELS, DISTANCE_LABELS, RunningChartStatus, type ChartRegisterFilters, type ChartRegisterRow } from './runningCharts';
import { operationalTimeZoneLabel, OPERATIONAL_TIME_STEP_SECONDS, timestampWithOffset } from './vehicleUse';
import { UsageChargePanel } from './UsageChargePanel';
import { RunningChartHistoryPanel } from './RunningChartHistoryPanel';

export default function RunningChartRegisterPage() {
    const [search, setSearch] = useState(() => {
        if (typeof window === 'undefined') return '';
        return new URLSearchParams(window.location.search).get('search') ?? '';
    });
    const [status, setStatus] = useState<RunningChartStatus | ''>(() => {
        if (typeof window === 'undefined') return '';
        const s = new URLSearchParams(window.location.search).get('status') ?? new URLSearchParams(window.location.search).get('chart_status');
        return Object.values(RunningChartStatus).includes(s as RunningChartStatus) ? (s as RunningChartStatus) : '';
    });
    const [from, setFrom] = useState(() => {
        if (typeof window === 'undefined') return '';
        return new URLSearchParams(window.location.search).get('from') ?? '';
    });
    const [until, setUntil] = useState(() => {
        if (typeof window === 'undefined') return '';
        return new URLSearchParams(window.location.search).get('until') ?? '';
    });
    const [filters, setFilters] = useState<ChartRegisterFilters>(() => {
        if (typeof window === 'undefined') return {};
        const params = new URLSearchParams(window.location.search);
        const searchParam = params.get('search')?.trim();
        const statusParam = params.get('status') ?? params.get('chart_status');
        const fromParam = params.get('from');
        const untilParam = params.get('until');
        const initial: ChartRegisterFilters = {};
        if (searchParam) initial.search = searchParam;
        if (statusParam && Object.values(RunningChartStatus).includes(statusParam as RunningChartStatus)) {
            initial.chart_status = statusParam as RunningChartStatus;
        }
        if (fromParam) {
            try { initial.from = timestampWithOffset(fromParam); } catch {}
        }
        if (untilParam) {
            try { initial.until = timestampWithOffset(untilParam); } catch {}
        }
        return initial;
    });
    const [page, setPage] = useState(1);
    const [rows, setRows] = useState<ChartRegisterRow[]>([]); const [meta, setMeta] = useState<PaginationMeta>();
    const [loading, setLoading] = useState(true); const [error, setError] = useState<ApiError | null>(null); const [selected, setSelected] = useState<number | null>(null);
    const [history, setHistory] = useState<number | null>(null);
    const [printingId, setPrintingId] = useState<number | null>(null);
    useEffect(() => {
        const controller = new AbortController();
        listChartRegister(filters, page, controller.signal).then(result => {
            if (!controller.signal.aborted) { setRows(result.data); setMeta(result.meta); setError(null); }
        }).catch(failure => { if (!controller.signal.aborted) { setRows([]); setMeta(undefined); setError(toApiError(failure)); } })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [filters, page]);
    function syncUrl(nextSearch: string, nextStatus: string, nextFrom: string, nextUntil: string) {
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        if (nextSearch.trim()) url.searchParams.set('search', nextSearch.trim());
        else url.searchParams.delete('search');
        if (nextStatus) url.searchParams.set('status', nextStatus);
        else url.searchParams.delete('status');
        url.searchParams.delete('chart_status');
        if (nextFrom) url.searchParams.set('from', nextFrom);
        else url.searchParams.delete('from');
        if (nextUntil) url.searchParams.set('until', nextUntil);
        else url.searchParams.delete('until');
        window.history.replaceState({}, '', url.toString());
    }
    function apply(event: FormEvent) {
        event.preventDefault();
        try {
            const next = { search: search.trim() || undefined, chart_status: status || undefined, from: from ? timestampWithOffset(from) : undefined, until: until ? timestampWithOffset(until) : undefined };
            syncUrl(search, status, from, until);
            setLoading(true); setError(null); setPage(1); setSelected(null); setHistory(null); setFilters(next);
        } catch (failure) { setError(toApiError(failure)); }
    }
    function clearFilters() {
        const needsReload = error !== null || page !== 1 || Object.values(filters).some(value => value !== undefined);
        setSearch(''); setStatus(''); setFrom(''); setUntil(''); setSelected(null); setHistory(null); setError(null);
        syncUrl('', '', '', '');
        if (needsReload) { setLoading(true); setFilters({}); setPage(1); }
    }
    const statusOptions = Object.values(RunningChartStatus).map(value => ({ value, label: CHART_LABELS[value] }));
    return <div className="space-y-5">
        <ContentHeader title="Running Chart register" description="Review recorded usage, driver identity, agreement context and correction lineage." />
        <Panel title="Filters"><form aria-label="Filter Running Charts" onSubmit={apply} className="grid gap-3 md:grid-cols-2">
            <Input label="Chart, vehicle, driver, agreement or party" value={search} onChange={event => setSearch(event.target.value)} error={error?.fields.search?.[0]} />
            <Select label="Chart status" placeholder="All states" options={statusOptions} value={status} onChange={event => setStatus(event.target.value as RunningChartStatus | '')} />
            <Input label="Period start (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} max={until || undefined} value={from} onChange={event => setFrom(event.target.value)} error={error?.fields.from?.[0]} />
            <Input label="Period end (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} min={from ? toHourFloor(from) : undefined} value={until} onChange={event => setUntil(event.target.value)} error={error?.fields.until?.[0]} />
            <p className="text-sm text-slate-600 md:col-span-2">Times use {operationalTimeZoneLabel()}. Results include charts overlapping the period and show their full recorded quantities. No quantities are prorated.</p>
            <div className="flex flex-wrap gap-2 md:col-span-2"><Button type="submit" loading={loading}>Apply filters</Button><Button type="button" variant="secondary" disabled={loading} onClick={clearFilters}>Clear filters</Button></div>
        </form></Panel>
        <ErrorAlert error={error} inline />
        {loading ? <LoadingState label="Loading Running Chart register…" /> : error ? null : <>
            {rows.length === 0 && <p className="rounded-xl border border-slate-200 bg-white p-5 text-slate-500 shadow-sm">No Running Charts match these filters.</p>}
            {rows.map(row => <article key={row.id} className="space-y-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3"><h2 className="text-base font-semibold text-slate-900">{row.reference} · {row.vehicle_use.vehicle_label}</h2><StatusBadge status={row.status} /></div>
                <div className="grid grid-cols-1 gap-1 text-sm text-slate-700 sm:grid-cols-2">
                    <p>
                        Customer: {row.customer_agreement.party_name} ·{' '}
                        <Link
                            to={`/vehicle-rental/customer/agreements?search=${encodeURIComponent(row.customer_agreement.reference)}`}
                            className="font-medium text-blue-600 hover:text-blue-800 hover:underline"
                            title="Review customer agreement"
                        >
                            {row.customer_agreement.reference}
                        </Link>
                    </p>
                    <p>
                        {row.owner_agreement ? (
                            <>
                                Owner: {row.owner_agreement.party_name} ·{' '}
                                <Link
                                    to={`/vehicle-rental/owner/agreements?search=${encodeURIComponent(row.owner_agreement.reference)}`}
                                    className="font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                    title="Review owner agreement"
                                >
                                    {row.owner_agreement.reference}
                                </Link>
                            </>
                        ) : (
                            'Company supply'
                        )}
                    </p>
                    <p>Driver: {row.driver ? `${row.driver.name} · ${row.driver.reference}` : 'Not recorded'}</p>
                    <p>{formatBusinessDateTime(row.starts_at)} — {formatBusinessDateTime(row.ends_at)}</p>
                    <p className="sm:col-span-2">Total distance: {row.total_km === null ? 'Not recorded' : <><QuantityDisplay value={row.total_km} /> km</>}</p>
                </div>
                {row.replaces_vehicle && <p className="text-sm font-medium text-amber-700">Replaces vehicle {row.replaces_vehicle}</p>}{row.corrects_chart && <p className="text-sm font-medium text-blue-600">Corrects chart {row.corrects_chart.reference}</p>}{row.corrected_by_chart && <p className="text-sm font-medium text-amber-700">Corrected by chart {row.corrected_by_chart.reference}</p>}
                <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="secondary" aria-expanded={selected === row.id} onClick={() => setSelected(selected === row.id ? null : row.id)}>{selected === row.id ? 'Hide details' : 'Review ' + row.reference}</Button>
                    {row.customer_agreement.id && (
                        <Button
                            type="button"
                            variant="secondary"
                            disabled={printingId !== null}
                            loading={printingId === row.id}
                            onClick={async () => {
                                const s = row.starts_at.slice(0, 10);
                                const e = row.ends_at ? row.ends_at.slice(0, 10) : s;
                                setPrintingId(row.id);
                                setError(null);
                                const preview = window.open('', '_blank');
                                if (preview) {
                                    preview.document.title = 'Preparing running chart…';
                                    preview.document.body.textContent = 'Preparing running chart…';
                                }
                                try {
                                    await exportRunningChartReport({
                                        agreement_id: row.customer_agreement.id!,
                                        start_date: s,
                                        end_date: e,
                                        vehicle_use_id: row.vehicle_use.id,
                                    }, 'print', preview);
                                } catch (err) {
                                    preview?.close();
                                    setError(toApiError(err));
                                } finally {
                                    setPrintingId(null);
                                }
                            }}
                        >
                            Print Sheet
                        </Button>
                    )}
                </div>
                {selected === row.id && <div className="space-y-3 border-t border-slate-200 pt-3">
                    <dl className="grid gap-3 sm:grid-cols-2">
                        {Object.entries(DISTANCE_LABELS).map(([key, label]) => <div key={key} className="rounded-lg bg-slate-50 p-2.5"><dt className="text-sm text-slate-500">{label}</dt><dd className="mt-1 font-medium text-slate-900">{row[key as keyof typeof DISTANCE_LABELS] === null ? 'Not recorded' : <QuantityDisplay value={row[key as keyof typeof DISTANCE_LABELS] as string} />}</dd></div>)}
                        {Object.entries(COUNT_LABELS).map(([key, label]) => <div key={key} className="rounded-lg bg-slate-50 p-2.5"><dt className="text-sm text-slate-500">{label}</dt><dd className="mt-1 font-medium text-slate-900">{row[key as keyof typeof COUNT_LABELS] === null ? 'Not recorded' : <QuantityDisplay value={row[key as keyof typeof COUNT_LABELS] as number} precision={0} />}</dd></div>)}
                    </dl>
                    <div className="grid grid-cols-1 gap-1 text-sm text-slate-700 sm:grid-cols-2">
                        <p>Air conditioning: {row.ac_mode === null ? 'Not recorded' : AC_LABELS[row.ac_mode]}</p>
                        <p>Authoritative driver: {row.driver ? `${row.driver.name} (${row.driver.source === 'employee' ? 'employee' : 'external'}) · ${row.driver.reference}` : 'Not recorded'}</p>
                        <p className="sm:col-span-2">Driver observation: {row.driver_observation ?? 'Not recorded'}</p>
                        <p className="sm:col-span-2">Notes: {row.notes ?? 'Not recorded'}</p>
                    </div>
                    <div className="pt-2"><Button type="button" variant="secondary" aria-expanded={history === row.id} onClick={() => setHistory(history === row.id ? null : row.id)}>{history === row.id ? 'Hide chart history' : 'Chart history'}</Button></div>
                    {history === row.id && <RunningChartHistoryPanel key={row.id} id={row.id} />}<UsageChargePanel chart={row} hasOwner={row.owner_agreement !== null} />
                </div>}
            </article>)}
            <Pagination meta={meta} onPageChange={next => { setLoading(true); setSelected(null); setHistory(null); setPage(next); }} />
        </>}
    </div>;
}
