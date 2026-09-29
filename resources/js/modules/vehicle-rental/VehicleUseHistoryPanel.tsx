import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Pagination } from '@/shared/components/Pagination';
import type { PaginationMeta } from '@/shared/types/pagination';
import { vehicleUseHistory } from './vehicleUseApi';
import type { VehicleUseHistory } from './vehicleUse';
export function VehicleUseHistoryPanel({ id }: { id: number }) {
    const [page, setPage] = useState(1); const [rows, setRows] = useState<VehicleUseHistory[]>([]); const [meta, setMeta] = useState<PaginationMeta>(); const [error, setError] = useState<ApiError | null>(null);
    useEffect(() => { const c = new AbortController(); vehicleUseHistory(id, page, c.signal).then(r => { if (!c.signal.aborted) { setRows(r.data); setMeta(r.meta); setError(null); } }).catch(e => { if (!c.signal.aborted) setError(toApiError(e)); }); return () => c.abort(); }, [id, page]);
    return <section aria-label="Vehicle-use history"><ErrorAlert error={error} inline />{rows.map(row => <article key={row.version} className="my-3 rounded border p-3"><p>{row.actor.name} · {row.action} · {row.recorded_at}</p><p>{row.vehicle_label} · {row.reason}</p><p>{row.starts_at} — {row.ends_at}</p>{row.handed_over_at && <p>Handed over: {row.handed_over_at} · Odometer: {row.handover_odometer ?? 'Not recorded'}</p>}{row.returned_at && <p>Returned: {row.returned_at} · Odometer: {row.return_odometer ?? 'Not recorded'}</p>}</article>)}<Pagination meta={meta} onPageChange={setPage} /></section>;
}
