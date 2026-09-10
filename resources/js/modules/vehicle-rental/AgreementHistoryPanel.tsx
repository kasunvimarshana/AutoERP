import { useEffect, useState } from 'react';
import { apiClient } from '@/shared/api/apiClient';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Pagination } from '@/shared/components/Pagination';
import type { ApiCollection } from '@/shared/types/api';
import type { PaginationMeta } from '@/shared/types/pagination';
import { AGREEMENT_API, AgreementKind, PAGE_SIZE, TERM_LABELS, type TermKey } from './agreements';

interface Revision { version: number; action: string; recorded_at: string; actor: { name: string }; reference: string; party_name: string; currency_code: string; agreed_on: string; executing_on: string | null; starts_on: string; ends_on: string | null; terms: Record<TermKey, string | null>; reason: string | null; }
export function AgreementHistoryPanel({ kind, id }: { kind: AgreementKind; id: number }) {
    const [rows, setRows] = useState<Revision[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [page, setPage] = useState(1);
    const [error, setError] = useState<ApiError | null>(null);
    useEffect(() => {
        const controller = new AbortController();
        apiClient.get<ApiCollection<Revision>>(`${AGREEMENT_API}/${kind}/agreements/${id}/history`, { params: { page, per_page: PAGE_SIZE }, signal: controller.signal })
            .then(response => { if (!controller.signal.aborted) { setRows(response.data.data); setMeta(response.data.meta); setError(null); } })
            .catch(failure => { if (!controller.signal.aborted) setError(toApiError(failure)); });
        return () => controller.abort();
    }, [kind, id, page]);
    return <section aria-label="Agreement history" className="space-y-3 border-t pt-4"><h3 className="font-semibold">Agreement history</h3><ErrorAlert error={error} inline />
        {rows.map(row => <details key={row.version} className="rounded border p-3"><summary className="cursor-pointer">Revision {row.version} · {row.action} · {row.actor.name} · {row.recorded_at}</summary>
            <p>Agreement date: {row.agreed_on} · Executing date: {row.executing_on ?? 'Not recorded'}</p>
            <p className="my-2">{row.reference} · {row.party_name} · {row.currency_code} · {row.starts_on} – {row.ends_on ?? 'Open-ended'}</p>
            {row.reason && <p>{row.reason}</p>}
            <dl className="grid gap-2 sm:grid-cols-2">{(Object.keys(TERM_LABELS) as TermKey[]).map(key => <div key={key}><dt className="text-sm text-slate-500">{TERM_LABELS[key]}</dt><dd>{row.terms[key] ?? 'Not specified'}</dd></div>)}</dl>
        </details>)}<Pagination meta={meta} onPageChange={setPage} /></section>;
}
