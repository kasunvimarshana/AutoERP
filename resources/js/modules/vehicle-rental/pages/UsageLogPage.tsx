import { useCallback, useEffect, useMemo, useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { Drawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { useAuth } from '@/modules/auth/AuthProvider';
import { searchEmployees } from '@/modules/hr/hrApi';
import type { EmployeeSummary } from '@/modules/hr/hrTypes';
import type { LookupLoadParams } from '@/shared/types/lookup';
import type { PaginationMeta } from '@/shared/types/pagination';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import {
    changeRunningChartTripStatus,
    createRunningChartTrip,
    deleteRunningChartTrip,
    getRunningChartContext,
    listRunningChartAgreements,
    listRunningChartTrips,
    previewRunningChart,
    updateRunningChartTrip,
} from '../vehicleRentalApi';
import type {
    RentalUsageLog,
    RunningChartAgreementOption,
    RunningChartMode,
    RunningChartPreview,
    RunningChartTripPayload,
} from '../vehicleRentalTypes';

const today = businessDateInputValue;
const modes: Array<{ value: RunningChartMode; label: string }> = [
    { value: 'lessee', label: 'Lessee Running Charts - Customer' },
    { value: 'lessor', label: 'Lessor Running Charts - Owner / Supplier' },
    { value: 'linked', label: 'Linked Running Charts - Customer + Owner' },
];
const optionKey = (option: RunningChartAgreementOption) => `${option.agreement_id}:${option.agreement_vehicle_id}`;
const editableStatuses = ['local', 'draft', 'rejected'];

interface DraftTrip {
    localId: string;
    savedId?: number;
    status: 'local' | RentalUsageLog['status'];
    start_time: string;
    end_time: string;
    start_odometer: string;
    end_odometer: string;
    route: string;
    purpose: string;
    remarks: string;
    driver: EmployeeSummary | null;
    driverId?: number | null;
    driverName?: string | null;
    ot_hours: string;
    ot_type: 'overtime' | 'double_overtime';
    night_out: boolean;
    pass_allowance: string;
    waiting_hours: string;
    outstation: boolean;
    weekend: boolean;
    holiday: boolean;
    day_out: boolean;
    dirty: boolean;
}

export default function UsageLogPage() {
    const routeAgreementId = Number(useParams().id) || null;
    const [searchParams, setSearchParams] = useSearchParams();
    const requestedMode = modeFromValue(searchParams.get('mode')) ?? 'lessee';
    const requestedAgreementId = Number(searchParams.get('agreement_id')) || routeAgreementId;
    const auth = useAuth();

    const [mode, setMode] = useState<RunningChartMode>(requestedMode);
    const [usageDate, setUsageDate] = useState(today());
    const [lesseeSearch, setLesseeSearch] = useState('');
    const [lessorSearch, setLessorSearch] = useState('');
    const [lesseePage, setLesseePage] = useState(1);
    const [lessorPage, setLessorPage] = useState(1);
    const [lessee, setLessee] = useState<RunningChartAgreementOption | null>(null);
    const [lessor, setLessor] = useState<RunningChartAgreementOption | null>(null);
    const [trips, setTrips] = useState<DraftTrip[]>([]);
    const [drawerTripId, setDrawerTripId] = useState<string | null>(null);
    const [preview, setPreview] = useState<RunningChartPreview | null>(null);
    const [noUsageMarked, setNoUsageMarked] = useState(false);
    const [busyAction, setBusyAction] = useState<string | null>(null);
    const [error, setError] = useState<ApiError | null>(null);

    const debouncedLesseeSearch = useDebounce(lesseeSearch);
    const debouncedLessorSearch = useDebounce(lessorSearch);
    const searchDriver = useCallback((params: LookupLoadParams) => searchEmployees(params), []);
    const superAdmin = auth.user?.roles?.includes('Super Admin');
    const canRecordUsage = superAdmin || auth.user?.permissions?.includes('vehicle-rental.usage.record');
    const canApproveUsage = superAdmin || auth.user?.permissions?.includes('vehicle-rental.usage.approve');
    const hasUnsavedTrips = trips.some((trip) => trip.dirty || trip.status === 'local');
    useUnsavedChanges(hasUnsavedTrips && !busyAction);

    const lesseeOptions = useApi(
        (signal) => listRunningChartAgreements({
            mode,
            side: 'lessee',
            search: debouncedLesseeSearch || undefined,
            agreement_id: mode !== 'lessor' ? requestedAgreementId ?? undefined : undefined,
            page: lesseePage,
            per_page: 20,
        }, signal),
        [mode, debouncedLesseeSearch, requestedAgreementId, lesseePage],
        mode !== 'lessor',
        true,
    );
    const lessorOptions = useApi(
        (signal) => listRunningChartAgreements({
            mode,
            side: 'lessor',
            search: debouncedLessorSearch || undefined,
            agreement_id: mode === 'lessor' ? requestedAgreementId ?? undefined : undefined,
            vehicle_id: mode === 'linked' ? lessee?.vehicle_id : undefined,
            page: lessorPage,
            per_page: 20,
        }, signal),
        [mode, debouncedLessorSearch, requestedAgreementId, lessee?.vehicle_id, lessorPage],
        mode !== 'lessee',
        true,
    );

    useEffect(() => {
        const next = modeFromValue(searchParams.get('mode')) ?? 'lessee';
        setMode(next);
    }, [searchParams]);

    useEffect(() => {
        if (searchParams.get('mode') === mode) return;
        const params = new URLSearchParams(searchParams);
        params.set('mode', mode);
        setSearchParams(params, { replace: true });
    }, [mode, searchParams, setSearchParams]);

    useEffect(() => {
        if (lessee || mode === 'lessor' || !lesseeOptions.data?.data.length) return;
        const initial = lesseeOptions.data.data.find((row) => row.agreement_id === requestedAgreementId)
            ?? lesseeOptions.data.data[0];
        setLessee(initial);
        setUsageDate(dateInsideOption(initial));
    }, [lessee, lesseeOptions.data, mode, requestedAgreementId]);

    useEffect(() => {
        if (lessor || mode === 'lessee' || !lessorOptions.data?.data.length) return;
        const initial = lessorOptions.data.data.find((row) => row.agreement_id === requestedAgreementId)
            ?? lessorOptions.data.data[0];
        setLessor(initial);
        if (mode === 'lessor') setUsageDate(dateInsideOption(initial));
    }, [lessor, lessorOptions.data, mode, requestedAgreementId]);

    useEffect(() => {
        if (mode !== 'linked' || !lessee || !lessor) return;
        if (lessee.vehicle_id !== lessor.vehicle_id) setLessor(null);
    }, [lessee, lessor, mode]);

    const selection = useMemo(() => buildSelection(mode, lessee, lessor, usageDate), [mode, lessee, lessor, usageDate]);
    const selectionReady = Boolean(selection);
    const context = useApi(
        (signal) => getRunningChartContext(selection!, signal),
        [selection?.mode, selection?.usage_date, selection?.lessee_agreement_id, selection?.lessee_agreement_vehicle_id, selection?.lessor_agreement_id, selection?.lessor_agreement_vehicle_id],
        selectionReady,
        true,
    );
    const savedTrips = useApi(
        (signal) => listRunningChartTrips(selection!, signal),
        [selection?.mode, selection?.usage_date, selection?.lessee_agreement_id, selection?.lessee_agreement_vehicle_id, selection?.lessor_agreement_id, selection?.lessor_agreement_vehicle_id],
        selectionReady,
        true,
    );

    useEffect(() => {
        setTrips([]);
        setPreview(null);
        setNoUsageMarked(false);
        setDrawerTripId(null);
        setError(null);
    }, [mode, usageDate, lessee?.agreement_vehicle_id, lessor?.agreement_vehicle_id]);

    useEffect(() => {
        if (!savedTrips.data) return;
        setTrips(savedTrips.data.map(tripFromLog));
    }, [savedTrips.data]);

    const selectedDrawerTrip = trips.find((trip) => trip.localId === drawerTripId) ?? null;
    const displayError = error ?? lesseeOptions.error ?? lessorOptions.error ?? context.error ?? savedTrips.error;

    const addTrip = () => {
        const resolvedContext = context.data;
        if (!resolvedContext) return;
        const previous = trips.at(-1);
        setNoUsageMarked(false);
        setPreview(null);
        setTrips((current) => [...current, blankTrip(
            previous?.end_odometer || resolvedContext.last_valid_finish_odometer,
            previous?.end_time || '',
        )]);
    };

    const copyPreviousTrip = () => {
        const previous = trips.at(-1);
        if (!previous) {
            addTrip();
            return;
        }
        setNoUsageMarked(false);
        setPreview(null);
        setTrips((current) => [...current, {
            ...previous,
            localId: crypto.randomUUID(),
            savedId: undefined,
            status: 'local',
            start_time: previous.end_time,
            end_time: '',
            start_odometer: previous.end_odometer,
            end_odometer: '',
            dirty: true,
        }]);
    };

    const saveDrafts = async (): Promise<RentalUsageLog[]> => {
        if (!selection || !canRecordUsage || noUsageMarked) return [];
        setBusyAction('save');
        setError(null);
        try {
            const saved: RentalUsageLog[] = [];
            for (const trip of trips) {
                if (!editableStatuses.includes(trip.status) || (!trip.dirty && trip.savedId)) continue;
                const payload = payloadFromTrip(selection, trip);
                const row = trip.savedId
                    ? await updateRunningChartTrip(trip.savedId, payload)
                    : await createRunningChartTrip(payload);
                saved.push(row);
            }
            savedTrips.reload();
            setPreview(null);
            return saved;
        } catch (requestError) {
            setError(toApiError(requestError));
            return [];
        } finally {
            setBusyAction(null);
        }
    };

    const previewTrips = async () => {
        if (!selection) return;
        setBusyAction('preview');
        setError(null);
        try {
            setPreview(await previewRunningChart({
                ...selection,
                trips: noUsageMarked ? [] : trips.map((trip) => payloadFromTrip(selection, trip)),
            }));
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusyAction(null);
        }
    };

    const submitTrips = async () => {
        if (!selection || !canRecordUsage || noUsageMarked) return;
        setBusyAction('submit');
        setError(null);
        try {
            const saved = await saveDrafts();
            const ids = saved.length > 0
                ? saved.map((trip) => trip.id)
                : trips.filter((trip) => trip.savedId && ['draft', 'rejected'].includes(trip.status)).map((trip) => trip.savedId!);
            for (const id of ids) {
                await changeRunningChartTripStatus(id, 'submit');
            }
            savedTrips.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusyAction(null);
        }
    };

    const changeStatus = async (trip: DraftTrip, status: 'approve' | 'reject') => {
        if (!trip.savedId || !canApproveUsage) return;
        setBusyAction(`${status}-${trip.savedId}`);
        setError(null);
        try {
            await changeRunningChartTripStatus(trip.savedId, status);
            savedTrips.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusyAction(null);
        }
    };

    const deleteTrip = async (trip: DraftTrip) => {
        if (!canRecordUsage || !editableStatuses.includes(trip.status)) return;
        setBusyAction(`delete-${trip.localId}`);
        setError(null);
        try {
            if (trip.savedId) await deleteRunningChartTrip(trip.savedId);
            setTrips((current) => current.filter((row) => row.localId !== trip.localId));
            savedTrips.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusyAction(null);
        }
    };

    return (
        <>
            <ContentHeader title={modes.find((item) => item.value === mode)?.label ?? 'Running Charts'} description="Daily rental usage is entered once per physical trip and resolved into the selected financial context." />
            <ErrorAlert error={displayError} />
            <div className="space-y-5">
                <Panel title="Daily running chart">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Select
                            label="Mode"
                            value={mode}
                            options={modes}
                            onChange={(event) => {
                                const next = modeFromValue(event.target.value) ?? 'lessee';
                                setMode(next);
                                setLessee(null);
                                setLessor(null);
                            }}
                        />
                        <Input label="Usage date" type="date" value={usageDate} onChange={(event) => setUsageDate(event.target.value)} />
                        {mode !== 'lessor' && (
                            <Input label="Search customer agreements" value={lesseeSearch} onChange={(event) => {
                                setLesseeSearch(event.target.value);
                                setLesseePage(1);
                            }} />
                        )}
                        {mode !== 'lessee' && (
                            <Input label="Search owner / supplier agreements" value={lessorSearch} onChange={(event) => {
                                setLessorSearch(event.target.value);
                                setLessorPage(1);
                            }} />
                        )}
                    </div>
                    <div className="mt-4 grid gap-4 lg:grid-cols-2">
                        {mode !== 'lessor' && (
                            <AgreementPicker
                                title="Lessee agreement"
                                value={lessee}
                                rows={withSelected(lesseeOptions.data?.data ?? [], lessee)}
                                loading={lesseeOptions.loading}
                                meta={lesseeOptions.data?.meta}
                                onPageChange={setLesseePage}
                                onChange={(row) => {
                                    setLessee(row);
                                    if (row) setUsageDate(dateInsideOption(row));
                                }}
                            />
                        )}
                        {mode !== 'lessee' && (
                            <AgreementPicker
                                title="Lessor agreement"
                                value={lessor}
                                rows={withSelected(lessorOptions.data?.data ?? [], lessor)}
                                loading={lessorOptions.loading}
                                meta={lessorOptions.data?.meta}
                                onPageChange={setLessorPage}
                                onChange={(row) => {
                                    setLessor(row);
                                    if (row && mode === 'lessor') setUsageDate(dateInsideOption(row));
                                }}
                            />
                        )}
                    </div>
                </Panel>

                {context.loading && selectionReady && <LoadingState label="Resolving running chart context..." />}
                {context.data && (
                    <Panel title="Resolved context">
                        <DetailGrid items={[
                            { label: 'Vehicle', value: context.data.vehicle?.registration_number ?? context.data.vehicle?.vehicle_number ?? context.data.vehicle_id },
                            { label: 'Billing period', value: context.data.contexts.map((row) => `${row.agreement_number}: ${row.billing_cycle.replaceAll('_', ' ')}`).join(' / ') },
                            { label: 'Previous approved odometer', value: context.data.last_valid_finish_odometer },
                            { label: 'Approved cumulative mileage', value: context.data.approved_cumulative_mileage },
                            { label: 'Allocation link', value: context.data.agreement_vehicle_link_id ? `#${context.data.agreement_vehicle_link_id}` : '-' },
                        ]} />
                        <div className="mt-4 grid gap-4 lg:grid-cols-2">
                            {context.data.contexts.map((row) => (
                                <div key={row.agreement_id} className="rounded-lg border border-slate-200 p-4">
                                    <div className="flex items-center justify-between gap-3">
                                        <strong>{row.financial_side === 'revenue' ? 'Customer side' : 'Owner / Supplier side'}</strong>
                                        <RentalStatusBadge status={row.financial_side} />
                                    </div>
                                    <DetailGrid items={[
                                        { label: 'Agreement', value: row.agreement_number },
                                        { label: 'Party', value: row.party_name ?? row.party_type },
                                        { label: 'Billing', value: row.billing_cycle.replaceAll('_', ' ') },
                                        { label: 'Base', value: `${row.rate_snapshot.base_rate} / ${row.rate_snapshot.rate_unit}` },
                                        { label: 'Allowed KM', value: row.rate_snapshot.allowed_km },
                                        { label: 'OT', value: row.rate_snapshot.overtime_rate },
                                    ]} />
                                </div>
                            ))}
                        </div>
                    </Panel>
                )}

                {context.data && (
                    <Panel title="Trip rows">
                        <div className="mb-4 flex flex-wrap gap-2">
                            <Button type="button" variant="secondary" disabled={!canRecordUsage || Boolean(busyAction)} onClick={addTrip}>Add Trip</Button>
                            <Button type="button" variant="secondary" disabled={!canRecordUsage || Boolean(busyAction)} onClick={copyPreviousTrip}>Copy Previous Trip</Button>
                            <Button type="button" variant="secondary" disabled={!canRecordUsage || Boolean(busyAction)} onClick={() => {
                                setTrips([]);
                                setNoUsageMarked(true);
                                setPreview(null);
                            }}>Mark No Usage</Button>
                            <Button type="button" variant="secondary" loading={busyAction === 'preview'} onClick={() => void previewTrips()}>Preview</Button>
                            <Button type="button" loading={busyAction === 'save'} disabled={!canRecordUsage || noUsageMarked} onClick={() => void saveDrafts()}>Save Draft</Button>
                            <Button type="button" loading={busyAction === 'submit'} disabled={!canRecordUsage || noUsageMarked} onClick={() => void submitTrips()}>Submit</Button>
                        </div>
                        {noUsageMarked && (
                            <p className="mb-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3 text-sm text-slate-600">
                                No usage is marked for this working date. No rental usage log will be stored unless a trip row is added.
                            </p>
                        )}
                        {savedTrips.loading ? <LoadingState label="Loading daily trips..." /> : (
                            <DataTable
                                rows={trips}
                                rowKey={(trip) => trip.localId}
                                emptyMessage="No trips recorded for this working date."
                                columns={[
                                    { key: 'time', header: 'Start / Finish', render: (trip) => editableInput(trip, 'time') },
                                    { key: 'odo', header: 'Start / Finish KM', render: (trip) => editableInput(trip, 'odometer') },
                                    { key: 'distance', header: 'Distance', render: (trip) => calculatedDistance(trip) },
                                    { key: 'hours', header: 'Working Hours', render: (trip) => calculatedHours(trip) },
                                    { key: 'route', header: 'Route', render: (trip) => editableInput(trip, 'route') },
                                    { key: 'purpose', header: 'Purpose', render: (trip) => editableInput(trip, 'purpose') },
                                    { key: 'ot', header: 'OT', render: (trip) => `${trip.ot_hours || '-'} ${trip.ot_hours ? trip.ot_type.replaceAll('_', ' ') : ''}` },
                                    { key: 'night', header: 'Night Out', render: (trip) => trip.night_out ? 'Yes' : '-' },
                                    { key: 'pass', header: 'Pass / Allowance', render: (trip) => trip.pass_allowance || '-' },
                                    { key: 'status', header: 'Status', render: (trip) => <RentalStatusBadge status={trip.status === 'local' ? 'draft' : trip.status} /> },
                                    { key: 'actions', header: '', render: (trip) => (
                                        <div className="flex flex-wrap justify-end gap-2">
                                            <Button type="button" variant="secondary" onClick={() => setDrawerTripId(trip.localId)}>Edit</Button>
                                            {editableStatuses.includes(trip.status) && canRecordUsage && (
                                                <Button type="button" variant="danger" loading={busyAction === `delete-${trip.localId}`} onClick={() => void deleteTrip(trip)}>Delete Draft Trip</Button>
                                            )}
                                            {trip.status === 'submitted' && canApproveUsage && (
                                                <>
                                                    <Button type="button" loading={busyAction === `approve-${trip.savedId}`} onClick={() => void changeStatus(trip, 'approve')}>Approve</Button>
                                                    <Button type="button" variant="secondary" loading={busyAction === `reject-${trip.savedId}`} onClick={() => void changeStatus(trip, 'reject')}>Reject</Button>
                                                </>
                                            )}
                                        </div>
                                    ) },
                                ]}
                            />
                        )}
                    </Panel>
                )}

                {preview && (
                    <Panel title="Preview">
                        <DetailGrid items={[
                            { label: 'Daily KM', value: preview.daily_km },
                            { label: 'Hours', value: preview.working_hours },
                            { label: 'OT', value: preview.overtime_hours },
                            { label: 'Customer revenue', value: preview.customer_revenue },
                            { label: 'Owner / supplier cost', value: preview.owner_cost },
                            { label: 'Estimated margin', value: preview.estimated_margin },
                        ]} />
                    </Panel>
                )}
            </div>

            <Drawer open={Boolean(selectedDrawerTrip)} title="Trip details" onClose={() => setDrawerTripId(null)}>
                {selectedDrawerTrip && (
                    <TripDrawer
                        trip={selectedDrawerTrip}
                        searchDriver={searchDriver}
                        onChange={(patch) => updateTripRow(selectedDrawerTrip.localId, patch)}
                    />
                )}
            </Drawer>
        </>
    );

    function editableInput(trip: DraftTrip, kind: 'time' | 'odometer' | 'route' | 'purpose') {
        const editable = editableStatuses.includes(trip.status) && canRecordUsage;
        if (kind === 'time') {
            return (
                <div className="grid min-w-36 gap-2">
                    <Input type="time" value={trip.start_time} disabled={!editable} onChange={(event) => updateTripRow(trip.localId, { start_time: event.target.value })} />
                    <Input type="time" value={trip.end_time} disabled={!editable} onChange={(event) => updateTripRow(trip.localId, { end_time: event.target.value })} />
                </div>
            );
        }
        if (kind === 'odometer') {
            return (
                <div className="grid min-w-40 gap-2">
                    <DecimalInput value={trip.start_odometer} disabled={!editable} onChange={(event) => updateTripRow(trip.localId, { start_odometer: event.target.value })} />
                    <DecimalInput value={trip.end_odometer} disabled={!editable} onChange={(event) => updateTripRow(trip.localId, { end_odometer: event.target.value })} />
                </div>
            );
        }
        return (
            <Input
                value={kind === 'route' ? trip.route : trip.purpose}
                disabled={!editable}
                onChange={(event) => updateTripRow(trip.localId, kind === 'route' ? { route: event.target.value } : { purpose: event.target.value })}
            />
        );
    }

    function updateTripRow(localId: string, patch: Partial<DraftTrip>) {
        setPreview(null);
        setNoUsageMarked(false);
        setTrips((current) => current.map((trip) => (
            trip.localId === localId ? { ...trip, ...patch, dirty: true } : trip
        )));
    }
}

function AgreementPicker({
    title,
    value,
    rows,
    loading,
    meta,
    onPageChange,
    onChange,
}: {
    title: string;
    value: RunningChartAgreementOption | null;
    rows: RunningChartAgreementOption[];
    loading: boolean;
    meta?: PaginationMeta;
    onPageChange: (page: number) => void;
    onChange: (row: RunningChartAgreementOption | null) => void;
}) {
    return (
        <div>
            <Select
                label={title}
                value={value ? optionKey(value) : ''}
                options={rows.map((row) => ({
                    value: optionKey(row),
                    label: `${row.agreement_number} / ${row.party_name ?? row.party_type} / ${row.vehicle_registration ?? row.vehicle_id}`,
                }))}
                onChange={(event) => onChange(rows.find((row) => optionKey(row) === event.target.value) ?? null)}
            />
            {loading && <LoadingState />}
            <Pagination meta={meta} onPageChange={onPageChange} />
        </div>
    );
}

function TripDrawer({
    trip,
    searchDriver,
    onChange,
}: {
    trip: DraftTrip;
    searchDriver: (params: LookupLoadParams) => ReturnType<typeof searchEmployees>;
    onChange: (patch: Partial<DraftTrip>) => void;
}) {
    const editable = editableStatuses.includes(trip.status);

    return (
        <div className="grid gap-4 md:grid-cols-2">
            <GenericLookupSelect
                label="Driver"
                value={trip.driver}
                disabled={!editable}
                onChange={(driver) => onChange({ driver, driverId: driver?.id ?? null, driverName: driver?.display_name ?? null })}
                search={searchDriver}
                formatLabel={(row) => `${row.employee_number} ${row.display_name}`}
            />
            {trip.driverName && !trip.driver && <p className="self-end text-sm text-slate-600">Current driver: {trip.driverName}</p>}
            <DecimalInput label="Waiting hours" value={trip.waiting_hours} disabled={!editable} onChange={(event) => onChange({ waiting_hours: event.target.value })} />
            <DecimalInput label="OT hours" value={trip.ot_hours} disabled={!editable} onChange={(event) => onChange({ ot_hours: event.target.value })} />
            <Select label="OT type" value={trip.ot_type} disabled={!editable} options={[
                { value: 'overtime', label: 'Overtime' },
                { value: 'double_overtime', label: 'Double overtime' },
            ]} onChange={(event) => onChange({ ot_type: event.target.value as DraftTrip['ot_type'] })} />
            <DecimalInput label="Pass / allowance" value={trip.pass_allowance} disabled={!editable} onChange={(event) => onChange({ pass_allowance: event.target.value })} />
            <Checkbox label="Outstation" checked={trip.outstation} disabled={!editable} onChange={(checked) => onChange({ outstation: checked })} />
            <Checkbox label="Weekend" checked={trip.weekend} disabled={!editable} onChange={(checked) => onChange({ weekend: checked })} />
            <Checkbox label="Holiday" checked={trip.holiday} disabled={!editable} onChange={(checked) => onChange({ holiday: checked })} />
            <Checkbox label="Day out" checked={trip.day_out} disabled={!editable} onChange={(checked) => onChange({ day_out: checked })} />
            <Checkbox label="Night out" checked={trip.night_out} disabled={!editable} onChange={(checked) => onChange({ night_out: checked })} />
            <div className="md:col-span-2">
                <Textarea label="Additional remarks" value={trip.remarks} disabled={!editable} onChange={(event) => onChange({ remarks: event.target.value })} />
            </div>
        </div>
    );
}

function Checkbox({ label, checked, disabled, onChange }: {
    label: string;
    checked: boolean;
    disabled?: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <label className="flex min-h-10 items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" className="h-4 w-4 rounded border-slate-300" checked={checked} disabled={disabled} onChange={(event) => onChange(event.target.checked)} />
            {label}
        </label>
    );
}

function modeFromValue(value: string | null): RunningChartMode | null {
    return value === 'lessee' || value === 'lessor' || value === 'linked' ? value : null;
}

function buildSelection(
    mode: RunningChartMode,
    lessee: RunningChartAgreementOption | null,
    lessor: RunningChartAgreementOption | null,
    usageDate: string,
) {
    if (mode === 'lessee' && lessee) {
        return {
            mode,
            usage_date: usageDate,
            lessee_agreement_id: lessee.agreement_id,
            lessee_agreement_vehicle_id: lessee.agreement_vehicle_id,
        };
    }
    if (mode === 'lessor' && lessor) {
        return {
            mode,
            usage_date: usageDate,
            lessor_agreement_id: lessor.agreement_id,
            lessor_agreement_vehicle_id: lessor.agreement_vehicle_id,
        };
    }
    if (mode === 'linked' && lessee && lessor) {
        return {
            mode,
            usage_date: usageDate,
            lessee_agreement_id: lessee.agreement_id,
            lessee_agreement_vehicle_id: lessee.agreement_vehicle_id,
            lessor_agreement_id: lessor.agreement_id,
            lessor_agreement_vehicle_id: lessor.agreement_vehicle_id,
        };
    }

    return null;
}

function payloadFromTrip(selection: NonNullable<ReturnType<typeof buildSelection>>, trip: DraftTrip): RunningChartTripPayload {
    return {
        ...selection,
        driver_id: trip.driver?.id ?? trip.driverId ?? undefined,
        start_time: trip.start_time,
        end_time: trip.end_time,
        start_odometer: trip.start_odometer,
        end_odometer: trip.end_odometer,
        trip_from: trip.route || undefined,
        trip_purpose: trip.purpose || undefined,
        remarks: trip.remarks || undefined,
        events: eventsFromTrip(trip),
    };
}

function eventsFromTrip(trip: DraftTrip) {
    const events: RunningChartTripPayload['events'] = [];
    if (positive(trip.ot_hours)) events.push({ event_type: trip.ot_type, quantity: trip.ot_hours });
    if (trip.night_out) events.push({ event_type: 'night_out', quantity: '1.000000' });
    if (positive(trip.pass_allowance)) events.push({ event_type: 'pass', quantity: trip.pass_allowance, remarks: 'Pass / allowance' });
    if (positive(trip.waiting_hours)) events.push({ event_type: 'waiting', quantity: trip.waiting_hours });
    if (trip.outstation) events.push({ event_type: 'outstation', quantity: '1.000000' });
    if (trip.weekend) events.push({ event_type: 'weekend', quantity: '1.000000' });
    if (trip.holiday) events.push({ event_type: 'holiday', quantity: '1.000000', remarks: trip.remarks || 'Holiday usage' });
    if (trip.day_out) events.push({ event_type: 'day_out', quantity: '1.000000' });

    return events;
}

function tripFromLog(log: RentalUsageLog): DraftTrip {
    const events = new Map(log.events.map((event) => [event.event_type, event.quantity]));

    return {
        localId: `saved-${log.id}`,
        savedId: log.id,
        status: log.status,
        start_time: log.start_time ?? '',
        end_time: log.end_time ?? '',
        start_odometer: log.start_odometer,
        end_odometer: log.end_odometer,
        route: log.trip_from ?? '',
        purpose: log.trip_purpose ?? '',
        remarks: log.remarks ?? '',
        driver: null,
        driverId: log.driver_id,
        driverName: log.driver?.name ?? null,
        ot_hours: events.get('overtime') ?? events.get('double_overtime') ?? '',
        ot_type: events.has('double_overtime') ? 'double_overtime' : 'overtime',
        night_out: events.has('night_out'),
        pass_allowance: events.get('pass') ?? '',
        waiting_hours: events.get('waiting') ?? '',
        outstation: events.has('outstation'),
        weekend: events.has('weekend'),
        holiday: events.has('holiday'),
        day_out: events.has('day_out'),
        dirty: false,
    };
}

function blankTrip(startOdometer: string, startTime: string): DraftTrip {
    return {
        localId: crypto.randomUUID(),
        status: 'local',
        start_time: startTime,
        end_time: '',
        start_odometer: startOdometer,
        end_odometer: '',
        route: '',
        purpose: '',
        remarks: '',
        driver: null,
        ot_hours: '',
        ot_type: 'overtime',
        night_out: false,
        pass_allowance: '',
        waiting_hours: '',
        outstation: false,
        weekend: false,
        holiday: false,
        day_out: false,
        dirty: true,
    };
}

function calculatedDistance(trip: DraftTrip): string {
    const start = Number(trip.start_odometer);
    const end = Number(trip.end_odometer);
    if (!Number.isFinite(start) || !Number.isFinite(end) || end < start) return '-';

    return (end - start).toFixed(3);
}

function calculatedHours(trip: DraftTrip): string {
    if (!trip.start_time || !trip.end_time) return '-';
    const [startHour, startMinute] = trip.start_time.split(':').map(Number);
    const [endHour, endMinute] = trip.end_time.split(':').map(Number);
    const start = startHour * 60 + startMinute;
    let end = endHour * 60 + endMinute;
    if (end < start) end += 24 * 60;
    const minutes = Math.max(0, end - start);

    return (minutes / 60).toFixed(2);
}

function positive(value: string): boolean {
    const parsed = Number(value);

    return Number.isFinite(parsed) && parsed > 0;
}

function withSelected(rows: RunningChartAgreementOption[], selected: RunningChartAgreementOption | null): RunningChartAgreementOption[] {
    if (!selected || rows.some((row) => optionKey(row) === optionKey(selected))) return rows;

    return [selected, ...rows];
}

function dateInsideOption(option: RunningChartAgreementOption): string {
    const current = today();
    const from = option.allocation_from.slice(0, 10);
    const to = (option.allocation_to ?? option.expected_end_at).slice(0, 10);

    return current >= from && current <= to ? current : from;
}
