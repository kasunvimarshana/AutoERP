import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { CHART_PERMISSION } from './runningCharts';
import { RunningChartsPanel } from './RunningChartsPanel';
import { useEffect, useState, type FormEvent } from 'react';
import { toApiError, ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Textarea } from '@/shared/components/Textarea';
import { Pagination } from '@/shared/components/Pagination';
import { formatBusinessDateTime, businessDateInputValue, businessDateTimeInputValueWithSeconds, toHourFloor } from '@/shared/utils/businessDate';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { PaginationMeta } from '@/shared/types/pagination';
import { AgreementStatus, type Agreement } from './agreements';
import { VehicleUseEditor } from './VehicleUseEditor';
import { VehicleUseHistoryPanel } from './VehicleUseHistoryPanel';
import { listVehicleUses, transitionVehicleUse } from './vehicleUseApi';
import { operationalTimeZoneLabel, OPERATIONAL_TIME_STEP_SECONDS, timestampWithOffset, localTimestampValue, VehicleUseStatus, VehicleUseAction, USE_ACTION_CONFIRM_LABELS, USE_ACTION_LABELS, USE_LABELS, type VehicleUse } from './vehicleUse';
export function VehicleUsePanel({
    agreement,
    canManage,
    initialVehicleId,
    onVehicleAssigned,
}: {
    agreement: Agreement;
    canManage: boolean;
    initialVehicleId?: number;
    onVehicleAssigned?: () => void;
}) {
    const auth = useAuth(); const [charts, setCharts] = useState<number | null>(null);
    const [replacement, setReplacement] = useState<VehicleUse | null>(null);
    const [rows, setRows] = useState<VehicleUse[]>([]); const [page, setPage] = useState(1); const [meta, setMeta] = useState<PaginationMeta>(); const [revision, setRevision] = useState(0);
    const [error, setError] = useState<ApiError | null>(null); const [loading, setLoading] = useState(true);
    const [adding, setAdding] = useState(Boolean(initialVehicleId));
    const [action, setAction] = useState<{ row: VehicleUse; type: VehicleUseAction } | null>(null); const [at, setAt] = useState(''); const [odometer, setOdometer] = useState(''); const [reason, setReason] = useState(''); const [saving, setSaving] = useState(false); const [history, setHistory] = useState<number | null>(null);
    const actionHandoverLocal = (() => {
        if (!action?.row?.handed_over_at) return undefined;
        try {
            return localTimestampValue(action.row.handed_over_at);
        } catch {
            return action.row.handed_over_at.slice(0, 19);
        }
    })();
    const actionPlannedStartLocal = (() => {
        if (!action?.row?.starts_at) return undefined;
        try {
            return localTimestampValue(action.row.starts_at);
        } catch {
            return action.row.starts_at.slice(0, 19);
        }
    })();
    const actionPlannedEndLocal = (() => {
        if (!action?.row?.ends_at) return undefined;
        try {
            return localTimestampValue(action.row.ends_at);
        } catch {
            return action.row.ends_at.slice(0, 19);
        }
    })();
    useEffect(() => { const c = new AbortController(); listVehicleUses(agreement.id, page, c.signal).then(r => { if (!c.signal.aborted) { setRows(r.data); setMeta(r.meta); setError(null); } }).catch(e => { if (!c.signal.aborted) { setRows([]); setMeta(undefined); setError(toApiError(e)); } }).finally(() => { if (!c.signal.aborted) setLoading(false); }); return () => c.abort(); }, [agreement.id, page, revision]);
    function reload() { setReplacement(null); setLoading(true); setAdding(false); setAction(null); setHistory(null); setRevision(v => v + 1); }
    function choose(row: VehicleUse, type: VehicleUseAction) {
        setAction({ row, type });
        setAt('');
        setOdometer('');
        setReason(
            type === VehicleUseAction.Handover
                ? 'Vehicle handed over to customer'
                : type === VehicleUseAction.Return
                    ? 'Vehicle returned by customer'
                    : ''
        );
        setError(null);
    }
    async function submit(event: FormEvent) {
        event.preventDefault(); if (!action) return;

        if (action.type !== VehicleUseAction.Cancel) {
            let atIso = '';
            try {
                atIso = timestampWithOffset(at);
            } catch {
                return;
            }
            const parsedAt = new Date(atIso);
            if (parsedAt.getTime() > Date.now() + 120_000) {
                setError(new ApiError(
                    action.type === VehicleUseAction.Handover ? 'Handover time cannot be in the future.' : 'Return time cannot be in the future.',
                    422,
                    'VALIDATION_FAILED',
                    'validation',
                    { occurred_at: [action.type === VehicleUseAction.Handover ? 'Handover time cannot be in the future.' : 'Return time cannot be in the future.'] },
                ));
                return;
            }
            if (action.type === VehicleUseAction.Return && action.row.handed_over_at && atIso <= action.row.handed_over_at) {
                setError(new ApiError(
                    'Return must follow handover.',
                    422,
                    'VALIDATION_FAILED',
                    'validation',
                    { occurred_at: ['Return must follow handover.'] },
                ));
                return;
            }
            if (action.type === VehicleUseAction.Return && action.row.handover_odometer !== null && odometer.trim() !== '') {
                if (Number(odometer) < Number(action.row.handover_odometer)) {
                    setError(new ApiError(
                        'Return odometer cannot be less than handover odometer.',
                        422,
                        'VALIDATION_FAILED',
                        'validation',
                        { odometer: ['Return odometer cannot be less than handover odometer.'] },
                    ));
                    return;
                }
            }
        }

        setSaving(true); setError(null);
        try { await transitionVehicleUse(action.row, action.type, { reason: reason.trim(), ...(action.type === VehicleUseAction.Cancel ? {} : { occurred_at: timestampWithOffset(at), odometer: odometer.trim() || null }) }); reload(); }
        catch (failure) { setError(toApiError(failure)); } finally { setSaving(false); }
    }
    return <section aria-label="Assigned vehicles" className="space-y-4 border-t pt-4">
        <div className="flex flex-wrap items-center gap-2"><h3 className="grow text-lg font-semibold">Assigned vehicles</h3><Button variant="secondary" disabled={loading || saving || adding || !!replacement} onClick={reload}>Reload vehicles</Button>{canManage && agreement.status === AgreementStatus.Active && <Button disabled={loading || saving || adding || !!replacement || action !== null} onClick={() => setAdding(true)}>Assign vehicle</Button>}</div>
        {agreement.status === AgreementStatus.Draft && (
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                This agreement is currently in Draft. Activate the agreement to assign and dispatch vehicles.
            </div>
        )}
        <ErrorAlert error={error} inline />
        {(adding || replacement) && (
            <VehicleUseEditor
                initialVehicleId={initialVehicleId}
                replacement={replacement ?? undefined}
                agreement={agreement}
                onSaved={() => {
                    onVehicleAssigned?.();
                    reload();
                }}
                onCancel={() => {
                    setAdding(false);
                    setReplacement(null);
                }}
            />
        )}
        {loading ? <LoadingState label="Loading assigned vehicles…" /> : error ? null : rows.map(row => <article key={row.id} className="space-y-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex flex-wrap items-center gap-2"><p className="font-semibold text-slate-900">{row.vehicle.label}</p><StatusBadge status={row.status} label={USE_LABELS[row.status]} /></div><p className="text-sm text-slate-600">{formatBusinessDateTime(row.starts_at)} — {row.ends_at ? formatBusinessDateTime(row.ends_at) : 'Open-ended'}</p>{row.replaces_use && <p className="text-xs text-blue-600 font-medium">Replaces {row.replaces_use.vehicle_label}</p>}{row.replaced_by_use && <p className="text-xs text-amber-600 font-medium">Replaced by {row.replaced_by_use.vehicle_label}</p>}<p className="text-sm text-slate-600">{row.owner_agreement ? `Owner: ${row.owner_agreement.party_name} · ${row.owner_agreement.reference}` : 'Company supply'}</p>
            {row.handed_over_at && <p className="text-xs text-slate-500">Actual handover: {formatBusinessDateTime(row.handed_over_at)} · Odometer: {row.handover_odometer === null ? 'Not recorded' : <QuantityDisplay value={row.handover_odometer} />}</p>}{row.returned_at && <p className="text-xs text-slate-500">Actual return: {formatBusinessDateTime(row.returned_at)} · Odometer: {row.return_odometer === null ? 'Not recorded' : <QuantityDisplay value={row.return_odometer} />}</p>}
            <div className="flex flex-wrap gap-2">
                <Button type="button" variant="secondary" disabled={saving} aria-expanded={history === row.id} onClick={() => setHistory(history === row.id ? null : row.id)}>
                    {history === row.id ? 'Hide vehicle-use history' : 'Vehicle-use history'}
                </Button>
                {hasPermission(auth, CHART_PERMISSION.view) && (
                    <Button type="button" variant="secondary" aria-expanded={charts === row.id} onClick={() => setCharts(charts === row.id ? null : row.id)}>
                        {charts === row.id ? 'Hide Running Charts' : 'Running Charts'}
                    </Button>
                )}
                {canManage && !adding && !replacement && !action && row.status === VehicleUseStatus.Planned && (
                    <>
                        <Button onClick={() => choose(row, VehicleUseAction.Handover)}>Hand over vehicle</Button>
                        <Button variant="danger" onClick={() => choose(row, VehicleUseAction.Cancel)}>Cancel plan</Button>
                    </>
                )}
                {canManage && !adding && !replacement && !action && row.status === VehicleUseStatus.InCustody && (
                    <>
                        <Button onClick={() => choose(row, VehicleUseAction.Return)}>Record return</Button>
                        <Button variant="secondary" onClick={() => setReplacement(row)}>Replace vehicle</Button>
                    </>
                )}
            </div>
            {charts === row.id && <RunningChartsPanel key={row.id} use={row} />}
            {history === row.id && <VehicleUseHistoryPanel key={row.id} id={row.id} />}
        </article>)}
        {!loading && !error && rows.length === 0 && <p className="text-sm text-slate-500">No vehicles assigned.</p>}<Pagination meta={meta} disabled={loading || saving} onPageChange={value => { setLoading(true); setPage(value); setAction(null); }} />
        {action && <form onSubmit={submit} className="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-label={USE_ACTION_LABELS[action.type]}>
            <h3 className="font-semibold">{USE_ACTION_LABELS[action.type]} · {action.row.vehicle.label}</h3>
            <p className="text-sm">{action.type === VehicleUseAction.Cancel ? 'Cancel the planned use and retain its history.' : `Record the actual event in ${operationalTimeZoneLabel()}. An expected return alone never releases a vehicle.`}</p>
            <fieldset disabled={saving} className="space-y-3">
                {action.type !== VehicleUseAction.Cancel && (
                    <>
                        <Input
                            label="Actual event time"
                            type="datetime-local"
                            step={OPERATIONAL_TIME_STEP_SECONDS}
                            min={action.type === VehicleUseAction.Handover
                                ? (agreement.starts_on ? `${agreement.starts_on}T00:00` : undefined)
                                : (actionHandoverLocal ? toHourFloor(actionHandoverLocal) : undefined)
                            }
                            max={action.type === VehicleUseAction.Handover && actionPlannedEndLocal
                                ? actionPlannedEndLocal
                                : `${businessDateInputValue()}T23:59:59`
                            }
                            value={at}
                            onChange={e => setAt(e.target.value)}
                            required
                            hint={action.type === VehicleUseAction.Handover
                                ? `Planned period: ${formatBusinessDateTime(action.row.starts_at)}${action.row.ends_at ? ` — ${formatBusinessDateTime(action.row.ends_at)}` : ''}`
                                : (action.row.handed_over_at ? `Handover occurred: ${formatBusinessDateTime(action.row.handed_over_at)}` : undefined)
                            }
                            error={error?.fields.occurred_at?.[0]}
                        />
                        {action.type === VehicleUseAction.Handover && (
                            <div className="flex flex-wrap gap-2 text-xs">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => setAt(actionPlannedStartLocal ?? '')}
                                >
                                    Use planned handover time
                                </Button>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => {
                                        try {
                                            setAt(businessDateTimeInputValueWithSeconds());
                                        } catch {
                                            setAt(actionPlannedStartLocal ?? '');
                                        }
                                    }}
                                >
                                    Use current time
                                </Button>
                            </div>
                        )}
                        {action.type === VehicleUseAction.Return && (
                            <div className="flex flex-wrap gap-2 text-xs">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => {
                                        try {
                                            setAt(businessDateTimeInputValueWithSeconds());
                                        } catch {
                                            setAt(new Date().toISOString().slice(0, 19));
                                        }
                                    }}
                                >
                                    Use current time
                                </Button>
                            </div>
                        )}
                        <Input
                            label="Odometer (optional)"
                            value={odometer}
                            onChange={e => setOdometer(e.target.value)}
                            inputMode="decimal"
                            min={action.type === VehicleUseAction.Return && action.row.handover_odometer ? action.row.handover_odometer : undefined}
                            hint={action.type === VehicleUseAction.Return && action.row.handover_odometer
                                ? `Handover reading was ${action.row.handover_odometer}. Return reading cannot be lower.`
                                : (action.type === VehicleUseAction.Handover ? 'Record the physical vehicle odometer reading at handover if verified.' : undefined)
                            }
                            error={error?.fields.odometer?.[0]}
                        />
                    </>
                )}
                <Textarea label="Action reason" value={reason} onChange={e => setReason(e.target.value)} required error={error?.fields.reason?.[0]} />
            </fieldset><div className="flex flex-wrap gap-2"><Button type="submit" variant={action.type === VehicleUseAction.Cancel ? 'danger' : 'primary'} loading={saving} disabled={!reason.trim() || (action.type !== VehicleUseAction.Cancel && !at)}>{USE_ACTION_CONFIRM_LABELS[action.type]}</Button><Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button></div>
        </form>}
    </section>;
}
