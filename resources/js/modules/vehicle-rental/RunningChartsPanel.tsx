import { hasPermission } from '@/modules/auth/accessControl';
import { useEffect, useState, type FormEvent } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Pagination } from '@/shared/components/Pagination';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { VehicleUseStatus, type VehicleUse } from './vehicleUse';
import { CHART_LABELS, CHART_PERMISSION, RunningChartAction, RunningChartStatus, type RunningChart } from './runningCharts';
import { listCharts, transitionChart } from './runningChartApi';
import { RunningChartEditor } from './RunningChartEditor';
import { RunningChartHistoryPanel } from './RunningChartHistoryPanel';
export function RunningChartsPanel({ use }: { use: VehicleUse }) {
    const auth = useAuth(); const canManage = hasPermission(auth, CHART_PERMISSION.manage);
    const [rows, setRows] = useState<RunningChart[]>([]); const [page, setPage] = useState(1); const [meta, setMeta] = useState<PaginationMeta>(); const [revision, setRevision] = useState(0);
    const [error, setError] = useState<ApiError | null>(null); const [loading, setLoading] = useState(true); const [saving, setSaving] = useState(false);
    const [editor, setEditor] = useState<{ chart?: RunningChart; correction?: boolean } | null>(null); const [history, setHistory] = useState<number | null>(null);
    const [action, setAction] = useState<{ chart: RunningChart; type: RunningChartAction } | null>(null); const [reason, setReason] = useState('');
    useEffect(() => { const c = new AbortController(); listCharts(use.id, page, c.signal).then(r => { if (!c.signal.aborted) { setRows(r.data); setMeta(r.meta); setError(null); } }).catch(e => { if (!c.signal.aborted) setError(toApiError(e)); }).finally(() => { if (!c.signal.aborted) setLoading(false); }); return () => c.abort(); }, [use.id, page, revision]);
    function reload() { setLoading(true); setEditor(null); setAction(null); setHistory(null); setRevision(v => v + 1); }
    async function submit(event: FormEvent) { event.preventDefault(); if (!action) return; setSaving(true); setError(null); try { await transitionChart(action.chart, action.type, reason); reload(); } catch (failure) { setError(toApiError(failure)); } finally { setSaving(false); } }
    function choose(chart: RunningChart, type: RunningChartAction) { setAction({ chart, type }); setReason(''); setError(null); }
    return <section aria-label="Running Charts" className="space-y-3 border-t pt-3"><div className="flex gap-2"><h4 className="grow font-semibold">Running Charts · {use.vehicle.label}</h4><Button variant="secondary" disabled={saving || !!editor} onClick={reload}>Reload charts</Button>{canManage && [VehicleUseStatus.InCustody, VehicleUseStatus.Returned].includes(use.status) && <Button disabled={saving || !!editor || !!action} onClick={() => setEditor({})}>Record usage</Button>}</div>
        <ErrorAlert error={error} inline />{editor && <RunningChartEditor use={use} chart={editor.chart} correction={editor.correction} onSaved={reload} onCancel={() => setEditor(null)} />}
        {loading ? <p role="status">Loading Running Charts…</p> : rows.map(chart => <article key={chart.id} className="space-y-2 rounded border p-3"><p>{chart.reference} · {CHART_LABELS[chart.status]}</p><p>{chart.starts_at} — {chart.ends_at}</p><p>Total distance: {chart.total_km ?? 'Not recorded'} km</p>{chart.corrects_chart && <p>Corrects {chart.corrects_chart.reference}</p>}
            <div className="flex flex-wrap gap-2"><Button variant="secondary" onClick={() => setHistory(history === chart.id ? null : chart.id)}>Chart history</Button>{!editor && !action && <>
                {canManage && chart.status === RunningChartStatus.Draft && <Button onClick={() => setEditor({ chart })}>Edit draft</Button>}
                {hasPermission(auth, CHART_PERMISSION.finalize) && chart.status === RunningChartStatus.Draft && <Button onClick={() => choose(chart, RunningChartAction.Finalize)}>Finalize usage</Button>}
                {hasPermission(auth, CHART_PERMISSION.reverse) && chart.status === RunningChartStatus.Finalized && <Button onClick={() => choose(chart, RunningChartAction.Reverse)}>Reverse usage</Button>}
                {canManage && chart.status === RunningChartStatus.Reversed && <Button onClick={() => setEditor({ chart, correction: true })}>Create correction</Button>}
            </>}</div>{history === chart.id && <RunningChartHistoryPanel key={chart.id} id={chart.id} />}</article>)}
        {!loading && !rows.length && <p>No Running Charts recorded.</p>}<Pagination meta={meta} onPageChange={value => { setLoading(true); setPage(value); setAction(null); }} />
        {action && <form onSubmit={submit} aria-label="Confirm chart action" className="space-y-3 rounded border p-3"><p>{action.type === RunningChartAction.Finalize ? 'Finalize physical evidence. This does not create charges.' : 'Reverse physical evidence and retain its original history.'} · {action.chart.reference}</p>{action.type === RunningChartAction.Reverse && <Input label="Reversal reason" required value={reason} onChange={e => setReason(e.target.value)} error={error?.fields.reason?.[0]} />}<Button type="submit" loading={saving} disabled={action.type === RunningChartAction.Reverse && !reason.trim()}>Confirm</Button> <Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button></form>}
    </section>;
}
