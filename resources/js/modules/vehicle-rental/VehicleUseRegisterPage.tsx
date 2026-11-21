import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { formatBusinessDateTime, businessDateTimeInputValueWithSeconds } from '@/shared/utils/businessDate';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Textarea } from '@/shared/components/Textarea';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { ApiError, toApiError } from '@/shared/api/apiError';
import { requestLookup } from '@/shared/api/lookupRequest';
import { endpoints } from '@/shared/api/endpoints';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import type { PaginationMeta } from '@/shared/types/pagination';
import { listVehicleUseRegister, planInternalVehicleUse, transitionVehicleUse } from './vehicleUseApi';
import {
    operationalTimeZoneLabel,
    OPERATIONAL_TIME_STEP_SECONDS,
    timestampWithOffset,
    USE_LABELS,
    USE_ACTION_LABELS,
    USE_ACTION_CONFIRM_LABELS,
    USE_PERMISSION,
    VehicleUseAction,
    VehicleUseStatus,
    type VehicleUse,
    type VehicleUseRegisterFilters,
} from './vehicleUse';
import { CHART_PERMISSION } from './runningCharts';
import { RunningChartsPanel } from './RunningChartsPanel';
import { VehicleUseHistoryPanel } from './VehicleUseHistoryPanel';

function useSafeAuth() {
    try {
        return useAuth();
    } catch {
        return null;
    }
}

const lookupVehicles = (params: LookupLoadParams) =>
    requestLookup<Record<string, unknown>>(`${endpoints.vehicles}/lookup/active`, params).then(result => ({
        ...result,
        data: result.data.map(row => ({
            id: Number(row.id),
            name: String(row.registration_number || row.vehicle_number),
        })),
    }));

export default function VehicleUseRegisterPage() {
    const auth = useSafeAuth();
    const canManage = Boolean(auth && (hasPermission(auth, USE_PERMISSION.manage) || hasPermission(auth, 'vehicle-rental.vehicle-use.manage')));

    const searchParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : new URLSearchParams();
    const initialSearch = searchParams.get('search') ?? '';
    const initialStatusParam = searchParams.get('status') ?? searchParams.get('use_status') ?? '';
    const initialStatus = Object.values(VehicleUseStatus).includes(initialStatusParam as VehicleUseStatus)
        ? (initialStatusParam as VehicleUseStatus)
        : '';
    const initialKindParam = searchParams.get('kind') ?? '';
    const initialKind = ['customer', 'owner', 'internal'].includes(initialKindParam)
        ? (initialKindParam as 'customer' | 'owner' | 'internal')
        : '';
    const initialFrom = searchParams.get('from') ?? '';
    const initialUntil = searchParams.get('until') ?? '';
    let initialFromOffset: string | undefined;
    let initialUntilOffset: string | undefined;
    if (initialFrom) {
        try { initialFromOffset = timestampWithOffset(initialFrom); } catch {}
    }
    if (initialUntil) {
        try { initialUntilOffset = timestampWithOffset(initialUntil); } catch {}
    }

    const [search, setSearch] = useState(initialSearch);
    const [status, setStatus] = useState<VehicleUseStatus | ''>(initialStatus);
    const [kind, setKind] = useState<'customer' | 'owner' | 'internal' | ''>(initialKind);
    const [from, setFrom] = useState(initialFrom);
    const [until, setUntil] = useState(initialUntil);
    const [filters, setFilters] = useState<VehicleUseRegisterFilters>({
        ...(initialSearch ? { search: initialSearch } : {}),
        ...(initialStatus ? { use_status: initialStatus } : {}),
        ...(initialKind ? { kind: initialKind } : {}),
        ...(initialFromOffset ? { from: initialFromOffset } : {}),
        ...(initialUntilOffset ? { until: initialUntilOffset } : {}),
    });
    const [page, setPage] = useState(1);
    const [rows, setRows] = useState<VehicleUse[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const [selected, setSelected] = useState<number | null>(null);
    const [activeChartsUseId, setActiveChartsUseId] = useState<number | null>(null);

    // Internal deployment form state
    const [deploying, setDeploying] = useState(false);
    const [deployVehicle, setDeployVehicle] = useState<NamedResource | null>(null);
    const [deployStartsAt, setDeployStartsAt] = useState('');
    const [deployEndsAt, setDeployEndsAt] = useState('');
    const [deployNotes, setDeployNotes] = useState('');
    const [deploySaving, setDeploySaving] = useState(false);
    const [deployError, setDeployError] = useState<ApiError | null>(null);

    // Custody action form state
    const [action, setAction] = useState<{ row: VehicleUse; type: VehicleUseAction } | null>(null);
    const [actionAt, setActionAt] = useState('');
    const [actionOdometer, setActionOdometer] = useState('');
    const [actionReason, setActionReason] = useState('');
    const [actionSaving, setActionSaving] = useState(false);

    useEffect(() => {
        const controller = new AbortController();
        listVehicleUseRegister(filters, page, controller.signal).then(result => {
            if (!controller.signal.aborted) { setRows(result.data); setMeta(result.meta); setError(null); }
        }).catch(failure => { if (!controller.signal.aborted) { setRows([]); setMeta(undefined); setError(toApiError(failure)); } })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [filters, page]);

    function reload() {
        setLoading(true);
        setAction(null);
        setSelected(null);
        setActiveChartsUseId(null);
        const controller = new AbortController();
        listVehicleUseRegister(filters, page, controller.signal).then(result => {
            if (!controller.signal.aborted) { setRows(result.data); setMeta(result.meta); setError(null); }
        }).catch(failure => { if (!controller.signal.aborted) { setRows([]); setMeta(undefined); setError(toApiError(failure)); } })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
    }

    function syncUrl(nextSearch: string, nextStatus: string, nextFrom: string, nextUntil: string, nextKind: string) {
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        if (nextSearch.trim()) url.searchParams.set('search', nextSearch.trim());
        else url.searchParams.delete('search');
        if (nextStatus) url.searchParams.set('status', nextStatus);
        else url.searchParams.delete('status');
        url.searchParams.delete('use_status');
        if (nextKind) url.searchParams.set('kind', nextKind);
        else url.searchParams.delete('kind');
        if (nextFrom) url.searchParams.set('from', nextFrom);
        else url.searchParams.delete('from');
        if (nextUntil) url.searchParams.set('until', nextUntil);
        else url.searchParams.delete('until');
        window.history.replaceState({}, '', url.toString());
    }

    function apply(event: FormEvent) {
        event.preventDefault();
        if (from && until && until <= from) {
            setError(new ApiError(
                'Planned period end must follow period start.',
                422,
                'VALIDATION_FAILED',
                'validation_error',
                { until: ['Planned period end must follow period start.'] },
            ));
            return;
        }
        try {
            const next: VehicleUseRegisterFilters = {
                search: search.trim() || undefined,
                use_status: status || undefined,
                from: from ? timestampWithOffset(from) : undefined,
                until: until ? timestampWithOffset(until) : undefined,
                ...(kind ? { kind } : {}),
            };
            syncUrl(search, status, from, until, kind);
            setLoading(true); setError(null); setPage(1); setSelected(null); setActiveChartsUseId(null); setFilters(next);
        } catch (failure) { setError(toApiError(failure)); }
    }

    function clearFilters() {
        const needsReload = error !== null || page !== 1 || Object.values(filters).some(value => value !== undefined);
        setSearch(''); setStatus(''); setKind(''); setFrom(''); setUntil(''); setSelected(null); setActiveChartsUseId(null); setError(null);
        syncUrl('', '', '', '', '');
        if (needsReload) { setLoading(true); setFilters({}); setPage(1); }
    }

    function chooseAction(row: VehicleUse, type: VehicleUseAction) {
        setAction({ row, type });
        setActionAt(businessDateTimeInputValueWithSeconds());
        setActionOdometer('');
        setActionReason(
            type === VehicleUseAction.Handover
                ? 'Internal vehicle handover'
                : type === VehicleUseAction.Return
                    ? 'Internal vehicle return'
                    : 'Cancellation of planned internal deployment'
        );
        setError(null);
    }

    async function handleActionSubmit(event: FormEvent) {
        event.preventDefault();
        if (!action) return;

        setActionSaving(true);
        setError(null);
        try {
            await transitionVehicleUse(action.row, action.type, {
                reason: actionReason.trim(),
                ...(action.type === VehicleUseAction.Cancel ? {} : {
                    occurred_at: timestampWithOffset(actionAt),
                    odometer: actionOdometer.trim() || null,
                }),
            });
            setAction(null);
            reload();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setActionSaving(false);
        }
    }

    async function handleDeploySubmit(event: FormEvent) {
        event.preventDefault();
        if (!deployVehicle || !deployStartsAt) return;

        setDeploySaving(true);
        setDeployError(null);
        try {
            await planInternalVehicleUse({
                vehicle_id: deployVehicle.id,
                starts_at: timestampWithOffset(deployStartsAt),
                ends_at: deployEndsAt ? timestampWithOffset(deployEndsAt) : null,
                notes: deployNotes.trim() || null,
            });
            setDeploying(false);
            setDeployVehicle(null);
            setDeployStartsAt('');
            setDeployEndsAt('');
            setDeployNotes('');
            reload();
        } catch (failure) {
            setDeployError(toApiError(failure));
        } finally {
            setDeploySaving(false);
        }
    }

    const statusOptions = Object.values(VehicleUseStatus).map(value => ({ value, label: USE_LABELS[value] }));
    const kindOptions = [
        { value: '', label: 'All operations' },
        { value: 'customer', label: 'Customer rentals' },
        { value: 'owner', label: 'Owner-leased vehicles' },
        { value: 'internal', label: 'Internal company operations' },
    ];

    return (
        <div className="space-y-5">
            <ContentHeader
                title="Vehicle Use register"
                description="Find assignments and review planned periods, actual custody, replacements and history."
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="secondary" disabled={loading} onClick={reload}>
                            Reload
                        </Button>
                        {canManage && (
                            <Button disabled={loading || deploying} onClick={() => setDeploying(true)}>
                                Deploy vehicle for internal use
                            </Button>
                        )}
                    </div>
                }
            />

            {deploying && (
                <Panel title="Deploy vehicle for internal company use">
                    <form onSubmit={handleDeploySubmit} className="space-y-4">
                        <p className="text-sm text-slate-600">
                            Deploy a company-owned or fleet vehicle for internal business operations without requiring a customer assignment.
                        </p>
                        <ErrorAlert error={deployError} inline />
                        <div className="grid gap-3 sm:grid-cols-2">
                            <LookupSelect
                                label="Vehicle"
                                value={deployVehicle}
                                onChange={setDeployVehicle}
                                search={lookupVehicles}
                                loadOnOpen
                                minSearchLength={0}
                                required
                                error={deployError?.fields.vehicle_id?.[0]}
                            />
                            <Input
                                label="Planned deployment start"
                                type="datetime-local"
                                step={OPERATIONAL_TIME_STEP_SECONDS}
                                value={deployStartsAt}
                                onChange={e => setDeployStartsAt(e.target.value)}
                                required
                                error={deployError?.fields.starts_at?.[0]}
                            />
                            <Input
                                label="Planned deployment end (optional)"
                                type="datetime-local"
                                step={OPERATIONAL_TIME_STEP_SECONDS}
                                value={deployEndsAt}
                                onChange={e => setDeployEndsAt(e.target.value)}
                                error={deployError?.fields.ends_at?.[0]}
                            />
                            <div className="sm:col-span-2">
                                <Textarea
                                    label="Deployment notes & purpose"
                                    value={deployNotes}
                                    onChange={e => setDeployNotes(e.target.value)}
                                    placeholder="Operational purpose (e.g. site logistics, employee transport, emergency response)"
                                    error={deployError?.fields.notes?.[0]}
                                />
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" loading={deploySaving} disabled={!deployVehicle || !deployStartsAt}>
                                Confirm deployment
                            </Button>
                            <Button type="button" variant="secondary" disabled={deploySaving} onClick={() => setDeploying(false)}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                </Panel>
            )}

            <Panel title="Filters">
                <form aria-label="Filter vehicle use" onSubmit={apply} className="grid gap-3 md:grid-cols-2">
                    <Input label="Vehicle, agreement or party" value={search} onChange={event => setSearch(event.target.value)} error={error?.fields.search?.[0]} />
                    <Select label="Use status" placeholder="All states" options={statusOptions} value={status} onChange={event => setStatus(event.target.value as VehicleUseStatus | '')} />
                    <Select label="Operational scope" placeholder="All operations" options={kindOptions} value={kind} onChange={event => setKind(event.target.value as 'customer' | 'owner' | 'internal' | '')} />
                    <Input label="Planned period start (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} value={from} onChange={event => setFrom(event.target.value)} error={error?.fields.from?.[0]} />
                    <Input label="Planned period end (optional)" type="datetime-local" step={OPERATIONAL_TIME_STEP_SECONDS} value={until} onChange={event => setUntil(event.target.value)} error={error?.fields.until?.[0]} />
                    <p className="text-sm text-slate-600 md:col-span-2">Filter times use {operationalTimeZoneLabel()}. Results overlap the planned period. Actual custody is shown separately; no rental charges are calculated.</p>
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit" loading={loading}>Apply filters</Button>
                        <Button type="button" variant="secondary" disabled={loading} onClick={clearFilters}>Clear filters</Button>
                    </div>
                </form>
            </Panel>

            <ErrorAlert error={error} inline />

            {action && (
                <Panel title={`${USE_ACTION_LABELS[action.type]} · ${action.row.vehicle.label}`}>
                    <form onSubmit={handleActionSubmit} className="space-y-4">
                        <fieldset disabled={actionSaving} className="space-y-3">
                            {action.type !== VehicleUseAction.Cancel && (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <Input
                                        label="Actual event time"
                                        type="datetime-local"
                                        step={OPERATIONAL_TIME_STEP_SECONDS}
                                        value={actionAt}
                                        onChange={e => setActionAt(e.target.value)}
                                        required
                                        error={error?.fields.occurred_at?.[0]}
                                    />
                                    <Input
                                        label="Odometer (optional)"
                                        value={actionOdometer}
                                        onChange={e => setActionOdometer(e.target.value)}
                                        inputMode="decimal"
                                        error={error?.fields.odometer?.[0]}
                                    />
                                </div>
                            )}
                            <Textarea
                                label="Action reason"
                                value={actionReason}
                                onChange={e => setActionReason(e.target.value)}
                                required
                                error={error?.fields.reason?.[0]}
                            />
                        </fieldset>
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" variant={action.type === VehicleUseAction.Cancel ? 'danger' : 'primary'} loading={actionSaving} disabled={!actionReason.trim() || (action.type !== VehicleUseAction.Cancel && !actionAt)}>
                                {USE_ACTION_CONFIRM_LABELS[action.type]}
                            </Button>
                            <Button type="button" variant="secondary" disabled={actionSaving} onClick={() => setAction(null)}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                </Panel>
            )}

            {loading ? <LoadingState label="Loading vehicle use register…" /> : error ? null : <>
                {rows.length === 0 && <p className="rounded-xl border border-slate-200 bg-white p-5 text-slate-500 shadow-sm">No vehicle uses match these filters.</p>}
                {rows.map(row => <article key={row.id} className="space-y-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                        <h2 className="text-base font-semibold text-slate-900">
                            {row.vehicle.label}
                            {row.customer_agreement ? (
                                <>
                                    {' · '}
                                    <Link
                                        to={`/vehicle-rental/customer/agreements?search=${encodeURIComponent(row.customer_agreement.reference)}`}
                                        className="text-blue-600 hover:text-blue-800 hover:underline"
                                        title="Review customer agreement"
                                    >
                                        {row.customer_agreement.reference}
                                    </Link>
                                </>
                            ) : (
                                <span className="text-slate-600 font-normal"> · Internal Company Use</span>
                            )}
                        </h2>
                        <StatusBadge status={row.status} label={USE_LABELS[row.status]} />
                        {(row.customer_agreement?.driver_mode || row.owner_agreement?.driver_mode) && (
                            <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 border border-slate-200">
                                {(row.customer_agreement?.driver_mode ?? row.owner_agreement?.driver_mode) === 'self_drive' ? 'Self-drive' : 'With driver'}
                            </span>
                        )}
                    </div>
                    <div className="grid grid-cols-1 gap-1 text-sm text-slate-700 sm:grid-cols-2">
                        <p>Customer: {row.customer_agreement ? row.customer_agreement.party_name : 'Internal Company Use'}</p>
                        <p>
                            {row.owner_agreement ? (
                                <>
                                    Owner: {row.owner_agreement.party_name} ·{' '}
                                    <Link
                                        to={`/vehicle-rental/owner/agreements?search=${encodeURIComponent(row.owner_agreement.reference)}`}
                                        className="text-blue-600 hover:text-blue-800 hover:underline"
                                        title="Review owner agreement"
                                    >
                                        {row.owner_agreement.reference}
                                    </Link>
                                </>
                            ) : (
                                'Company supply'
                            )}
                        </p>
                        <p>Planned: {formatBusinessDateTime(row.starts_at)} — {row.ends_at ? formatBusinessDateTime(row.ends_at) : 'Open-ended'}</p>
                        <p>Handed over: {formatBusinessDateTime(row.handed_over_at, 'Not recorded')}</p>
                        <p>Returned: {formatBusinessDateTime(row.returned_at, 'Not recorded')}</p>
                    </div>
                    {row.replaces_use && <p className="text-sm font-medium text-amber-700">Replaces vehicle {row.replaces_use.vehicle_label}</p>}
                    {row.replaced_by_use && <p className="text-sm font-medium text-purple-700">Replaced by vehicle {row.replaced_by_use.vehicle_label}</p>}
                    <div className="flex flex-wrap gap-2">
                        <Button type="button" variant="secondary" aria-expanded={selected === row.id} onClick={() => setSelected(selected === row.id ? null : row.id)}>
                            {selected === row.id ? 'Hide details' : 'Review ' + row.vehicle.label}
                        </Button>
                        {auth && hasPermission(auth, CHART_PERMISSION.view) && (
                            <Button
                                type="button"
                                variant="secondary"
                                aria-expanded={activeChartsUseId === row.id}
                                onClick={() => setActiveChartsUseId(activeChartsUseId === row.id ? null : row.id)}
                            >
                                {activeChartsUseId === row.id ? 'Hide Running Charts' : 'Running Charts'}
                            </Button>
                        )}
                        {canManage && !action && row.status === VehicleUseStatus.Planned && (
                            <>
                                <Button onClick={() => chooseAction(row, VehicleUseAction.Handover)}>
                                    Hand over vehicle
                                </Button>
                                <Button variant="danger" onClick={() => chooseAction(row, VehicleUseAction.Cancel)}>
                                    Cancel plan
                                </Button>
                            </>
                        )}
                        {canManage && !action && row.status === VehicleUseStatus.InCustody && (
                            <Button onClick={() => chooseAction(row, VehicleUseAction.Return)}>
                                Record return
                            </Button>
                        )}
                    </div>
                    {selected === row.id && <div className="space-y-3 border-t border-slate-200 pt-3">
                        <p>Handover odometer: {row.handover_odometer === null ? 'Not recorded' : <QuantityDisplay value={row.handover_odometer} />}</p>
                        <p>Return odometer: {row.return_odometer === null ? 'Not recorded' : <QuantityDisplay value={row.return_odometer} />}</p>
                        <p>Notes: {row.notes ?? 'Not recorded'}</p>
                        <VehicleUseHistoryPanel key={row.id} id={row.id} />
                    </div>}
                    {activeChartsUseId === row.id && (
                        <RunningChartsPanel key={`charts-${row.id}`} use={row} />
                    )}
                </article>)}
                <Pagination meta={meta} onPageChange={next => { setLoading(true); setSelected(null); setActiveChartsUseId(null); setPage(next); }} />
            </>}
        </div>
    );
}
