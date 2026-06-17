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
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { ApiCollection } from '@/shared/types/api';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { compareDecimalStrings, isPositiveDecimal, subtractDecimal } from '@/shared/utils/decimal';
import { useAuth } from '@/modules/auth/AuthProvider';
import { searchEmployees } from '@/modules/hr/hrApi';
import type { EmployeeSummary } from '@/modules/hr/hrTypes';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import {
    changeRunningChartTripStatus,
    createRunningChartTrip,
    deleteRunningChartTrip,
    getRunningChartContext,
    listRunningChartAgreements,
    listRunningChartTrips,
    previewRunningChart,
    submitRunningChartDaily,
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
const unsavedRunningChartMessage = 'You have unsaved Running Chart data.\nDiscard the changes and continue?';
const editableStatuses = ['local', 'draft', 'rejected'];
const modeOptions: Array<{ value: RunningChartMode; label: string }> = [
    { value: 'lessee', label: 'Customer Running Charts' },
    { value: 'lessor', label: 'Owner / Supplier Running Charts' },
    { value: 'linked', label: 'Linked Running Charts' },
];
const modeHeadings: Record<RunningChartMode, string> = {
    lessee: 'Customer Running Charts — Lessee',
    lessor: 'Owner / Supplier Running Charts — Lessor',
    linked: 'Linked Running Charts — Customer + Owner',
};

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

interface RunningChartAgreementLookupOption extends RunningChartAgreementOption {
    id: number;
    name: string;
}

export default function UsageLogPage() {
    const routeAgreementId = positiveInteger(useParams().id);
    const [searchParams, setSearchParams] = useSearchParams();
    const mode = modeFromValue(searchParams.get('mode')) ?? 'lessee';
    const requestedAgreementId = positiveInteger(searchParams.get('agreement_id')) ?? routeAgreementId;

    const changeMode = useCallback((nextMode: RunningChartMode) => {
        const params = new URLSearchParams(searchParams);
        params.set('mode', nextMode);
        params.delete('agreement_id');
        setSearchParams(params);
    }, [searchParams, setSearchParams]);

    return (
        <>
            <ContentHeader title={modeHeadings[mode]} description="Daily rental usage is entered once per physical trip and resolved into the selected financial context." />
            <RunningChartWorkspace
                key={mode}
                mode={mode}
                requestedAgreementId={requestedAgreementId}
                onModeChange={changeMode}
            />
        </>
    );
}

function RunningChartWorkspace({
    mode,
    requestedAgreementId,
    onModeChange,
}: {
    mode: RunningChartMode;
    requestedAgreementId: number | null;
    onModeChange: (mode: RunningChartMode) => void;
}) {
    const auth = useAuth();
    const [usageDate, setUsageDate] = useState(today());
    const [lessee, setLessee] = useState<RunningChartAgreementLookupOption | null>(null);
    const [lessor, setLessor] = useState<RunningChartAgreementLookupOption | null>(null);
    const [trips, setTrips] = useState<DraftTrip[]>([]);
    const [drawerTripId, setDrawerTripId] = useState<string | null>(null);
    const [preview, setPreview] = useState<RunningChartPreview | null>(null);
    const [busyAction, setBusyAction] = useState<string | null>(null);
    const [error, setError] = useState<ApiError | null>(null);

    const searchDriver = useCallback((params: LookupLoadParams) => searchEmployees(params), []);
    const permissions = auth.permissions.length > 0 ? auth.permissions : auth.user?.permissions ?? [];
    const roles = auth.roles.length > 0 ? auth.roles : auth.user?.roles ?? [];
    const superAdmin = roles.includes('Super Admin');
    const hasPermission = useCallback((permission: string) => superAdmin || permissions.includes(permission), [permissions, superAdmin]);
    const canRecordUsage = hasPermission('vehicle-rental.usage.record');
    const canApproveUsage = hasPermission('vehicle-rental.usage.approve');
    const canClassifyHoliday = hasPermission('vehicle-rental.usage.classify-holiday');

    const hasUnsavedTrips = trips.some((trip) => trip.dirty || trip.status === 'local');
    const confirmDiscard = useUnsavedChanges(hasUnsavedTrips && !busyAction, unsavedRunningChartMessage);

    const lesseePreselection = useApi(
        (signal) => loadAgreementOptions({
            mode,
            side: 'lessee',
            agreementId: requestedAgreementId ?? undefined,
            usageDate,
            signal,
        }),
        [mode, requestedAgreementId, usageDate],
        requestedAgreementId !== null && mode !== 'lessor' && lessee === null,
        true,
    );
    const lessorPreselection = useApi(
        (signal) => loadAgreementOptions({
            mode,
            side: 'lessor',
            agreementId: requestedAgreementId ?? undefined,
            usageDate,
            signal,
        }),
        [mode, requestedAgreementId, usageDate],
        requestedAgreementId !== null && mode !== 'lessee' && lessor === null,
        true,
    );

    useEffect(() => {
        if (lessee || requestedAgreementId === null) return;

        const match = lesseePreselection.data?.data.find((row) => row.agreement_id === requestedAgreementId);
        if (match) setLessee(match);
    }, [lessee, lesseePreselection.data, requestedAgreementId]);

    useEffect(() => {
        if (lessor || requestedAgreementId === null) return;

        const match = lessorPreselection.data?.data.find((row) => row.agreement_id === requestedAgreementId);
        if (match) setLessor(match);
    }, [lessor, lessorPreselection.data, requestedAgreementId]);

    const lesseeDateError = lessee && !agreementCoversDate(lessee, usageDate)
        ? `${lessee.agreement_number} does not cover ${usageDate}. Select an eligible customer agreement or choose a covered date.`
        : '';
    const lessorDateError = lessor && !agreementCoversDate(lessor, usageDate)
        ? `${lessor.agreement_number} does not cover ${usageDate}. Select an eligible owner / supplier agreement or choose a covered date.`
        : '';
    const linkedVehicleError = mode === 'linked' && lessee && lessor && lessee.vehicle_id !== lessor.vehicle_id
        ? 'Linked running charts require customer and owner / supplier agreements for the same physical vehicle.'
        : '';
    const selectionError = lesseeDateError || lessorDateError || linkedVehicleError;

    const selection = useMemo(() => {
        if (selectionError) return null;

        return buildSelection(mode, lessee, lessor, usageDate);
    }, [lessee, lessor, mode, selectionError, usageDate]);
    const selectionReady = Boolean(selection);
    const selectionKey = selection ? stableSelectionKey(selection) : 'none';

    const context = useApi(
        (signal) => getRunningChartContext(selection!, signal),
        [
            selection?.mode,
            selection?.usage_date,
            selection?.lessee_agreement_id,
            selection?.lessee_agreement_vehicle_id,
            selection?.lessor_agreement_id,
            selection?.lessor_agreement_vehicle_id,
        ],
        selectionReady,
        true,
    );
    const savedTrips = useApi(
        (signal) => listRunningChartTrips(selection!, signal),
        [
            selection?.mode,
            selection?.usage_date,
            selection?.lessee_agreement_id,
            selection?.lessee_agreement_vehicle_id,
            selection?.lessor_agreement_id,
            selection?.lessor_agreement_vehicle_id,
        ],
        selectionReady,
        true,
    );

    useEffect(() => {
        setTrips([]);
        setPreview(null);
        setDrawerTripId(null);
        setError(null);
    }, [selectionKey]);

    useEffect(() => {
        if (!savedTrips.data) return;
        setTrips(savedTrips.data.map(tripFromLog));
    }, [savedTrips.data]);

    const searchLesseeAgreements = useCallback((params: LookupLoadParams) => loadAgreementOptions({
        mode,
        side: 'lessee',
        search: params.search,
        page: params.page,
        perPage: params.perPage,
        usageDate,
        vehicleId: mode === 'linked' ? lessor?.vehicle_id : undefined,
        signal: params.signal,
    }), [lessor?.vehicle_id, mode, usageDate]);

    const searchLessorAgreements = useCallback((params: LookupLoadParams) => loadAgreementOptions({
        mode,
        side: 'lessor',
        search: params.search,
        page: params.page,
        perPage: params.perPage,
        usageDate,
        vehicleId: mode === 'linked' ? lessee?.vehicle_id : undefined,
        signal: params.signal,
    }), [lessee?.vehicle_id, mode, usageDate]);

    const selectedDrawerTrip = trips.find((trip) => trip.localId === drawerTripId) ?? null;
    const selectedDrawerTripEditable = selectedDrawerTrip ? isTripEditable(selectedDrawerTrip, canRecordUsage) : false;
    const displayError = error ?? lesseePreselection.error ?? lessorPreselection.error ?? context.error ?? savedTrips.error;
    const hasSubmittableTrips = trips.some((trip) => isTripSubmittable(trip, canRecordUsage));
    const actionBusy = Boolean(busyAction);

    const requestModeChange = (nextMode: RunningChartMode) => {
        if (nextMode === mode) return;
        if (!confirmDiscard()) return;

        onModeChange(nextMode);
    };

    const changeUsageDate = (nextDate: string) => {
        if (nextDate === usageDate) return;
        if (!confirmDiscard()) return;

        setUsageDate(nextDate);
    };

    const changeLesseeAgreement = (row: RunningChartAgreementLookupOption | null): boolean => {
        if (sameAgreement(lessee, row)) return true;
        if (!confirmDiscard()) return false;

        setLessee(row);
        if (mode === 'linked' && row && lessor && row.vehicle_id !== lessor.vehicle_id) {
            setLessor(null);
        }

        return true;
    };

    const changeLessorAgreement = (row: RunningChartAgreementLookupOption | null): boolean => {
        if (sameAgreement(lessor, row)) return true;
        if (!confirmDiscard()) return false;

        setLessor(row);
        if (mode === 'linked' && row && lessee && row.vehicle_id !== lessee.vehicle_id) {
            setLessee(null);
        }

        return true;
    };

    const addTrip = () => {
        const resolvedContext = context.data;
        if (!resolvedContext || !canRecordUsage || actionBusy) return;

        const previous = trips.at(-1);
        setPreview(null);
        setTrips((current) => [...current, blankTrip(
            previous?.end_odometer || resolvedContext.last_valid_finish_odometer,
            previous?.end_time || '',
        )]);
    };

    const copyPreviousTrip = () => {
        if (!canRecordUsage || actionBusy) return;

        const previous = trips.at(-1);
        if (!previous) {
            addTrip();
            return;
        }
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

    const persistDraftTrips = async (): Promise<RentalUsageLog[]> => {
        if (!selection || !canRecordUsage) return [];

        const saved: RentalUsageLog[] = [];
        for (const trip of trips) {
            if (!isTripEditable(trip, canRecordUsage) || (!trip.dirty && trip.savedId)) continue;

            const payload = payloadFromTrip(selection, trip);
            const row = trip.savedId
                ? await updateRunningChartTrip(trip.savedId, payload)
                : await createRunningChartTrip(payload);
            saved.push(row);
        }

        return saved;
    };

    const saveDrafts = async () => {
        if (actionBusy || !selection || !canRecordUsage) return;

        setBusyAction('save');
        setError(null);
        try {
            await persistDraftTrips();
            savedTrips.reload();
            setPreview(null);
        } catch (requestError) {
            handleRequestError(requestError);
        } finally {
            setBusyAction(null);
        }
    };

    const previewTrips = async () => {
        if (actionBusy || !selection) return;

        setBusyAction('preview');
        setError(null);
        try {
            setPreview(await previewRunningChart({
                ...selection,
                trips: trips.map((trip) => payloadFromTrip(selection, trip)),
            }));
        } catch (requestError) {
            handleRequestError(requestError);
        } finally {
            setBusyAction(null);
        }
    };

    const submitTrips = async () => {
        if (actionBusy || !selection || !canRecordUsage) return;

        const tripsToSubmit = trips.filter((trip) => isTripSubmittable(trip, canRecordUsage));
        if (tripsToSubmit.length === 0) return;

        setBusyAction('submit');
        setError(null);
        try {
            await submitRunningChartDaily({
                ...selection,
                trips: tripsToSubmit.map((trip) => ({
                    ...payloadFromTrip(selection, trip),
                    id: trip.savedId,
                })),
            });
            savedTrips.reload();
            setPreview(null);
        } catch (requestError) {
            handleRequestError(requestError);
        } finally {
            setBusyAction(null);
        }
    };

    const changeStatus = async (trip: DraftTrip, status: 'approve' | 'reject') => {
        if (actionBusy || !trip.savedId || !canApproveUsage) return;

        setBusyAction(`${status}-${trip.savedId}`);
        setError(null);
        try {
            await changeRunningChartTripStatus(trip.savedId, status);
            savedTrips.reload();
        } catch (requestError) {
            handleRequestError(requestError);
        } finally {
            setBusyAction(null);
        }
    };

    const deleteTrip = async (trip: DraftTrip) => {
        if (actionBusy || !isTripEditable(trip, canRecordUsage)) return;

        setBusyAction(`delete-${trip.localId}`);
        setError(null);
        try {
            if (trip.savedId) await deleteRunningChartTrip(trip.savedId);
            setTrips((current) => current.filter((row) => row.localId !== trip.localId));
            savedTrips.reload();
        } catch (requestError) {
            handleRequestError(requestError);
        } finally {
            setBusyAction(null);
        }
    };

    function handleRequestError(requestError: unknown) {
        setError(toApiError(requestError));
        logRunningChartDiagnostics(requestError, mode, selection);
    }

    return (
        <>
            <ErrorAlert error={displayError} />
            <div className="space-y-5">
                <Panel title="Daily running chart">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Select
                            label="Mode"
                            value={mode}
                            options={modeOptions}
                            onChange={(event) => requestModeChange(modeFromValue(event.target.value) ?? 'lessee')}
                        />
                        <Input label="Usage date" type="date" value={usageDate} onChange={(event) => changeUsageDate(event.target.value)} />
                        {mode !== 'lessor' && (
                            <RunningChartAgreementSelector
                                label="Customer agreement"
                                value={lessee}
                                search={searchLesseeAgreements}
                                onChange={changeLesseeAgreement}
                            />
                        )}
                        {mode !== 'lessee' && (
                            <RunningChartAgreementSelector
                                label="Owner / supplier agreement"
                                value={lessor}
                                search={searchLessorAgreements}
                                onChange={changeLessorAgreement}
                            />
                        )}
                    </div>
                    {selectionError && (
                        <p className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            {selectionError}
                        </p>
                    )}
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
                            <Button type="button" variant="secondary" disabled={!canRecordUsage || actionBusy} onClick={addTrip}>Add Trip</Button>
                            <Button type="button" variant="secondary" disabled={!canRecordUsage || actionBusy} onClick={copyPreviousTrip}>Copy Previous Trip</Button>
                            <Button type="button" variant="secondary" loading={busyAction === 'preview'} disabled={actionBusy && busyAction !== 'preview'} onClick={() => void previewTrips()}>Preview</Button>
                            <Button type="button" loading={busyAction === 'save'} disabled={!canRecordUsage || (actionBusy && busyAction !== 'save')} onClick={() => void saveDrafts()}>Save Draft</Button>
                            <Button type="button" loading={busyAction === 'submit'} disabled={!canRecordUsage || !hasSubmittableTrips || (actionBusy && busyAction !== 'submit')} onClick={() => void submitTrips()}>Submit</Button>
                        </div>
                        {savedTrips.loading ? <LoadingState label="Loading daily trips..." /> : (
                            <DataTable
                                rows={trips}
                                rowKey={(trip) => trip.localId}
                                emptyMessage="No trips recorded for this working date."
                                columns={[
                                    { key: 'time', header: 'Start / Finish', render: (trip) => editableInput(trip, 'time') },
                                    { key: 'odo', header: 'Start / Finish KM', render: (trip) => editableInput(trip, 'odometer') },
                                    { key: 'distance', header: 'Distance', render: (trip) => calculatedDistance(trip) },
                                    { key: 'duration', header: 'Working Time', render: (trip) => calculatedDuration(trip) },
                                    { key: 'route', header: 'Route', render: (trip) => editableInput(trip, 'route') },
                                    { key: 'purpose', header: 'Purpose', render: (trip) => editableInput(trip, 'purpose') },
                                    { key: 'ot', header: 'OT', render: (trip) => `${trip.ot_hours || '-'} ${trip.ot_hours ? trip.ot_type.replaceAll('_', ' ') : ''}` },
                                    { key: 'night', header: 'Night Out', render: (trip) => trip.night_out ? 'Yes' : '-' },
                                    { key: 'pass', header: 'Pass / Allowance', render: (trip) => trip.pass_allowance || '-' },
                                    { key: 'status', header: 'Status', render: (trip) => <RentalStatusBadge status={trip.status === 'local' ? 'draft' : trip.status} /> },
                                    { key: 'actions', header: '', render: (trip) => (
                                        <div className="flex flex-wrap justify-end gap-2">
                                            <Button type="button" variant="secondary" disabled={actionBusy} onClick={() => setDrawerTripId(trip.localId)}>
                                                {isTripEditable(trip, canRecordUsage) ? 'Edit' : 'View'}
                                            </Button>
                                            {isTripEditable(trip, canRecordUsage) && (
                                                <Button type="button" variant="danger" loading={busyAction === `delete-${trip.localId}`} disabled={actionBusy && busyAction !== `delete-${trip.localId}`} onClick={() => void deleteTrip(trip)}>Delete Draft Trip</Button>
                                            )}
                                            {trip.status === 'submitted' && canApproveUsage && (
                                                <>
                                                    <Button type="button" loading={busyAction === `approve-${trip.savedId}`} disabled={actionBusy && busyAction !== `approve-${trip.savedId}`} onClick={() => void changeStatus(trip, 'approve')}>Approve</Button>
                                                    <Button type="button" variant="secondary" loading={busyAction === `reject-${trip.savedId}`} disabled={actionBusy && busyAction !== `reject-${trip.savedId}`} onClick={() => void changeStatus(trip, 'reject')}>Reject</Button>
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
                            { label: 'Minutes', value: preview.working_minutes },
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
                        editable={selectedDrawerTripEditable}
                        canClassifyHoliday={canClassifyHoliday}
                        searchDriver={searchDriver}
                        onChange={(patch) => updateTripRow(selectedDrawerTrip.localId, patch)}
                    />
                )}
            </Drawer>
        </>
    );

    function editableInput(trip: DraftTrip, kind: 'time' | 'odometer' | 'route' | 'purpose') {
        const editable = isTripEditable(trip, canRecordUsage);
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
        setTrips((current) => current.map((trip) => (
            trip.localId === localId ? { ...trip, ...patch, dirty: true } : trip
        )));
    }
}

function RunningChartAgreementSelector({
    label,
    value,
    search,
    onChange,
}: {
    label: string;
    value: RunningChartAgreementLookupOption | null;
    search: (params: LookupLoadParams) => Promise<ApiCollection<RunningChartAgreementLookupOption>>;
    onChange: (row: RunningChartAgreementLookupOption | null) => boolean;
}) {
    return (
        <GenericLookupSelect
            label={label}
            value={value}
            onChange={onChange}
            search={search}
            formatLabel={agreementLabel}
            placeholder="Search agreement, party, or vehicle"
            minSearchLength={0}
            loadOnOpen
        />
    );
}

function TripDrawer({
    trip,
    editable,
    canClassifyHoliday,
    searchDriver,
    onChange,
}: {
    trip: DraftTrip;
    editable: boolean;
    canClassifyHoliday: boolean;
    searchDriver: (params: LookupLoadParams) => ReturnType<typeof searchEmployees>;
    onChange: (patch: Partial<DraftTrip>) => void;
}) {
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
            <Checkbox label="Holiday" checked={trip.holiday} disabled={!editable || !canClassifyHoliday} onChange={(checked) => onChange({ holiday: checked })} />
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
    if (isPositiveDecimal(trip.ot_hours)) events.push({ event_type: trip.ot_type, quantity: trip.ot_hours });
    if (trip.night_out) events.push({ event_type: 'night_out', quantity: '1.000000' });
    if (isPositiveDecimal(trip.pass_allowance)) events.push({ event_type: 'pass', quantity: trip.pass_allowance, remarks: 'Pass / allowance' });
    if (isPositiveDecimal(trip.waiting_hours)) events.push({ event_type: 'waiting', quantity: trip.waiting_hours });
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
    if (!trip.start_odometer.trim() || !trip.end_odometer.trim()) return '-';
    if (compareDecimalStrings(trip.end_odometer, trip.start_odometer) < 0) return '-';

    return subtractDecimal(trip.end_odometer, trip.start_odometer);
}

function calculatedDuration(trip: DraftTrip): string {
    const start = timeToMinutes(trip.start_time);
    const finish = timeToMinutes(trip.end_time);
    if (start === null || finish === null) return '-';

    const minutes = finish < start ? finish + (24 * 60) - start : finish - start;

    return formatMinutes(minutes);
}

function timeToMinutes(value: string): number | null {
    const match = /^(\d{2}):(\d{2})$/.exec(value);
    if (!match) return null;

    const hours = parseInt(match[1], 10);
    const minutes = parseInt(match[2], 10);
    if (hours > 23 || minutes > 59) return null;

    return (hours * 60) + minutes;
}

function formatMinutes(totalMinutes: number): string {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    return minutes === 0 ? `${hours}h` : `${hours}h ${String(minutes).padStart(2, '0')}m`;
}

function loadAgreementOptions({
    mode,
    side,
    search,
    page,
    perPage,
    usageDate,
    agreementId,
    vehicleId,
    signal,
}: {
    mode: RunningChartMode;
    side: 'lessee' | 'lessor';
    search?: string;
    page?: number;
    perPage?: number;
    usageDate: string;
    agreementId?: number;
    vehicleId?: number;
    signal?: AbortSignal;
}): Promise<ApiCollection<RunningChartAgreementLookupOption>> {
    return listRunningChartAgreements({
        mode,
        side,
        search: search || undefined,
        usage_date: usageDate,
        agreement_id: agreementId,
        vehicle_id: vehicleId,
        page,
        per_page: perPage,
    }, signal).then((result) => ({
        ...result,
        data: result.data.map(toAgreementLookupOption),
    }));
}

function toAgreementLookupOption(option: RunningChartAgreementOption): RunningChartAgreementLookupOption {
    return {
        ...option,
        id: option.agreement_vehicle_id,
        name: agreementLabel(option),
    };
}

function agreementLabel(option: RunningChartAgreementOption): string {
    return `${option.agreement_number} / ${option.party_name ?? option.party_type} / ${option.vehicle_registration ?? option.vehicle_id}`;
}

function agreementCoversDate(option: RunningChartAgreementOption, usageDate: string): boolean {
    const from = option.allocation_from.slice(0, 10);
    const to = (option.allocation_to ?? option.expected_end_at).slice(0, 10);

    return usageDate >= from && usageDate <= to;
}

function sameAgreement(left: RunningChartAgreementOption | null, right: RunningChartAgreementOption | null): boolean {
    return left?.agreement_id === right?.agreement_id
        && left?.agreement_vehicle_id === right?.agreement_vehicle_id;
}

function isTripEditable(trip: DraftTrip, canRecordUsage: boolean): boolean {
    return canRecordUsage && editableStatuses.includes(trip.status);
}

function isTripSubmittable(trip: DraftTrip, canRecordUsage: boolean): boolean {
    return canRecordUsage && ['local', 'draft', 'rejected'].includes(trip.status);
}

function stableSelectionKey(selection: NonNullable<ReturnType<typeof buildSelection>>): string {
    return [
        selection.mode,
        selection.usage_date,
        selection.lessee_agreement_id ?? '',
        selection.lessee_agreement_vehicle_id ?? '',
        selection.lessor_agreement_id ?? '',
        selection.lessor_agreement_vehicle_id ?? '',
    ].join('|');
}

function positiveInteger(value: string | undefined | null): number | null {
    if (!value) return null;

    const parsed = parseInt(value, 10);
    return Number.isSafeInteger(parsed) && parsed > 0 ? parsed : null;
}

function logRunningChartDiagnostics(
    requestError: unknown,
    mode: RunningChartMode,
    selection: ReturnType<typeof buildSelection>,
) {
    if (!import.meta.env.DEV) return;

    console.error('Running Chart request failed', {
        mode,
        usageDate: selection?.usage_date,
        lesseeAgreementId: selection?.lessee_agreement_id,
        lessorAgreementId: selection?.lessor_agreement_id,
        error: requestError,
    });
}
