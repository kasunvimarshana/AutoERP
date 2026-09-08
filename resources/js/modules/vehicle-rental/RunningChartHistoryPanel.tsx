import { useEffect, useState } from 'react';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Pagination } from '@/shared/components/Pagination';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { chartHistory } from './runningChartApi';
import type { ChartHistory } from './runningCharts';
export function RunningChartHistoryPanel({ id }: { id: number }) {
    const [rows, setRows] = useState<ChartHistory[]>([]); const [page, setPage] = useState(1); const [meta, setMeta] = useState<PaginationMeta>(); const [error, setError] = useState<ApiError | null>(null);
    useEffect(() => { const c = new AbortController(); chartHistory(id, page, c.signal).then(r => { if (!c.signal.aborted) { setRows(r.data); setMeta(r.meta); setError(null); } }).catch(e => { if (!c.signal.aborted) setError(toApiError(e)); }); return () => c.abort(); }, [id, page]);
    return <section aria-label="Running Chart history"><ErrorAlert error={error} inline />{rows.map(row => <article key={row.version} className="border-t py-2"><p>Revision {row.version} · {row.action} · {row.actor.name} · {row.recorded_at}</p><p>{row.reason}</p><p>{row.facts.reference}: {row.facts.starts_at} — {row.facts.ends_at}</p><p>Odometer: {row.facts.start_odometer ?? 'Not recorded'} — {row.facts.end_odometer ?? 'Not recorded'}</p></article>)}<Pagination meta={meta} onPageChange={setPage} /></section>;
}
