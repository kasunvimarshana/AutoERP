import { useState } from 'react';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { humanize } from '@/shared/utils/object';
import { agreementHistory } from './agreementApi';
import { AgreementKind, DriverMode, RentalBasis } from './agreements';
import { AgreementTermsGrid } from './AgreementTermsGrid';

export function AgreementHistoryPanel({ kind, id }: { kind: AgreementKind; id: number }) {
    const [page, setPage] = useState(1);
    const history = useApi(signal => agreementHistory(kind, id, page, signal), [kind, id, page]);

    return (
        <section aria-label="Agreement history" className="space-y-3 border-t pt-4">
            <h3 className="font-semibold">Agreement history</h3>
            <ErrorAlert error={history.error} inline />
            {history.loading ? <LoadingState label="Loading agreement history…" /> : history.data && <>
                {history.data.data.length === 0 && <p className="text-sm text-slate-500">No agreement history has been recorded.</p>}
                {history.data.data.map((row) => (
                    <details key={row.version} className="rounded-lg border border-slate-200 p-3">
                        <summary className="cursor-pointer font-medium">
                            Revision {row.version} · {humanize(row.action)} · {row.actor.name} · {row.recorded_at}
                        </summary>
                        <div className="mt-3 space-y-3">
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="font-medium">{row.reference} · {row.party_name}</p>
                                <StatusBadge status={row.status} />
                            </div>
                            <p className="text-sm text-slate-700">
                                {row.currency_code} · {row.basis === RentalBasis.Daily ? 'Daily' : 'Monthly'} · {row.driver_mode === DriverMode.SelfDrive ? 'Self-drive' : 'With driver'}
                            </p>
                            <p className="text-sm text-slate-700">
                                Agreement date: {row.agreed_on} · Executing date: {row.executing_on ?? 'Not recorded'}
                            </p>
                            <p className="text-sm text-slate-700">
                                Effective period: {row.starts_on} – {row.ends_on ?? 'Open-ended'}
                            </p>
                            {row.reason && <p className="text-sm"><span className="font-medium">Reason:</span> {row.reason}</p>}
                            {row.notes && <p className="text-sm"><span className="font-medium">Notes:</span> {row.notes}</p>}
                            <AgreementTermsGrid kind={kind} currency={row.currency_code} terms={row.terms} />
                        </div>
                    </details>
                ))}
                <Pagination meta={history.data.meta} onPageChange={setPage} />
            </>}
        </section>
    );
}
