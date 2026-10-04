import { useState } from 'react';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { humanize } from '@/shared/utils/object';
import { vehicleUseHistory } from './vehicleUseApi';
import { formatOperationalDateTime, USE_LABELS } from './vehicleUse';

export function VehicleUseHistoryPanel({ id }: { id: number }) {
    const [page, setPage] = useState(1);
    const history = useApi(signal => vehicleUseHistory(id, page, signal), [id, page]);

    return (
        <section aria-label="Vehicle-use history" className="space-y-3">
            <ErrorAlert error={history.error} inline />
            {history.loading ? <LoadingState label="Loading vehicle-use history…" /> : history.data && <>
                {history.data.data.length === 0 && <p className="text-sm text-slate-500">No vehicle-use history has been recorded.</p>}
                {history.data.data.map((row) => (
                    <article key={row.version} className="space-y-2 rounded-lg border border-slate-200 p-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <p className="font-medium">Revision {row.version} · {humanize(row.action)}</p>
                            <StatusBadge status={row.status} label={USE_LABELS[row.status]} />
                        </div>
                        <p className="text-sm text-slate-600">{row.actor.name} · {formatOperationalDateTime(row.recorded_at)}</p>
                        <p>{row.vehicle_label} · {formatOperationalDateTime(row.starts_at)} — {formatOperationalDateTime(row.ends_at, 'Open-ended')}</p>
                        {row.reason && <p><span className="font-medium">Reason:</span> {row.reason}</p>}
                        {row.handed_over_at && <p>Handed over: {formatOperationalDateTime(row.handed_over_at)} · Odometer: {row.handover_odometer === null ? 'Not recorded' : <QuantityDisplay value={row.handover_odometer} />}</p>}
                        {row.returned_at && <p>Returned: {formatOperationalDateTime(row.returned_at)} · Odometer: {row.return_odometer === null ? 'Not recorded' : <QuantityDisplay value={row.return_odometer} />}</p>}
                    </article>
                ))}
                <Pagination meta={history.data.meta} onPageChange={setPage} />
            </>}
        </section>
    );
}
