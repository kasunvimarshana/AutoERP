import { useEffect, useState, type FormEvent } from 'react';
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
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { listVehicleUseRegister } from './vehicleUseApi';
import { operationalTimeZoneLabel, OPERATIONAL_TIME_STEP_SECONDS, timestampWithOffset, USE_LABELS, VehicleUseStatus, type VehicleUse, type VehicleUseRegisterFilters } from './vehicleUse';
import { VehicleUseHistoryPanel } from './VehicleUseHistoryPanel';

export default function VehicleUseRegisterPage() {
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<VehicleUseStatus | ''>('');
    const [from, setFrom] = useState(''); const [until, setUntil] = useState('');
    const [filters, setFilters] = useState<VehicleUseRegisterFilters>({}); const [page, setPage] = useState(1);
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
    function apply(event: FormEvent) {
        event.preventDefault();
        try {
            const next = { search: search.trim() || undefined, use_status: status || undefined, from: from ? timestampWithOffset(from) : undefined, until: until ? timestampWithOffset(until) : undefined };
            setLoading(true); setError(null); setPage(1); setSelected(null); setFilters(next);
        } catch (failure) { setError(toApiError(failure)); }
    }
    function clearFilters() {
        const needsReload = error !== null || page !== 1 || Object.values(filters).some(value => value !== undefined);
        setSearch(''); setStatus(''); setFrom(''); setUntil(''); setSelected(null); setError(null);
        if (needsReload) { setLoading(true); setFilters({}); setPage(1); }
    }
    const statusOptions = Object.values(VehicleUseStatus).map(value => ({ value, label: USE_LABELS[value] }));
    return <div className="space-y-5">
        <ContentHeader title="Vehicle Use register" description="Find assignments and review planned periods, actual custody, replacements and history." />
        <Panel title="Filters"><form aria-label="Filter vehicle use" onSubmit={apply} className="grid gap-3 md:grid-cols-2">
            <Input label="Vehicle, agreement or party" value={search} onChange={event => setSearch(event.target.value)} error={error?.fields.search?.[0]} />
            <Select label="Use status" placeholder="All states" options={statusOptions} value={status} onChange={event => setStatus(event.target.value as VehicleUseStatus | '')} />
            <Input label="Planned period start (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} max={until || undefined} value={from} onChange={event => setFrom(event.target.value)} error={error?.fields.from?.[0]} />
            <Input label="Planned period end (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} min={from || undefined} value={until} onChange={event => setUntil(event.target.value)} error={error?.fields.until?.[0]} />
            <p className="text-sm text-slate-600 md:col-span-2">Filter times use {operationalTimeZoneLabel()}. Results overlap the planned period. Actual custody is shown separately; no rental charges are calculated.</p>
            <div className="flex flex-wrap gap-2 md:col-span-2"><Button type="submit" loading={loading}>Apply filters</Button><Button type="button" variant="secondary" disabled={loading} onClick={clearFilters}>Clear filters</Button></div>
        </form></Panel>
        <ErrorAlert error={error} inline />
        {loading ? <LoadingState label="Loading vehicle use register…" /> : error ? null : <>
            {rows.length === 0 && <p>No vehicle uses match these filters.</p>}
            {rows.map(row => <article key={row.id} className="space-y-2 rounded-lg border p-4">
                <div className="flex flex-wrap items-center justify-between gap-2"><h2 className="font-semibold">{row.vehicle.label} · {row.customer_agreement.reference}</h2><StatusBadge status={row.status} label={USE_LABELS[row.status]} /></div>
                <p>Customer: {row.customer_agreement.party_name}</p>
                <p>{row.owner_agreement ? `Owner: ${row.owner_agreement.party_name} · ${row.owner_agreement.reference}` : 'Company supply'}</p>
                <p>Planned: {formatBusinessDateTime(row.starts_at)} — {row.ends_at ? formatBusinessDateTime(row.ends_at) : 'Open-ended'}</p>
                <p>Handed over: {formatBusinessDateTime(row.handed_over_at, 'Not recorded')}</p><p>Returned: {formatBusinessDateTime(row.returned_at, 'Not recorded')}</p>
                {row.replaces_use && <p>Replaces vehicle {row.replaces_use.vehicle_label}</p>}
                <Button type="button" variant="secondary" aria-expanded={selected === row.id} onClick={() => setSelected(selected === row.id ? null : row.id)}>{selected === row.id ? 'Hide details' : 'Review ' + row.vehicle.label}</Button>
                {selected === row.id && <div className="space-y-3 border-t pt-3">
                    <p>Handover odometer: {row.handover_odometer === null ? 'Not recorded' : <QuantityDisplay value={row.handover_odometer} />}</p><p>Return odometer: {row.return_odometer === null ? 'Not recorded' : <QuantityDisplay value={row.return_odometer} />}</p>
                    <p>Notes: {row.notes ?? 'Not recorded'}</p>
                    <VehicleUseHistoryPanel key={row.id} id={row.id} />
                </div>}
            </article>)}
            <Pagination meta={meta} onPageChange={next => { setLoading(true); setSelected(null); setPage(next); }} />
        </>}
    </div>;
}
