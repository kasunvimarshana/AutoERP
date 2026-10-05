import { hasPermission } from '@/modules/auth/accessControl';
import { useEffect, useState, type FormEvent } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Textarea } from '@/shared/components/Textarea';
import { Pagination } from '@/shared/components/Pagination';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { VehicleUseStatus, type VehicleUse } from './vehicleUse';
import { CHART_ACTION_CONFIRM_LABELS, CHART_PERMISSION, RunningChartAction, RunningChartStatus, type RunningChart } from './runningCharts';
import { listCharts, transitionChart } from './runningChartApi';
import { UsageChargePanel } from './UsageChargePanel';
import { RunningChartEditor } from './RunningChartEditor';
import { RunningChartHistoryPanel } from './RunningChartHistoryPanel';
export function RunningChartsPanel({ use }: { use: VehicleUse }) {
    const auth = useAuth(); const canManage = hasPermission(auth, CHART_PERMISSION.manage);
    const [rows, setRows] = useState<RunningChart[]>([]); const [page, setPage] = useState(1); const [meta, setMeta] = useState<PaginationMeta>(); const [revision, setRevision] = useState(0);
    const [error, setError] = useState<ApiError | null>(null); const [loading, setLoading] = useState(true); const [saving, setSaving] = useState(false);
    const [editor, setEditor] = useState<{ chart?: RunningChart; correction?: boolean } | null>(null); const [history, setHistory] = useState<number | null>(null);
    const [action, setAction] = useState<{ chart: RunningChart; type: RunningChartAction } | null>(null); const [reason, setReason] = useState('');
    useEffect(() => { const c = new AbortController(); listCharts(use.id, page, c.signal).then(r => { if (!c.signal.aborted) { setRows(r.data); setMeta(r.meta); setError(null); } }).catch(e => { if (!c.signal.aborted) { setRows([]); setMeta(undefined); setError(toApiError(e)); } }).finally(() => { if (!c.signal.aborted) setLoading(false); }); return () => c.abort(); }, [use.id, page, revision]);
    function reload() { setLoading(true); setEditor(null); setAction(null); setHistory(null); setRevision(v => v + 1); }
    async function submit(event: FormEvent) { event.preventDefault(); if (!action) return; setSaving(true); setError(null); try { await transitionChart(action.chart, action.type, reason); reload(); } catch (failure) { setError(toApiError(failure)); } finally { setSaving(false); } }
    function choose(chart: RunningChart, type: RunningChartAction) { setAction({ chart, type }); setReason(''); setError(null); }
    return <section aria-label="Running Charts" className="space-y-3 border-t pt-3"><div className="flex flex-wrap items-center gap-2"><h4 className="grow font-semibold">Running Charts · {use.vehicle.label}</h4><Button variant="secondary" disabled={saving || !!editor} onClick={reload}>Reload charts</Button>{canManage && [VehicleUseStatus.InCustody, VehicleUseStatus.Returned].includes(use.status) && <Button disabled={saving || !!editor || !!action} onClick={() => setEditor({})}>Record usage</Button>}</div>
        <ErrorAlert error={error} inline />{editor && <RunningChartEditor use={use} chart={editor.chart} correction={editor.correction} onSaved={reload} onCancel={() => setEditor(null)} />}
        {loading ? <LoadingState label="Loading Running Charts…" /> : error ? null : rows.map(chart => <article key={chart.id} className="space-y-2 rounded border p-3"><div className="flex flex-wrap items-center gap-2"><p className="font-medium">{chart.reference}</p><StatusBadge status={chart.status} /></div><p>{formatBusinessDateTime(chart.starts_at)} — {formatBusinessDateTime(chart.ends_at)}</p><p>Total distance: {chart.total_km === null ? 'Not recorded' : <><QuantityDisplay value={chart.total_km} /> km</>}</p>{chart.corrects_chart && <p>Corrects {chart.corrects_chart.reference}</p>}
            <div className="flex flex-wrap gap-2"><Button type="button" variant="secondary" aria-expanded={history === chart.id} onClick={() => setHistory(history === chart.id ? null : chart.id)}>{history === chart.id ? 'Hide chart history' : 'Chart history'}</Button>{!editor && !action && <>
                {canManage && chart.status === RunningChartStatus.Draft && <Button onClick={() => setEditor({ chart })}>Edit draft</Button>}
                {hasPermission(auth, CHART_PERMISSION.finalize) && chart.status === RunningChartStatus.Draft && <Button onClick={() => choose(chart, RunningChartAction.Finalize)}>Finalize usage</Button>}
                {hasPermission(auth, CHART_PERMISSION.reverse) && chart.status === RunningChartStatus.Finalized && <Button variant="danger" onClick={() => choose(chart, RunningChartAction.Reverse)}>Reverse usage</Button>}
                {canManage && chart.status === RunningChartStatus.Reversed && <Button onClick={() => setEditor({ chart, correction: true })}>Create correction</Button>}
            </>}</div>{history === chart.id && <RunningChartHistoryPanel key={chart.id} id={chart.id} />}<UsageChargePanel chart={chart} hasOwner={use.owner_agreement !== null} /></article>)}
        {!loading && !error && !rows.length && <p className="text-sm text-slate-500">No Running Charts recorded.</p>}<Pagination meta={meta} onPageChange={value => { setLoading(true); setPage(value); setAction(null); }} />
        {action && <form onSubmit={submit} aria-label="Confirm chart action" className="space-y-3 rounded border p-3"><p>{action.type === RunningChartAction.Finalize ? 'Finalize physical evidence. This does not create charges.' : 'Reverse physical evidence and retain its original history.'} · {action.chart.reference}</p>{action.type === RunningChartAction.Reverse && <Textarea label="Reversal reason" required value={reason} onChange={e => setReason(e.target.value)} error={error?.fields.reason?.[0]} />}<div className="flex flex-wrap gap-2"><Button type="submit" variant={action.type === RunningChartAction.Reverse ? 'danger' : 'primary'} loading={saving} disabled={action.type === RunningChartAction.Reverse && !reason.trim()}>{CHART_ACTION_CONFIRM_LABELS[action.type]}</Button><Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button></div></form>}
    </section>;
}
