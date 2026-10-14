import { useState } from 'react';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';
import { humanize } from '@/shared/utils/object';
import { useApi } from '@/shared/hooks/useApi';
import { incidentHistory } from './incidentEvidenceApi';

export function IncidentHistoryPanel({ id }: { id: number }) {
    const [page, setPage] = useState(1);
    const history = useApi(signal => incidentHistory(id, page, signal), [id, page]);
    return <section aria-label="Rental incident history" className="space-y-3">
        <ErrorAlert error={history.error} inline />
        {history.loading ? <LoadingState label="Loading incident history…" /> : history.data && <>
            {history.data.data.length === 0 && <p className="text-sm">No incident history recorded.</p>}
            {history.data.data.map((event, index) => <article key={event.recorded_at + String(index)} className="rounded-lg border p-3">
                <p className="font-medium">{humanize(event.action)}</p>
                <p className="text-sm text-slate-600">{event.actor.name} · {formatBusinessDateTime(event.recorded_at)}</p>
                {event.reason && <p className="text-sm">Reason: {event.reason}</p>}
            </article>)}
            <Pagination meta={history.data.meta} onPageChange={setPage} />
        </>}
    </section>;
}
