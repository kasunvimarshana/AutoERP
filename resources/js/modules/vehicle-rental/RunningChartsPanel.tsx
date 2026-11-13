import { hasPermission } from '@/modules/auth/accessControl';
import { useEffect, useState, type FormEvent } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Textarea } from '@/shared/components/Textarea';
import { Pagination } from '@/shared/components/Pagination';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { ApiError, toApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { VehicleUseStatus, localTimestampValue, type VehicleUse } from './vehicleUse';
import { CHART_ACTION_CONFIRM_LABELS, CHART_PERMISSION, DriverIdentitySource, RunningChartAction, RunningChartStatus, type RunningChart } from './runningCharts';
import { exportRunningChartReport, listCharts, transitionChart } from './runningChartApi';
import { UsageChargePanel } from './UsageChargePanel';
import { RunningChartEditor } from './RunningChartEditor';
import { RunningChartHistoryPanel } from './RunningChartHistoryPanel';
import { ReassignDriverModal } from './ReassignDriverModal';
export function RunningChartsPanel({ use }: { use: VehicleUse }) {
    const auth = useAuth(); const canManage = hasPermission(auth, CHART_PERMISSION.manage);
    const [rows, setRows] = useState<RunningChart[]>([]); const [page, setPage] = useState(1); const [meta, setMeta] = useState<PaginationMeta>(); const [revision, setRevision] = useState(0);
    const [error, setError] = useState<ApiError | null>(null); const [loading, setLoading] = useState(true); const [saving, setSaving] = useState(false);
    const [printing, setPrinting] = useState<'lessee' | 'owner' | null>(null);
    const [editor, setEditor] = useState<{ chart?: RunningChart; correction?: boolean } | null>(null); const [history, setHistory] = useState<number | null>(null);
    const [action, setAction] = useState<{ chart: RunningChart; type: RunningChartAction } | null>(null); const [reason, setReason] = useState('');
    const [reassignChart, setReassignChart] = useState<RunningChart | null>(null);
    const [currentTime] = useState(() => Date.now());

    const latestDriverChart = rows.find(r => r.driver_identity_source !== null);
    const latestChartWithOdo = rows.find(r => r.end_odometer !== null);
    const initialOdometer = latestChartWithOdo?.end_odometer ?? use.handover_odometer ?? undefined;
    const latestChart = rows.find(r => r.ends_at !== null);
    const initialStartTime = latestChart ? localTimestampValue(latestChart.ends_at) : (use.handed_over_at ? localTimestampValue(use.handed_over_at) : undefined);
    const initialDriver = latestDriverChart ? {
        source: latestDriverChart.driver_identity_source as DriverIdentitySource,
        employee: latestDriverChart.driver?.source === DriverIdentitySource.Employee && latestDriverChart.driver.employee_id
            ? { id: latestDriverChart.driver.employee_id, name: latestDriverChart.driver.name, code: latestDriverChart.driver.reference }
            : null,
        externalName: latestDriverChart.driver?.source === DriverIdentitySource.External ? latestDriverChart.driver.name : undefined,
        externalReference: latestDriverChart.driver?.source === DriverIdentitySource.External ? latestDriverChart.driver.reference : undefined,
    } : undefined;

    useEffect(() => { const c = new AbortController(); listCharts(use.id, page, c.signal).then(r => { if (!c.signal.aborted) { setRows(r.data); setMeta(r.meta); setError(null); } }).catch(e => { if (!c.signal.aborted) { setRows([]); setMeta(undefined); setError(toApiError(e)); } }).finally(() => { if (!c.signal.aborted) setLoading(false); }); return () => c.abort(); }, [use.id, page, revision]);
    function reload() { setLoading(true); setEditor(null); setAction(null); setHistory(null); setRevision(v => v + 1); }
    async function submit(event: FormEvent) {
        event.preventDefault();
        if (!action) return;
        if (action.type === RunningChartAction.Finalize) {
            const chartStartMs = new Date(action.chart.starts_at).getTime();
            const chartEndMs = new Date(action.chart.ends_at).getTime();
            const nowGraceMs = Date.now() + 120_000;
            if (chartStartMs > nowGraceMs) {
                setError(new ApiError(
                    'Usage has not started yet. Running charts can only be finalized as physical evidence after the trip has concluded.',
                    422,
                    'VALIDATION_FAILED',
                    'validation_error',
                    { starts_at: ['Usage has not started yet. Running charts can only be finalized as physical evidence after the trip has concluded.'] },
                ));
                return;
            }
            if (chartEndMs > nowGraceMs) {
                setError(new ApiError(
                    'Usage is currently ongoing. Running charts can only be finalized as physical evidence after the trip has concluded.',
                    422,
                    'VALIDATION_FAILED',
                    'validation_error',
                    { ends_at: ['Usage is currently ongoing. Running charts can only be finalized as physical evidence after the trip has concluded.'] },
                ));
                return;
            }
            if (use.returned_at) {
                const returnMs = new Date(use.returned_at).getTime() + 60_000;
                if (chartEndMs > returnMs) {
                    setError(new ApiError(
                        'Usage ends after vehicle was returned. Edit the draft chart to match custody dates.',
                        422,
                        'VALIDATION_FAILED',
                        'validation_error',
                        { ends_at: ['Usage ends after vehicle was returned.'] },
                    ));
                    return;
                }
            }
        }
        setSaving(true);
        setError(null);
        try {
            await transitionChart(action.chart, action.type, action.type === RunningChartAction.Reverse ? reason.trim() : undefined);
            reload();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setSaving(false);
        }
    }
    function choose(chart: RunningChart, type: RunningChartAction) { setAction({ chart, type }); setReason(''); setError(null); }

    async function handlePrint(mode: 'lessee' | 'owner') {
        const s = use.starts_at.slice(0, 10);
        const e = use.ends_at ? use.ends_at.slice(0, 10) : s;
        setPrinting(mode);
        setError(null);
        const preview = window.open('', '_blank');
        if (preview) {
            preview.document.title = 'Preparing running chart…';
            preview.document.body.textContent = 'Preparing running chart…';
        }
        try {
            await exportRunningChartReport({
                agreement_id: use.customer_agreement.id,
                start_date: s,
                end_date: e,
                vehicle_use_id: use.id,
                sheet_mode: mode,
            }, 'print', preview);
        } catch (err) {
            preview?.close();
            setError(toApiError(err));
        } finally {
            setPrinting(null);
        }
    }

    return <section aria-label="Running Charts" className="space-y-3 border-t pt-3"><div className="flex flex-wrap items-center gap-2"><h4 className="grow font-semibold">Running Charts · {use.vehicle.label}</h4><Button variant="secondary" disabled={loading || saving || printing !== null} loading={printing === 'lessee'} onClick={() => void handlePrint('lessee')}>Print Sheet</Button>{use.owner_agreement !== null && <Button variant="secondary" disabled={loading || saving || printing !== null} loading={printing === 'owner'} onClick={() => void handlePrint('owner')}>Print Owner Sheet</Button>}<Button variant="secondary" disabled={loading || saving || !!editor} onClick={reload}>Reload charts</Button>{canManage && [VehicleUseStatus.Planned, VehicleUseStatus.InCustody, VehicleUseStatus.Returned].includes(use.status) && <Button disabled={loading || saving || !!editor || !!action} onClick={() => setEditor({})}>Record usage</Button>}</div>
        <ErrorAlert error={error} inline />{editor && <RunningChartEditor use={use} chart={editor.chart} correction={editor.correction} initialDriver={initialDriver} initialOdometer={initialOdometer} initialStartTime={initialStartTime} onSaved={reload} onCancel={() => setEditor(null)} />}
        {loading ? <LoadingState label="Loading Running Charts…" /> : error ? null : rows.map(chart => {
            const nowGraceMs = currentTime + 60_000;
            const startMs = new Date(chart.starts_at).getTime();
            const endMs = new Date(chart.ends_at).getTime();
            const isUpcoming = startMs > nowGraceMs;
            const isOngoing = !isUpcoming && endMs > nowGraceMs;
            const isCompleted = !isUpcoming && !isOngoing;
            const returnMs = use.returned_at ? new Date(use.returned_at).getTime() + 60_000 : null;
            const exceedsReturn = returnMs !== null && endMs > returnMs;

            return (
                <article key={chart.id} className="space-y-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="font-semibold text-slate-900">{chart.reference}</p>
                        <StatusBadge status={chart.status} />
                        {chart.status === RunningChartStatus.Draft && (
                            exceedsReturn ? (
                                <span className="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 border border-rose-200">
                                    Exceeds return custody
                                </span>
                            ) : isUpcoming ? (
                                <span className="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 border border-sky-200">
                                    Upcoming trip
                                </span>
                            ) : isOngoing ? (
                                <span className="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 border border-amber-200">
                                    Trip in progress
                                </span>
                            ) : (
                                <span className="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                                    Ready to finalize
                                </span>
                            )
                        )}
                    </div>
                    <p className="text-sm text-slate-600">{formatBusinessDateTime(chart.starts_at)} — {formatBusinessDateTime(chart.ends_at)}</p>
                    {chart.status === RunningChartStatus.Draft && (
                        exceedsReturn ? (
                            <p className="text-xs text-rose-700 font-medium">
                                Usage ends after the vehicle was returned ({formatBusinessDateTime(use.returned_at!)}). Edit draft to adjust usage times within actual custody.
                            </p>
                        ) : isUpcoming ? (
                            <p className="text-xs text-slate-500">
                                Scheduled trip starts {formatBusinessDateTime(chart.starts_at)}. Physical evidence can be finalized after the trip concludes.
                            </p>
                        ) : isOngoing ? (
                            <p className="text-xs text-amber-700">
                                Trip is currently ongoing (scheduled end {formatBusinessDateTime(chart.ends_at)}). Finalize after the trip concludes, or edit draft if concluded early.
                            </p>
                        ) : null
                    )}
                    <p className="text-sm text-slate-700">Driver: {chart.driver ? `${chart.driver.name} · ${chart.driver.reference}` : 'Not recorded'}</p>
                    <p className="text-sm text-slate-700">Total distance: {chart.total_km === null ? 'Not recorded' : <><QuantityDisplay value={chart.total_km} /> km</>}</p>
                    {chart.corrects_chart && <p className="text-xs text-blue-600 font-medium">Corrects {chart.corrects_chart.reference}</p>}
                    {chart.corrected_by_chart && <p className="text-xs text-amber-700 font-medium">Corrected by chart {chart.corrected_by_chart.reference}</p>}
                    <div className="flex flex-wrap gap-2">
                        <Button type="button" variant="secondary" aria-expanded={history === chart.id} onClick={() => setHistory(history === chart.id ? null : chart.id)}>
                            {history === chart.id ? 'Hide chart history' : 'Chart history'}
                        </Button>
                        {!editor && !action && <>
                            {canManage && chart.status === RunningChartStatus.Draft && <Button onClick={() => setEditor({ chart })}>Edit draft</Button>}
                            {hasPermission(auth, CHART_PERMISSION.finalize) && chart.status === RunningChartStatus.Draft && (
                                <Button
                                    disabled={!isCompleted || exceedsReturn}
                                    title={
                                        exceedsReturn
                                            ? 'Usage ends after vehicle return'
                                            : isUpcoming
                                            ? 'Usage has not started yet'
                                            : isOngoing
                                            ? 'Usage is currently ongoing'
                                            : 'Finalize physical evidence'
                                    }
                                    onClick={() => choose(chart, RunningChartAction.Finalize)}
                                >
                                    Finalize usage
                                </Button>
                            )}
                            {hasPermission(auth, CHART_PERMISSION.reverse) && chart.status === RunningChartStatus.Finalized && <Button variant="danger" onClick={() => choose(chart, RunningChartAction.Reverse)}>Reverse usage</Button>}
                            {canManage && chart.status === RunningChartStatus.Reversed && !chart.corrected_by_chart && <Button onClick={() => setEditor({ chart, correction: true })}>Create correction</Button>}
                            {canManage && chart.status !== RunningChartStatus.Reversed && (
                                <Button variant="secondary" onClick={() => setReassignChart(chart)}>Reassign driver</Button>
                            )}
                        </>}
                    </div>
                    {history === chart.id && <RunningChartHistoryPanel key={chart.id} id={chart.id} />}
                    <UsageChargePanel chart={chart} hasOwner={use.owner_agreement !== null} />
                </article>
            );
        })}
        {!loading && !error && !rows.length && <p className="text-sm text-slate-500">No Running Charts recorded.</p>}<Pagination meta={meta} disabled={loading || saving} onPageChange={value => { setLoading(true); setPage(value); setAction(null); }} />
        {action && <form onSubmit={submit} aria-label="Confirm chart action" className="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p className="text-sm text-slate-700">{action.type === RunningChartAction.Finalize ? 'Finalize physical evidence. This does not create charges.' : 'Reverse physical evidence and retain its original history.'} · {action.chart.reference}</p>{action.type === RunningChartAction.Reverse && <Textarea label="Reversal reason" required value={reason} onChange={e => setReason(e.target.value)} error={error?.fields.reason?.[0]} />}<div className="flex flex-wrap gap-2"><Button type="submit" variant={action.type === RunningChartAction.Reverse ? 'danger' : 'primary'} loading={saving} disabled={action.type === RunningChartAction.Reverse && !reason.trim()}>{CHART_ACTION_CONFIRM_LABELS[action.type]}</Button><Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button></div></form>}
        {reassignChart && (
            <ReassignDriverModal
                open={Boolean(reassignChart)}
                chart={reassignChart}
                onClose={() => setReassignChart(null)}
                onSaved={reload}
            />
        )}
    </section>;
}
