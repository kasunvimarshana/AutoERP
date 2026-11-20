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
import { formatBusinessDateTime, businessDateInputValue } from '@/shared/utils/businessDate';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { ApiError, toApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { exportRunningChartReport, listChartRegister, listCharts, transitionChart } from './runningChartApi';
import {
    AC_LABELS,
    CHART_ACTION_CONFIRM_LABELS,
    CHART_LABELS,
    CHART_PERMISSION,
    COUNT_LABELS,
    DISTANCE_LABELS,
    DriverIdentitySource,
    formatMinutes,
    RunningChartAction,
    RunningChartStatus,
    type ChartRegisterFilters,
    type ChartRegisterRow,
    type RunningChart,
} from './runningCharts';
import {
    localTimestampValue,
    operationalTimeZoneLabel,
    OPERATIONAL_TIME_STEP_SECONDS,
    timestampWithOffset,
    USE_LABELS,
    VehicleUseStatus,
    type VehicleUse,
    type VehicleUseRegisterFilters,
} from './vehicleUse';
import { getVehicleUse, listVehicleUseRegister } from './vehicleUseApi';
import { UsageChargePanel } from './UsageChargePanel';
import { RunningChartHistoryPanel } from './RunningChartHistoryPanel';
import { RunningChartEditor, type InitialDriverState } from './RunningChartEditor';
import { ReassignDriverModal } from './ReassignDriverModal';

export default function RunningChartRegisterPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, CHART_PERMISSION.manage);
    const canFinalize = hasPermission(auth, CHART_PERMISSION.finalize);
    const canReverse = hasPermission(auth, CHART_PERMISSION.reverse);

    const [search, setSearch] = useState(() => {
        if (typeof window === 'undefined') return '';
        return new URLSearchParams(window.location.search).get('search') ?? '';
    });
    const [status, setStatus] = useState<RunningChartStatus | ''>(() => {
        if (typeof window === 'undefined') return '';
        const s = new URLSearchParams(window.location.search).get('status') ?? new URLSearchParams(window.location.search).get('chart_status');
        return Object.values(RunningChartStatus).includes(s as RunningChartStatus) ? (s as RunningChartStatus) : '';
    });
    const [kind, setKind] = useState<'customer' | 'owner' | ''>(() => {
        if (typeof window === 'undefined') return '';
        const k = new URLSearchParams(window.location.search).get('kind');
        return k === 'customer' || k === 'owner' ? k : '';
    });
    const [from, setFrom] = useState(() => {
        if (typeof window === 'undefined') return '';
        return new URLSearchParams(window.location.search).get('from') ?? '';
    });
    const [until, setUntil] = useState(() => {
        if (typeof window === 'undefined') return '';
        return new URLSearchParams(window.location.search).get('until') ?? '';
    });
    const [filters, setFilters] = useState<ChartRegisterFilters>(() => {
        if (typeof window === 'undefined') return {};
        const params = new URLSearchParams(window.location.search);
        const searchParam = params.get('search')?.trim();
        const statusParam = params.get('status') ?? params.get('chart_status');
        const kindParam = params.get('kind');
        const fromParam = params.get('from');
        const untilParam = params.get('until');
        const initial: ChartRegisterFilters = {};
        if (searchParam) initial.search = searchParam;
        if (statusParam && Object.values(RunningChartStatus).includes(statusParam as RunningChartStatus)) {
            initial.chart_status = statusParam as RunningChartStatus;
        }
        if (kindParam === 'customer' || kindParam === 'owner') {
            initial.kind = kindParam;
        }
        if (fromParam) {
            try { initial.from = timestampWithOffset(fromParam); } catch {}
        }
        if (untilParam) {
            try { initial.until = timestampWithOffset(untilParam); } catch {}
        }
        return initial;
    });

    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [rows, setRows] = useState<ChartRegisterRow[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [selected, setSelected] = useState<number | null>(null);
    const [history, setHistory] = useState<number | null>(null);
    const [printing, setPrinting] = useState<{ id: number; mode: 'lessee' | 'owner' } | null>(null);

    // Interactive CRUD modal states
    const [editor, setEditor] = useState<{
        use: VehicleUse;
        chart?: RunningChart;
        correction?: boolean;
        initialOdometer?: string;
        initialStartTime?: string;
        initialDriver?: InitialDriverState;
    } | null>(null);
    const [action, setAction] = useState<{ chart: RunningChart; type: RunningChartAction; use?: VehicleUse } | null>(null);
    const [reason, setReason] = useState('');
    const [reassignChart, setReassignChart] = useState<RunningChart | null>(null);

    // Quick vehicle selection for new running chart creation
    const [recording, setRecording] = useState(false);
    const [vehicleStatus, setVehicleStatus] = useState<string>(VehicleUseStatus.InCustody);
    const [vehicleSearch, setVehicleSearch] = useState<string>('');
    const [custodyUses, setCustodyUses] = useState<VehicleUse[]>([]);
    const [loadingCustody, setLoadingCustody] = useState(false);
    const [selectedCustodyId, setSelectedCustodyId] = useState<number | ''>('');

    const [currentTime] = useState(() => Date.now());

    useEffect(() => {
        const controller = new AbortController();
        listChartRegister(filters, page, controller.signal).then(result => {
            if (!controller.signal.aborted) { setRows(result.data); setMeta(result.meta); setError(null); }
        }).catch(failure => { if (!controller.signal.aborted) { setRows([]); setMeta(undefined); setError(toApiError(failure)); } })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [filters, page, revision]);

    function reload() {
        setEditor(null);
        setAction(null);
        setHistory(null);
        setRecording(false);
        setLoading(true);
        setRevision(v => v + 1);
    }

    function syncUrl(nextSearch: string, nextStatus: string, nextFrom: string, nextUntil: string, nextKind: string) {
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        if (nextSearch.trim()) url.searchParams.set('search', nextSearch.trim());
        else url.searchParams.delete('search');
        if (nextStatus) url.searchParams.set('status', nextStatus);
        else url.searchParams.delete('status');
        url.searchParams.delete('chart_status');
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
                'Period end must follow period start.',
                422,
                'VALIDATION_FAILED',
                'validation_error',
                { until: ['Period end must follow period start.'] },
            ));
            return;
        }
        try {
            const next: ChartRegisterFilters = {
                search: search.trim() || undefined,
                chart_status: status || undefined,
                from: from ? timestampWithOffset(from) : undefined,
                until: until ? timestampWithOffset(until) : undefined,
                ...(kind ? { kind } : {}),
            };
            syncUrl(search, status, from, until, kind);
            setLoading(true);
            setError(null);
            setPage(1);
            setSelected(null);
            setHistory(null);
            setFilters(next);
        } catch (failure) {
            setError(toApiError(failure));
        }
    }

    function clearFilters() {
        const needsReload = error !== null || page !== 1 || Object.values(filters).some(value => value !== undefined);
        setSearch(''); setStatus(''); setKind(''); setFrom(''); setUntil(''); setSelected(null); setHistory(null); setError(null);
        syncUrl('', '', '', '', '');
        if (needsReload) { setLoading(true); setFilters({}); setPage(1); }
    }

    async function resolveVehicleUse(row: ChartRegisterRow): Promise<VehicleUse> {
        if (row.vehicle_use && (row.vehicle_use as unknown as VehicleUse).status) {
            return row.vehicle_use as unknown as VehicleUse;
        }
        return await getVehicleUse(row.vehicle_use.id);
    }

    async function handleEditDraft(row: ChartRegisterRow) {
        setError(null);
        try {
            const fullUse = await resolveVehicleUse(row);
            setEditor({ use: fullUse, chart: row });
        } catch (err) {
            setError(toApiError(err));
        }
    }

    async function handleCreateCorrection(row: ChartRegisterRow) {
        setError(null);
        try {
            const fullUse = await resolveVehicleUse(row);
            setEditor({ use: fullUse, chart: row, correction: true });
        } catch (err) {
            setError(toApiError(err));
        }
    }

    async function loadEligibleUses(statusFilter: string, searchQuery: string) {
        setLoadingCustody(true);
        try {
            const filters: VehicleUseRegisterFilters = {
                use_status: (statusFilter || undefined) as VehicleUseStatus | undefined,
                search: searchQuery.trim() || undefined,
            };
            const result = await listVehicleUseRegister(filters, 1);
            const valid = result.data.filter(u =>
                [VehicleUseStatus.InCustody, VehicleUseStatus.Returned, VehicleUseStatus.Planned].includes(u.status)
            );
            setCustodyUses(valid);
            if (valid.length === 1) {
                setSelectedCustodyId(valid[0].id);
            } else if (!valid.some(u => u.id === selectedCustodyId)) {
                setSelectedCustodyId('');
            }
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setLoadingCustody(false);
        }
    }

    async function handleStartRecording() {
        setRecording(true);
        setSelectedCustodyId('');
        setVehicleStatus(VehicleUseStatus.InCustody);
        setVehicleSearch('');
        setError(null);
        await loadEligibleUses(VehicleUseStatus.InCustody, '');
    }

    async function handleProceedWithVehicle() {
        const found = custodyUses.find(u => u.id === selectedCustodyId);
        if (found) {
            setRecording(false);
            setLoading(true);
            try {
                const chartsRes = await listCharts(found.id, 1);
                const recentCharts = chartsRes.data;
                const latestDriverChart = recentCharts.find(r => r.driver_identity_source !== null);
                const latestChartWithOdo = recentCharts.find(r => r.end_odometer !== null);
                const initialOdometer = latestChartWithOdo?.end_odometer ?? found.handover_odometer ?? undefined;
                const latestChart = recentCharts.find(r => r.ends_at !== null);
                const initialStartTime = latestChart ? localTimestampValue(latestChart.ends_at) : (found.handed_over_at ? localTimestampValue(found.handed_over_at) : undefined);
                const initialDriver: InitialDriverState | undefined = latestDriverChart ? {
                    source: latestDriverChart.driver_identity_source as DriverIdentitySource,
                    employee: latestDriverChart.driver?.source === DriverIdentitySource.Employee && latestDriverChart.driver.employee_id
                        ? { id: latestDriverChart.driver.employee_id, name: latestDriverChart.driver.name, code: latestDriverChart.driver.reference }
                        : null,
                    externalName: latestDriverChart.driver?.source === DriverIdentitySource.External ? latestDriverChart.driver.name : undefined,
                    externalReference: latestDriverChart.driver?.source === DriverIdentitySource.External ? latestDriverChart.driver.reference : undefined,
                } : undefined;

                setEditor({
                    use: found,
                    initialOdometer,
                    initialStartTime,
                    initialDriver,
                });
            } catch {
                setEditor({ use: found });
            } finally {
                setLoading(false);
            }
        }
    }

    function chooseAction(chart: RunningChart, type: RunningChartAction, use?: VehicleUse) {
        setAction({ chart, type, use });
        setReason('');
        setError(null);
    }

    async function submitAction(event: FormEvent) {
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
            if (action.use?.returned_at) {
                const returnMs = new Date(action.use.returned_at).getTime() + 60_000;
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

    async function handlePrint(row: ChartRegisterRow, mode: 'lessee' | 'owner') {
        const s = businessDateInputValue(new Date(row.starts_at));
        const e = row.ends_at ? businessDateInputValue(new Date(row.ends_at)) : s;
        setPrinting({ id: row.id, mode });
        setError(null);
        const preview = window.open('', '_blank');
        if (preview) {
            preview.document.title = 'Preparing running chart…';
            preview.document.body.textContent = 'Preparing running chart…';
        }
        try {
            const agreementId = (mode === 'owner' && row.owner_agreement?.id)
                ? row.owner_agreement.id
                : row.customer_agreement.id!;
            await exportRunningChartReport({
                agreement_id: agreementId,
                start_date: s,
                end_date: e,
                vehicle_use_id: row.vehicle_use.id,
                sheet_mode: mode,
            }, 'print', preview);
        } catch (err) {
            preview?.close();
            setError(toApiError(err));
        } finally {
            setPrinting(null);
        }
    }

    const statusOptions = Object.values(RunningChartStatus).map(value => ({ value, label: CHART_LABELS[value] }));
    const kindOptions = [
        { value: '', label: 'All supplies' },
        { value: 'customer', label: 'Company supply only' },
        { value: 'owner', label: 'Owner-supplied only' },
    ];

    return (
        <div className="space-y-5">
            <ContentHeader
                title="Running Chart register"
                description="Review recorded usage, driver identity, agreement context, and correction lineage."
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="secondary" disabled={loading || saving} onClick={reload}>
                            Reload
                        </Button>
                        {canManage && (
                            <Button disabled={loading || saving || !!editor || recording} onClick={handleStartRecording}>
                                Record Running Chart
                            </Button>
                        )}
                    </div>
                }
            />

            {recording && (
                <Panel title="Select vehicle dispatch for new running chart">
                    <div className="space-y-4">
                        <p className="text-sm text-slate-600">
                            Choose a vehicle dispatch to record its running chart facts. You can search by plate or filter between in-custody, recently returned, and planned dispatches.
                        </p>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Input
                                label="Search vehicle, customer, or agreement"
                                placeholder="Type plate, customer name, reference..."
                                value={vehicleSearch}
                                onChange={e => {
                                    const next = e.target.value;
                                    setVehicleSearch(next);
                                    void loadEligibleUses(vehicleStatus, next);
                                }}
                            />
                            <Select
                                label="Dispatch status"
                                value={vehicleStatus}
                                options={[
                                    { value: VehicleUseStatus.InCustody, label: 'In custody only (active rentals)' },
                                    { value: VehicleUseStatus.Returned, label: 'Returned (completed dispatches)' },
                                    { value: VehicleUseStatus.Planned, label: 'Planned (upcoming dispatches)' },
                                    { value: '', label: 'All active & custody dispatches' },
                                ]}
                                onChange={e => {
                                    const next = e.target.value;
                                    setVehicleStatus(next);
                                    void loadEligibleUses(next, vehicleSearch);
                                }}
                            />
                        </div>

                        {loadingCustody ? (
                            <LoadingState label="Loading matching vehicle dispatches…" />
                        ) : custodyUses.length === 0 ? (
                            <p className="text-sm text-slate-500">
                                No matching vehicle dispatches found. Try adjusting your search query or selecting a different dispatch status.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                <Select
                                    label="Vehicle dispatch"
                                    value={selectedCustodyId}
                                    onChange={e => setSelectedCustodyId(e.target.value ? Number(e.target.value) : '')}
                                    options={custodyUses.map(u => ({
                                        value: u.id,
                                        label: `${u.vehicle.label} · Customer: ${u.customer_agreement.party_name} (${u.customer_agreement.reference}) [${USE_LABELS[u.status]}]${u.owner_agreement ? ` · Owner: ${u.owner_agreement.party_name}` : ' (Company supply)'}`,
                                    }))}
                                />
                                <div className="flex flex-wrap gap-2">
                                    <Button disabled={!selectedCustodyId} onClick={handleProceedWithVehicle}>
                                        Proceed with selected vehicle
                                    </Button>
                                    <Button variant="secondary" onClick={() => setRecording(false)}>
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </Panel>
            )}

            {editor && (
                <RunningChartEditor
                    use={editor.use}
                    chart={editor.chart}
                    correction={editor.correction}
                    initialOdometer={editor.initialOdometer}
                    initialStartTime={editor.initialStartTime}
                    initialDriver={editor.initialDriver}
                    onSaved={reload}
                    onCancel={() => setEditor(null)}
                />
            )}

            {action && (
                <form onSubmit={submitAction} aria-label="Confirm chart action" className="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p className="text-sm text-slate-700">
                        {action.type === RunningChartAction.Finalize
                            ? 'Finalize physical evidence. This locks physical facts as immutable evidence and enables usage billing.'
                            : 'Reverse physical evidence and retain its original history.'}{' '}
                        · {action.chart.reference}
                    </p>
                    {action.type === RunningChartAction.Reverse && (
                        <Textarea
                            label="Reversal reason"
                            required
                            value={reason}
                            onChange={e => setReason(e.target.value)}
                            error={error?.fields.reason?.[0]}
                        />
                    )}
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="submit"
                            variant={action.type === RunningChartAction.Reverse ? 'danger' : 'primary'}
                            loading={saving}
                            disabled={action.type === RunningChartAction.Reverse && !reason.trim()}
                        >
                            {CHART_ACTION_CONFIRM_LABELS[action.type]}
                        </Button>
                        <Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>
                            Cancel
                        </Button>
                    </div>
                </form>
            )}

            <Panel title="Filters">
                <form aria-label="Filter Running Charts" onSubmit={apply} className="grid gap-3 md:grid-cols-2">
                    <Input
                        label="Chart, vehicle, driver, agreement or party"
                        value={search}
                        onChange={event => setSearch(event.target.value)}
                        error={error?.fields.search?.[0]}
                    />
                    <Select
                        label="Chart status"
                        placeholder="All states"
                        options={statusOptions}
                        value={status}
                        onChange={event => setStatus(event.target.value as RunningChartStatus | '')}
                    />
                    <Select
                        label="Commercial side"
                        options={kindOptions}
                        value={kind}
                        onChange={event => setKind(event.target.value as 'customer' | 'owner' | '')}
                    />
                    <div className="hidden md:block" />
                    <Input
                        label="Period start (optional)"
                        type="datetime-local"
                        step={OPERATIONAL_TIME_STEP_SECONDS}
                        value={from}
                        onChange={event => setFrom(event.target.value)}
                        error={error?.fields.from?.[0]}
                    />
                    <Input
                        label="Period end (optional)"
                        type="datetime-local"
                        step={OPERATIONAL_TIME_STEP_SECONDS}
                        value={until}
                        onChange={event => setUntil(event.target.value)}
                        error={error?.fields.until?.[0]}
                    />
                    <p className="text-sm text-slate-600 md:col-span-2">
                        Times use {operationalTimeZoneLabel()}. Results include charts overlapping the period and show their full recorded quantities. No quantities are prorated.
                    </p>
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit" loading={loading}>
                            Apply filters
                        </Button>
                        <Button type="button" variant="secondary" disabled={loading} onClick={clearFilters}>
                            Clear filters
                        </Button>
                    </div>
                </form>
            </Panel>

            <ErrorAlert error={error} inline />

            {loading ? (
                <LoadingState label="Loading Running Chart register…" />
            ) : error ? null : (
                <>
                    {rows.length === 0 && (
                        <p className="rounded-xl border border-slate-200 bg-white p-5 text-slate-500 shadow-sm">
                            No Running Charts match these filters.
                        </p>
                    )}
                    {rows.map(row => {
                        const nowGraceMs = currentTime + 60_000;
                        const startMs = new Date(row.starts_at).getTime();
                        const endMs = new Date(row.ends_at).getTime();
                        const isUpcoming = startMs > nowGraceMs;
                        const isOngoing = !isUpcoming && endMs > nowGraceMs;
                        const isCompleted = !isUpcoming && !isOngoing;
                        const returnMs = row.vehicle_use?.returned_at ? new Date(row.vehicle_use.returned_at).getTime() + 60_000 : null;
                        const exceedsReturn = returnMs !== null && endMs > returnMs;

                        return (
                            <article key={row.id} className="space-y-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="text-base font-semibold text-slate-900">
                                            {row.reference} · {row.vehicle_use.vehicle_label}
                                        </h2>
                                        <StatusBadge status={row.status} />
                                        {row.status === RunningChartStatus.Draft && (
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
                                </div>

                                <div className="grid grid-cols-1 gap-1 text-sm text-slate-700 sm:grid-cols-2">
                                    <p>
                                        Customer: {row.customer_agreement.party_name} ·{' '}
                                        <Link
                                            to={`/vehicle-rental/customer/agreements?search=${encodeURIComponent(row.customer_agreement.reference)}`}
                                            className="font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                            title="Review customer agreement"
                                        >
                                            {row.customer_agreement.reference}
                                        </Link>
                                    </p>
                                    <p>
                                        {row.owner_agreement ? (
                                            <>
                                                Owner: {row.owner_agreement.party_name} ·{' '}
                                                <Link
                                                    to={`/vehicle-rental/owner/agreements?search=${encodeURIComponent(row.owner_agreement.reference)}`}
                                                    className="font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                                    title="Review owner agreement"
                                                >
                                                    {row.owner_agreement.reference}
                                                </Link>
                                            </>
                                        ) : (
                                            'Company supply'
                                        )}
                                    </p>
                                    <p>Driver: {row.driver ? `${row.driver.name} · ${row.driver.reference}` : 'Not recorded'}</p>
                                    <p>{formatBusinessDateTime(row.starts_at)} — {formatBusinessDateTime(row.ends_at)}</p>
                                    <p className="sm:col-span-2">
                                        Total distance: {row.total_km === null ? 'Not recorded' : <><QuantityDisplay value={row.total_km} /> km</>}
                                    </p>
                                    {row.normal_ot_minutes !== null && row.normal_ot_minutes > 0 && (
                                        <p className="sm:col-span-2 text-indigo-700 font-medium">
                                            Normal Overtime: {row.normal_ot_minutes} mins ({formatMinutes(row.normal_ot_minutes)})
                                        </p>
                                    )}
                                </div>

                                {row.replaces_vehicle && (
                                    <p className="text-sm font-medium text-amber-700">Replaces vehicle {row.replaces_vehicle}</p>
                                )}
                                {row.corrects_chart && (
                                    <p className="text-sm font-medium text-blue-600">Corrects chart {row.corrects_chart.reference}</p>
                                )}
                                {row.corrected_by_chart && (
                                    <p className="text-sm font-medium text-amber-700">Corrected by chart {row.corrected_by_chart.reference}</p>
                                )}

                                <div className="flex flex-wrap gap-2 pt-1">
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        aria-expanded={selected === row.id}
                                        onClick={() => setSelected(selected === row.id ? null : row.id)}
                                    >
                                        {selected === row.id ? 'Hide details' : 'Review ' + row.reference}
                                    </Button>

                                    {row.customer_agreement.id && (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            disabled={printing !== null}
                                            loading={printing?.id === row.id && printing.mode === 'lessee'}
                                            onClick={() => void handlePrint(row, 'lessee')}
                                        >
                                            Print Sheet
                                        </Button>
                                    )}

                                    {row.owner_agreement !== null && (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            disabled={printing !== null}
                                            loading={printing?.id === row.id && printing.mode === 'owner'}
                                            onClick={() => void handlePrint(row, 'owner')}
                                        >
                                            Print Owner Sheet
                                        </Button>
                                    )}

                                    {!editor && !action && (
                                        <>
                                            {canManage && row.status === RunningChartStatus.Draft && (
                                                <Button onClick={() => void handleEditDraft(row)}>
                                                    Edit draft
                                                </Button>
                                            )}
                                            {canFinalize && row.status === RunningChartStatus.Draft && (
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
                                                    onClick={async () => {
                                                        const fullUse = await resolveVehicleUse(row);
                                                        chooseAction(row, RunningChartAction.Finalize, fullUse);
                                                    }}
                                                >
                                                    Finalize usage
                                                </Button>
                                            )}
                                            {canReverse && row.status === RunningChartStatus.Finalized && (
                                                <Button
                                                    variant="danger"
                                                    onClick={() => chooseAction(row, RunningChartAction.Reverse)}
                                                >
                                                    Reverse usage
                                                </Button>
                                            )}
                                            {canManage && row.status === RunningChartStatus.Reversed && !row.corrected_by_chart && (
                                                <Button onClick={() => void handleCreateCorrection(row)}>
                                                    Create correction
                                                </Button>
                                            )}
                                            {canManage && row.status !== RunningChartStatus.Reversed && (
                                                <Button variant="secondary" onClick={() => setReassignChart(row)}>
                                                    Reassign driver
                                                </Button>
                                            )}
                                        </>
                                    )}
                                </div>

                                {selected === row.id && (
                                    <div className="space-y-3 border-t border-slate-200 pt-3">
                                        <dl className="grid gap-3 sm:grid-cols-2">
                                            {Object.entries(DISTANCE_LABELS).map(([key, label]) => (
                                                <div key={key} className="rounded-lg bg-slate-50 p-2.5">
                                                    <dt className="text-sm text-slate-500">{label}</dt>
                                                    <dd className="mt-1 font-medium text-slate-900">
                                                        {row[key as keyof typeof DISTANCE_LABELS] === null ? 'Not recorded' : <QuantityDisplay value={row[key as keyof typeof DISTANCE_LABELS] as string} />}
                                                    </dd>
                                                </div>
                                            ))}
                                            {Object.entries(COUNT_LABELS).map(([key, label]) => {
                                                const rawVal = row[key as keyof typeof COUNT_LABELS];
                                                const isOt = key.includes('ot_minutes');
                                                return (
                                                    <div key={key} className="rounded-lg bg-slate-50 p-2.5">
                                                        <dt className="text-sm text-slate-500">{label}</dt>
                                                        <dd className="mt-1 font-medium text-slate-900">
                                                            {rawVal === null ? (
                                                                'Not recorded'
                                                            ) : isOt && typeof rawVal === 'number' && rawVal > 0 ? (
                                                                <span>
                                                                    <QuantityDisplay value={rawVal} precision={0} /> mins{' '}
                                                                    <span className="text-xs text-slate-500 font-normal">
                                                                        ({formatMinutes(rawVal)})
                                                                    </span>
                                                                </span>
                                                            ) : (
                                                                <QuantityDisplay value={rawVal as number} precision={0} />
                                                            )}
                                                        </dd>
                                                    </div>
                                                );
                                            })}
                                        </dl>
                                        <div className="grid grid-cols-1 gap-1 text-sm text-slate-700 sm:grid-cols-2">
                                            <p>Air conditioning: {row.ac_mode === null ? 'Not recorded' : AC_LABELS[row.ac_mode]}</p>
                                            <p>Authoritative driver: {row.driver ? `${row.driver.name} (${row.driver.source === 'employee' ? 'employee' : 'external'}) · ${row.driver.reference}` : 'Not recorded'}</p>
                                            <p className="sm:col-span-2">Driver observation: {row.driver_observation ?? 'Not recorded'}</p>
                                            <p className="sm:col-span-2">Notes: {row.notes ?? 'Not recorded'}</p>
                                        </div>
                                        <div className="pt-2">
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                aria-expanded={history === row.id}
                                                onClick={() => setHistory(history === row.id ? null : row.id)}
                                            >
                                                {history === row.id ? 'Hide chart history' : 'Chart history'}
                                            </Button>
                                        </div>
                                        {history === row.id && <RunningChartHistoryPanel key={row.id} id={row.id} />}
                                        <UsageChargePanel chart={row} hasOwner={row.owner_agreement !== null} />
                                    </div>
                                )}
                            </article>
                        );
                    })}
                    <Pagination
                        meta={meta}
                        onPageChange={next => {
                            setLoading(true);
                            setSelected(null);
                            setHistory(null);
                            setPage(next);
                        }}
                    />
                </>
            )}

            {reassignChart && (
                <ReassignDriverModal
                    open={Boolean(reassignChart)}
                    chart={reassignChart}
                    onClose={() => setReassignChart(null)}
                    onSaved={reload}
                />
            )}
        </div>
    );
}
