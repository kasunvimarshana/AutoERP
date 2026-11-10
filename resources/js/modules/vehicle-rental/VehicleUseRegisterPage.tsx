import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { ApiError, toApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { listVehicleUseRegister } from './vehicleUseApi';
import { operationalTimeZoneLabel, OPERATIONAL_TIME_STEP_SECONDS, timestampWithOffset, USE_LABELS, VehicleUseStatus, type VehicleUse, type VehicleUseRegisterFilters } from './vehicleUse';
import { VehicleUseHistoryPanel } from './VehicleUseHistoryPanel';

export default function VehicleUseRegisterPage() {
    const searchParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : new URLSearchParams();
    const initialSearch = searchParams.get('search') ?? '';
    const initialStatusParam = searchParams.get('status') ?? searchParams.get('use_status') ?? '';
    const initialStatus = Object.values(VehicleUseStatus).includes(initialStatusParam as VehicleUseStatus)
        ? (initialStatusParam as VehicleUseStatus)
        : '';
    const initialFrom = searchParams.get('from') ?? '';
    const initialUntil = searchParams.get('until') ?? '';
    let initialFromOffset: string | undefined;
    let initialUntilOffset: string | undefined;
    if (initialFrom) {
        try { initialFromOffset = timestampWithOffset(initialFrom); } catch {}
    }
    if (initialUntil) {
        try { initialUntilOffset = timestampWithOffset(initialUntil); } catch {}
    }
    const [search, setSearch] = useState(initialSearch);
    const [status, setStatus] = useState<VehicleUseStatus | ''>(initialStatus);
    const [from, setFrom] = useState(initialFrom);
    const [until, setUntil] = useState(initialUntil);
    const [filters, setFilters] = useState<VehicleUseRegisterFilters>({
        ...(initialSearch ? { search: initialSearch } : {}),
        ...(initialStatus ? { use_status: initialStatus } : {}),
        ...(initialFromOffset ? { from: initialFromOffset } : {}),
        ...(initialUntilOffset ? { until: initialUntilOffset } : {}),
    }); const [page, setPage] = useState(1);
    const [rows, setRows] = useState<VehicleUse[]>([]); const [meta, setMeta] = useState<PaginationMeta>();
    const [loading, setLoading] = useState(true); const [error, setError] = useState<ApiError | null>(null);
    const [selected, setSelected] = useState<number | null>(null);
    useEffect(() => {
        const controller = new AbortController();
        listVehicleUseRegister(filters, page, controller.signal).then(result => {
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
        url.searchParams.delete('use_status');
        if (nextFrom) url.searchParams.set('from', nextFrom);
        else url.searchParams.delete('from');
        if (nextUntil) url.searchParams.set('until', nextUntil);
        else url.searchParams.delete('until');
        window.history.replaceState({}, '', url.toString());
    }
    function apply(event: FormEvent) {
        event.preventDefault();
        if (from && until && until <= from) {
            setError(new ApiError(
                'Planned period end must follow period start.',
                422,
                'VALIDATION_FAILED',
                'validation_error',
                { until: ['Planned period end must follow period start.'] },
            ));
            return;
        }
        try {
            const next = { search: search.trim() || undefined, use_status: status || undefined, from: from ? timestampWithOffset(from) : undefined, until: until ? timestampWithOffset(until) : undefined };
            syncUrl(search, status, from, until);
            setLoading(true); setError(null); setPage(1); setSelected(null); setFilters(next);
        } catch (failure) { setError(toApiError(failure)); }
    }
    function clearFilters() {
        const needsReload = error !== null || page !== 1 || Object.values(filters).some(value => value !== undefined);
        setSearch(''); setStatus(''); setFrom(''); setUntil(''); setSelected(null); setError(null);
        syncUrl('', '', '', '');
        if (needsReload) { setLoading(true); setFilters({}); setPage(1); }
    }
    const statusOptions = Object.values(VehicleUseStatus).map(value => ({ value, label: USE_LABELS[value] }));
    return <div className="space-y-5">
        <ContentHeader title="Vehicle Use register" description="Find assignments and review planned periods, actual custody, replacements and history." />
        <Panel title="Filters"><form aria-label="Filter vehicle use" onSubmit={apply} className="grid gap-3 md:grid-cols-2">
            <Input label="Vehicle, agreement or party" value={search} onChange={event => setSearch(event.target.value)} error={error?.fields.search?.[0]} />
            <Select label="Use status" placeholder="All states" options={statusOptions} value={status} onChange={event => setStatus(event.target.value as VehicleUseStatus | '')} />
            <Input label="Planned period start (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} value={from} onChange={event => setFrom(event.target.value)} error={error?.fields.from?.[0]} />
            <Input label="Planned period end (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} value={until} onChange={event => setUntil(event.target.value)} error={error?.fields.until?.[0]} />
            <p className="text-sm text-slate-600 md:col-span-2">Filter times use {operationalTimeZoneLabel()}. Results overlap the planned period. Actual custody is shown separately; no rental charges are calculated.</p>
            <div className="flex flex-wrap gap-2 md:col-span-2"><Button type="submit" loading={loading}>Apply filters</Button><Button type="button" variant="secondary" disabled={loading} onClick={clearFilters}>Clear filters</Button></div>
        </form></Panel>
        <ErrorAlert error={error} inline />
        {loading ? <LoadingState label="Loading vehicle use register…" /> : error ? null : <>
            {rows.length === 0 && <p className="rounded-xl border border-slate-200 bg-white p-5 text-slate-500 shadow-sm">No vehicle uses match these filters.</p>}
            {rows.map(row => <article key={row.id} className="space-y-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                    <h2 className="text-base font-semibold text-slate-900">
                        {row.vehicle.label} ·{' '}
                        <Link
                            to={`/vehicle-rental/customer/agreements?search=${encodeURIComponent(row.customer_agreement.reference)}`}
                            className="text-blue-600 hover:text-blue-800 hover:underline"
                            title="Review customer agreement"
                        >
                            {row.customer_agreement.reference}
                        </Link>
                    </h2>
                    <StatusBadge status={row.status} label={USE_LABELS[row.status]} />
                    {row.customer_agreement.driver_mode && (
                        <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 border border-slate-200">
                            {row.customer_agreement.driver_mode === 'self_drive' ? 'Self-drive' : 'With driver'}
                        </span>
                    )}
                </div>
                <div className="grid grid-cols-1 gap-1 text-sm text-slate-700 sm:grid-cols-2">
                    <p>Customer: {row.customer_agreement.party_name}</p>
                    <p>
                        {row.owner_agreement ? (
                            <>
                                Owner: {row.owner_agreement.party_name} ·{' '}
                                <Link
                                    to={`/vehicle-rental/owner/agreements?search=${encodeURIComponent(row.owner_agreement.reference)}`}
                                    className="text-blue-600 hover:text-blue-800 hover:underline"
                                    title="Review owner agreement"
                                >
                                    {row.owner_agreement.reference}
                                </Link>
                            </>
                        ) : (
                            'Company supply'
                        )}
                    </p>
                    <p>Planned: {formatBusinessDateTime(row.starts_at)} — {row.ends_at ? formatBusinessDateTime(row.ends_at) : 'Open-ended'}</p>
                    <p>Handed over: {formatBusinessDateTime(row.handed_over_at, 'Not recorded')}</p>
                    <p>Returned: {formatBusinessDateTime(row.returned_at, 'Not recorded')}</p>
                </div>
                {row.replaces_use && <p className="text-sm font-medium text-amber-700">Replaces vehicle {row.replaces_use.vehicle_label}</p>}
                {row.replaced_by_use && <p className="text-sm font-medium text-purple-700">Replaced by vehicle {row.replaced_by_use.vehicle_label}</p>}
                <div><Button type="button" variant="secondary" aria-expanded={selected === row.id} onClick={() => setSelected(selected === row.id ? null : row.id)}>{selected === row.id ? 'Hide details' : 'Review ' + row.vehicle.label}</Button></div>
                {selected === row.id && <div className="space-y-3 border-t border-slate-200 pt-3">
                    <p>Handover odometer: {row.handover_odometer === null ? 'Not recorded' : <QuantityDisplay value={row.handover_odometer} />}</p>
                    <p>Return odometer: {row.return_odometer === null ? 'Not recorded' : <QuantityDisplay value={row.return_odometer} />}</p>
                    <p>Notes: {row.notes ?? 'Not recorded'}</p>
                    <VehicleUseHistoryPanel key={row.id} id={row.id} />
                </div>}
            </article>)}
            <Pagination meta={meta} onPageChange={next => { setLoading(true); setSelected(null); setPage(next); }} />
        </>}
    </div>;
}
