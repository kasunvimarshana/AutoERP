import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { CHART_PERMISSION } from './runningCharts';
import { RunningChartsPanel } from './RunningChartsPanel';
import { useEffect, useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Pagination } from '@/shared/components/Pagination';
import type { PaginationMeta } from '@/shared/types/pagination';
import { AgreementStatus, type Agreement } from './agreements';
import { VehicleUseEditor } from './VehicleUseEditor';
import { VehicleUseHistoryPanel } from './VehicleUseHistoryPanel';
import { listVehicleUses, transitionVehicleUse } from './vehicleUseApi';
import { operationalTimeZone, timestampWithOffset, VehicleUseStatus, VehicleUseAction, USE_LABELS, USE_ACTION_LABELS, type VehicleUse } from './vehicleUse';
export function VehicleUsePanel({ agreement, canManage }: { agreement: Agreement; canManage: boolean }) {
    const auth = useAuth(); const [charts, setCharts] = useState<number | null>(null);
    const [replacement, setReplacement] = useState<VehicleUse | null>(null);
    const [rows, setRows] = useState<VehicleUse[]>([]); const [page, setPage] = useState(1); const [meta, setMeta] = useState<PaginationMeta>(); const [revision, setRevision] = useState(0);
    const [error, setError] = useState<ApiError | null>(null); const [loading, setLoading] = useState(true); const [adding, setAdding] = useState(false);
    const [action, setAction] = useState<{ row: VehicleUse; type: VehicleUseAction } | null>(null); const [at, setAt] = useState(''); const [odometer, setOdometer] = useState(''); const [reason, setReason] = useState(''); const [saving, setSaving] = useState(false); const [history, setHistory] = useState<number | null>(null);
    useEffect(() => { const c = new AbortController(); listVehicleUses(agreement.id, page, c.signal).then(r => { if (!c.signal.aborted) { setRows(r.data); setMeta(r.meta); setError(null); } }).catch(e => { if (!c.signal.aborted) setError(toApiError(e)); }).finally(() => { if (!c.signal.aborted) setLoading(false); }); return () => c.abort(); }, [agreement.id, page, revision]);
    function reload() { setReplacement(null); setLoading(true); setAdding(false); setAction(null); setHistory(null); setRevision(v => v + 1); }
    function choose(row: VehicleUse, type: VehicleUseAction) { setAction({ row, type }); setAt(''); setOdometer(''); setReason(''); setError(null); }
    async function submit(event: FormEvent) {
        event.preventDefault(); if (!action) return; setSaving(true); setError(null);
        try { await transitionVehicleUse(action.row, action.type, { reason, ...(action.type === VehicleUseAction.Cancel ? {} : { occurred_at: timestampWithOffset(at), odometer: odometer || null }) }); reload(); }
        catch (failure) { setError(toApiError(failure)); } finally { setSaving(false); }
    }
    return <section aria-label="Assigned vehicles" className="space-y-4 border-t pt-4">
        <div className="flex gap-2"><h3 className="grow text-lg font-semibold">Assigned vehicles</h3><Button variant="secondary" disabled={saving || adding || !!replacement} onClick={reload}>Reload vehicles</Button>{canManage && agreement.status === AgreementStatus.Active && <Button disabled={saving || adding || !!replacement || action !== null} onClick={() => setAdding(true)}>Assign vehicle</Button>}</div>
        <ErrorAlert error={error} inline />
        {(adding || replacement) && <VehicleUseEditor replacement={replacement ?? undefined} agreement={agreement} onSaved={reload} onCancel={() => { setAdding(false); setReplacement(null); }} />}
        {loading ? <p role="status">Loading vehicle use…</p> : rows.map(row => <article key={row.id} className="space-y-2 rounded-lg border p-4">
            <p className="font-medium">{row.vehicle.label} · {USE_LABELS[row.status]}</p><p>{row.starts_at} — {row.ends_at ?? 'Open-ended'}</p>{row.replaces_use && <p>Replaces {row.replaces_use.vehicle_label}</p>}<p>{row.owner_agreement ? `Owner: ${row.owner_agreement.party_name} · ${row.owner_agreement.reference}` : 'Company supply'}</p>
            {row.handed_over_at && <p>Actual handover: {row.handed_over_at} · Odometer: {row.handover_odometer ?? 'Not recorded'}</p>}{row.returned_at && <p>Actual return: {row.returned_at} · Odometer: {row.return_odometer ?? 'Not recorded'}</p>}
            <div className="flex flex-wrap gap-2"><Button variant="secondary" disabled={saving} onClick={() => setHistory(history === row.id ? null : row.id)}>Vehicle-use history</Button>{canManage && !adding && !replacement && !action && row.status === VehicleUseStatus.Planned && <><Button onClick={() => choose(row, VehicleUseAction.Handover)}>Hand over vehicle</Button><Button variant="secondary" onClick={() => choose(row, VehicleUseAction.Cancel)}>Cancel plan</Button></>}{canManage && !adding && !replacement && !action && row.status === VehicleUseStatus.InCustody && <><Button onClick={() => choose(row, VehicleUseAction.Return)}>Record return</Button><Button variant="secondary" onClick={() => setReplacement(row)}>Replace vehicle</Button></>}</div>
            {hasPermission(auth, CHART_PERMISSION.view) && <Button variant="secondary" onClick={() => setCharts(charts === row.id ? null : row.id)}>Running Charts</Button>}{charts === row.id && <RunningChartsPanel key={row.id} use={row} />}
            {history === row.id && <VehicleUseHistoryPanel key={row.id} id={row.id} />}
        </article>)}
        {!loading && rows.length === 0 && <p>No vehicles assigned.</p>}<Pagination meta={meta} onPageChange={value => { setPage(value); setAction(null); }} />
        {action && <form onSubmit={submit} className="space-y-3 rounded-lg border p-4" aria-label={USE_ACTION_LABELS[action.type]}>
            <h3 className="font-semibold">{USE_ACTION_LABELS[action.type]} · {action.row.vehicle.label}</h3>
            <p className="text-sm">{action.type === VehicleUseAction.Cancel ? 'Cancel the planned use and retain its history.' : `Record the actual event in ${operationalTimeZone}. An expected return alone never releases a vehicle.`}</p>
            <fieldset disabled={saving} className="space-y-3">{action.type !== VehicleUseAction.Cancel && <><Input label="Actual event time" type="datetime-local" value={at} onChange={e => setAt(e.target.value)} required error={error?.fields.occurred_at?.[0]} /><Input label="Odometer (optional)" value={odometer} onChange={e => setOdometer(e.target.value)} inputMode="decimal" error={error?.fields.odometer?.[0]} /></>}
                <Input label="Action reason" value={reason} onChange={e => setReason(e.target.value)} required error={error?.fields.reason?.[0]} />
            </fieldset><Button type="submit" loading={saving} disabled={!reason.trim()}>Confirm action</Button> <Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button>
        </form>}
    </section>;
}
