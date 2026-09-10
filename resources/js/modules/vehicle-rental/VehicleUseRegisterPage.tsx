import { useEffect, useState, type FormEvent } from 'react';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Pagination } from '@/shared/components/Pagination';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { listVehicleUseRegister } from './vehicleUseApi';
import { operationalTimeZone, OPERATIONAL_TIME_STEP_SECONDS, timestampWithOffset, USE_LABELS, VehicleUseStatus, type VehicleUse, type VehicleUseRegisterFilters } from './vehicleUse';
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
        }).catch(failure => { if (!controller.signal.aborted) setError(toApiError(failure)); })
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
    return <main className="space-y-5 p-4">
        <header><h1 className="text-2xl font-semibold">Vehicle Use register</h1><p>Find assignments and review custody and replacement history.</p></header>
        <form aria-label="Filter vehicle use" onSubmit={apply} className="grid gap-3 rounded-lg border p-4 md:grid-cols-2">
            <Input label="Vehicle, agreement or party" value={search} onChange={event => setSearch(event.target.value)} error={error?.fields.search?.[0]} />
            <label className="block">Use status<select className="block w-full rounded border p-2" value={status} onChange={event => setStatus(event.target.value as VehicleUseStatus | '')}><option value="">All states</option>{Object.values(VehicleUseStatus).map(value => <option key={value} value={value}>{USE_LABELS[value]}</option>)}</select></label>
            <Input label="Planned period start (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} value={from} onChange={event => setFrom(event.target.value)} error={error?.fields.from?.[0]} />
            <Input label="Planned period end (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} value={until} onChange={event => setUntil(event.target.value)} error={error?.fields.until?.[0]} />
            <p className="text-sm md:col-span-2">Filter times use {operationalTimeZone}. Results overlap the planned period. Actual custody is shown separately; no rental charges are calculated.</p>
            <Button type="submit" loading={loading}>Apply filters</Button>
        </form>
        <ErrorAlert error={error} inline />
        {loading ? <p role="status">Loading vehicle use register…</p> : error ? null : <>
            {rows.length === 0 && <p>No vehicle uses match these filters.</p>}
            {rows.map(row => <article key={row.id} className="space-y-2 rounded-lg border p-4">
                <div className="flex flex-wrap justify-between gap-2"><h2 className="font-semibold">{row.vehicle.label} · {row.customer_agreement.reference}</h2><span>{USE_LABELS[row.status]}</span></div>
                <p>Customer: {row.customer_agreement.party_name}</p>
                <p>{row.owner_agreement ? `Owner: ${row.owner_agreement.party_name} · ${row.owner_agreement.reference}` : 'Company supply'}</p>
                <p>Planned: {row.starts_at} — {row.ends_at ?? 'Open-ended'}</p>
                <p>Handed over: {row.handed_over_at ?? 'Not recorded'}</p><p>Returned: {row.returned_at ?? 'Not recorded'}</p>
                {row.replaces_use && <p>Replaces vehicle {row.replaces_use.vehicle_label}</p>}
                <Button variant="secondary" aria-expanded={selected === row.id} onClick={() => setSelected(selected === row.id ? null : row.id)}>Review {row.vehicle.label}</Button>
                {selected === row.id && <div className="space-y-3 border-t pt-3">
                    <p>Handover odometer: {row.handover_odometer ?? 'Not recorded'}</p><p>Return odometer: {row.return_odometer ?? 'Not recorded'}</p>
                    <p>Notes: {row.notes ?? 'Not recorded'}</p>
                    <VehicleUseHistoryPanel key={row.id} id={row.id} />
                </div>}
            </article>)}
            <Pagination meta={meta} onPageChange={next => { setLoading(true); setSelected(null); setPage(next); }} />
        </>}
    </main>;
}
