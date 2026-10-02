import { useState } from 'react';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { useApi } from '@/shared/hooks/useApi';
import { humanize } from '@/shared/utils/object';
import { chartHistory } from './runningChartApi';
import { AC_LABELS, COUNT_LABELS, DISTANCE_LABELS, DriverIdentitySource } from './runningCharts';

export function RunningChartHistoryPanel({ id }: { id: number }) {
    const [page, setPage] = useState(1);
    const history = useApi(signal => chartHistory(id, page, signal), [id, page]);

    return (
        <section aria-label="Running Chart history" className="space-y-3">
            <ErrorAlert error={history.error} inline />
            {history.loading ? <LoadingState label="Loading Running Chart history…" /> : history.data && <>
                {history.data.data.length === 0 && <p className="text-sm text-slate-500">No Running Chart history has been recorded.</p>}
                {history.data.data.map((row) => (
                    <article key={row.version} className="space-y-3 rounded-lg border border-slate-200 p-3">
                        <div>
                            <p className="font-medium">Revision {row.version} · {humanize(row.action)}</p>
                            <p className="text-sm text-slate-600">{row.actor.name} · {row.recorded_at}</p>
                        </div>
                        {row.reason && <p><span className="font-medium">Reason:</span> {row.reason}</p>}
                        <p>{row.facts.reference} · {row.facts.starts_at} — {row.facts.ends_at}</p>
                        <dl className="grid gap-3 sm:grid-cols-2">
                            {Object.entries(DISTANCE_LABELS).map(([key, label]) => {
                                const value = row.facts[key as keyof typeof DISTANCE_LABELS];
                                return <div key={key}><dt className="text-sm text-slate-500">{label}</dt><dd>{value === null ? 'Not recorded' : <QuantityDisplay value={value as string} />}</dd></div>;
                            })}
                            {Object.entries(COUNT_LABELS).map(([key, label]) => {
                                const value = row.facts[key as keyof typeof COUNT_LABELS];
                                return <div key={key}><dt className="text-sm text-slate-500">{label}</dt><dd>{value === null ? 'Not recorded' : <QuantityDisplay value={value as number} precision={0} />}</dd></div>;
                            })}
                        </dl>
                        <p>Air conditioning: {row.facts.ac_mode === null ? 'Not recorded' : AC_LABELS[row.facts.ac_mode]}</p>
                        <p>Driver identity: {row.facts.driver_identity_source === null || row.facts.driver_identity_source === undefined
                            ? 'Not recorded'
                            : row.facts.driver_identity_source === DriverIdentitySource.Employee
                                ? `Employee driver · ${row.facts.driver_name_snapshot ?? 'Name not recorded'} · ${row.facts.driver_reference_snapshot ?? 'Reference not recorded'}`
                                : `External driver · ${row.facts.driver_name_snapshot ?? 'Name not recorded'} · ${row.facts.driver_reference_snapshot ?? 'Reference not recorded'}`}</p>
                        <p>Driver observation: {row.facts.driver_observation ?? 'Not recorded'}</p>
                        <p>Notes: {row.facts.notes ?? 'Not recorded'}</p>
                    </article>
                ))}
                <Pagination meta={history.data.meta} onPageChange={setPage} />
            </>}
        </section>
    );
}
