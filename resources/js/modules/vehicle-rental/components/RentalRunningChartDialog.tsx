import { useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { notifySuccess } from '@/shared/notifications/appToast';
import { compareDecimalStrings, isDecimalString, subtractDecimal } from '@/shared/utils/decimal';
import { createRentalRunningChart, updateRentalRunningChart } from '../vehicleRentalApi';
import type {
    RentalAcMode,
    RentalRunningChart,
    RentalRunningChartPayload,
} from '../vehicleRentalTypes';
import { RentalAssignmentLookup, type RentalLookupOption } from './VehicleRentalLookups';

interface RunningChartFormState {
    assignment: RentalLookupOption | null;
    startsAt: string;
    endsAt: string;
    startOdometer: string;
    endOdometer: string;
    garageKm: string;
    normalOvertimeHours: string;
    doubleOvertimeHours: string;
    tripleOvertimeHours: string;
    nightOutCount: string;
    acMode: RentalAcMode | '';
    tripOrigin: string;
    tripDestination: string;
    purpose: string;
    odometerVarianceReason: string;
    remarks: string;
}

interface RentalRunningChartDialogProps {
    open: boolean;
    chart: RentalRunningChart | null;
    onClose: () => void;
    onSaved: (chart: RentalRunningChart) => void;
}

export function RentalRunningChartDialog(props: RentalRunningChartDialogProps) {
    const identity = `${props.open ? 'open' : 'closed'}:${props.chart?.id ?? 'new'}:${props.chart?.row_version ?? 0}`;
    return <RentalRunningChartDialogForm key={identity} {...props} />;
}

function RentalRunningChartDialogForm({
    open,
    chart,
    onClose,
    onSaved,
}: RentalRunningChartDialogProps) {
    const [state, setState] = useState<RunningChartFormState>(() => initialState(chart));
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const selfDrive = state.assignment?.selfDrive ?? (chart ? chart.driver == null : false);
    const odometerAvailable = chart
        ? chart.start_odometer !== null
        : state.assignment?.odometerAvailable === true;
    const distance = odometerAvailable
        ? distancePreview(state.startOdometer, state.endOdometer, state.garageKm)
        : null;
    const showVarianceReason = odometerAvailable
        && (state.startOdometer.trim() !== '' || state.odometerVarianceReason.trim() !== '');

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setError(null);
        try {
            const payload: RentalRunningChartPayload = {
                assignment_id: chart ? undefined : state.assignment?.id ?? 0,
                operational_date: localDate(state.startsAt),
                starts_at: state.startsAt,
                ends_at: state.endsAt,
                start_odometer: odometerAvailable ? nullable(state.startOdometer) : null,
                end_odometer: odometerAvailable ? nullable(state.endOdometer) : null,
                garage_km: odometerAvailable ? nullable(state.garageKm) : null,
                normal_overtime_hours: selfDrive ? '0' : state.normalOvertimeHours || '0',
                double_overtime_hours: selfDrive ? '0' : state.doubleOvertimeHours || '0',
                triple_overtime_hours: selfDrive ? '0' : state.tripleOvertimeHours || '0',
                night_out_count: selfDrive ? 0 : Number.parseInt(state.nightOutCount || '0', 10),
                ac_mode: state.acMode || null,
                trip_origin: nullable(state.tripOrigin),
                trip_destination: nullable(state.tripDestination),
                purpose: nullable(state.purpose),
                odometer_variance_reason: showVarianceReason ? nullable(state.odometerVarianceReason) : null,
                remarks: nullable(state.remarks),
                expected_version: chart?.row_version,
            };
            const saved = chart
                ? await updateRentalRunningChart(chart.id, payload)
                : await createRentalRunningChart(payload);
            notifySuccess(chart ? 'Running chart updated successfully.' : 'Running chart draft created successfully.');
            onSaved(saved);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    const selectAssignment = (value: RentalLookupOption | null) => {
        setState((current) => ({
            ...current,
            assignment: value,
            startOdometer: '',
            endOdometer: '',
            garageKm: value?.odometerAvailable === true ? '0' : '',
            odometerVarianceReason: '',
            normalOvertimeHours: value?.selfDrive ? '0' : current.normalOvertimeHours,
            doubleOvertimeHours: value?.selfDrive ? '0' : current.doubleOvertimeHours,
            tripleOvertimeHours: value?.selfDrive ? '0' : current.tripleOvertimeHours,
            nightOutCount: value?.selfDrive ? '0' : current.nightOutCount,
        }));
    };

    return (
        <Modal open={open} title={chart ? `Edit ${chart.chart_number}` : 'New running chart'} onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-5" onSubmit={(event) => void submit(event)}>
                <ErrorAlert error={error} inline />
                <div className="grid gap-4 md:grid-cols-2">
                    <RentalAssignmentLookup
                        label="Customer vehicle"
                        side="customer_use"
                        value={state.assignment}
                        required
                        disabled={Boolean(chart)}
                        onChange={selectAssignment}
                        error={fieldError(error, 'assignment_id')}
                    />
                    <Input
                        label="Starts at"
                        type="datetime-local"
                        required
                        min={state.assignment?.assignmentStartsAt ? toLocalDateTime(state.assignment.assignmentStartsAt) : undefined}
                        max={state.assignment?.assignmentEndsAt ? toLocalDateTime(state.assignment.assignmentEndsAt) : undefined}
                        value={state.startsAt}
                        error={fieldError(error, 'starts_at') ?? fieldError(error, 'operational_date')}
                        onChange={(event) => setState((current) => ({ ...current, startsAt: event.target.value }))}
                    />
                    <Input
                        label="Ends at"
                        type="datetime-local"
                        required
                        min={state.startsAt || undefined}
                        max={state.assignment?.assignmentEndsAt ? toLocalDateTime(state.assignment.assignmentEndsAt) : undefined}
                        value={state.endsAt}
                        error={fieldError(error, 'ends_at')}
                        onChange={(event) => setState((current) => ({ ...current, endsAt: event.target.value }))}
                    />
                    <Select label="AC mode" value={state.acMode} placeholder="Not applicable" options={[{ value: 'non_ac', label: 'Non-AC' }, { value: 'front_ac', label: 'Front AC' }, { value: 'dual_ac', label: 'Dual AC' }]} error={fieldError(error, 'ac_mode')} onChange={(event) => setState((current) => ({ ...current, acMode: event.target.value as RentalAcMode | '' }))} />
                </div>

                {state.assignment && (
                    <div className="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm md:grid-cols-2">
                        <Summary label="Customer agreement" value={state.assignment.agreement?.code || state.assignment.code || '—'} />
                        <Summary label="Vehicle" value={state.assignment.vehicle?.name || state.assignment.name || '—'} />
                        <Summary label="Owner agreement" value={state.assignment.ownerAgreement?.code || state.assignment.ownerAgreement?.name || 'Company-owned / not linked'} />
                        <Summary label="Driver" value={selfDrive ? 'Self-drive' : state.assignment.driver?.name || state.assignment.driver?.code || 'Not assigned'} />
                    </div>
                )}

                {odometerAvailable ? (
                    <div className="space-y-3 rounded-xl border border-slate-200 bg-white p-4">
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <Input label="Start KM" type="number" min="0" step="0.000001" placeholder="Auto from previous chart" value={state.startOdometer} error={fieldError(error, 'start_odometer')} onChange={(event) => setState((current) => ({ ...current, startOdometer: event.target.value }))} />
                                <p className="mt-1 text-xs text-slate-500">Leave blank to continue from the previous finalized chart.</p>
                            </div>
                            <Input label="End KM" type="number" min="0" step="0.000001" required value={state.endOdometer} error={fieldError(error, 'end_odometer')} onChange={(event) => setState((current) => ({ ...current, endOdometer: event.target.value }))} />
                            <Input label="Garage KM" type="number" min="0" step="0.000001" value={state.garageKm} error={fieldError(error, 'garage_km')} onChange={(event) => setState((current) => ({ ...current, garageKm: event.target.value }))} />
                        </div>
                        <div className="grid gap-3 md:grid-cols-2">
                            <Summary label="Total KM" value={distance?.total ?? 'Calculated after save'} />
                            <Summary label="Commercial KM" value={distance?.commercial ?? 'Calculated after save'} />
                            {distance?.error && <p className="text-sm text-rose-600 md:col-span-2">{distance.error}</p>}
                        </div>
                    </div>
                ) : state.assignment ? (
                    <p className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                        This vehicle has no available odometer. Kilometre fields are not required.
                    </p>
                ) : null}

                {showVarianceReason && (
                    <Textarea label="Odometer variance reason" maxLength={500} hint="Required only when the entered Start KM differs from the previous finalized chart." value={state.odometerVarianceReason} error={fieldError(error, 'odometer_variance_reason')} onChange={(event) => setState((current) => ({ ...current, odometerVarianceReason: event.target.value }))} />
                )}

                {selfDrive ? (
                    <p className="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900">
                        This is a self-drive assignment. Driver overtime and night-out fields are not applicable.
                    </p>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2">
                        <Input label="Normal overtime hours" type="number" min="0" step="0.000001" value={state.normalOvertimeHours} error={fieldError(error, 'normal_overtime_hours')} onChange={(event) => setState((current) => ({ ...current, normalOvertimeHours: event.target.value }))} />
                        <Input label="Double overtime hours" type="number" min="0" step="0.000001" value={state.doubleOvertimeHours} error={fieldError(error, 'double_overtime_hours')} onChange={(event) => setState((current) => ({ ...current, doubleOvertimeHours: event.target.value }))} />
                        <Input label="Triple overtime hours" type="number" min="0" step="0.000001" value={state.tripleOvertimeHours} error={fieldError(error, 'triple_overtime_hours')} onChange={(event) => setState((current) => ({ ...current, tripleOvertimeHours: event.target.value }))} />
                        <Input label="Night-out count" type="number" min="0" step="1" value={state.nightOutCount} error={fieldError(error, 'night_out_count')} onChange={(event) => setState((current) => ({ ...current, nightOutCount: event.target.value }))} />
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Trip origin" maxLength={255} value={state.tripOrigin} error={fieldError(error, 'trip_origin')} onChange={(event) => setState((current) => ({ ...current, tripOrigin: event.target.value }))} />
                    <Input label="Trip destination" maxLength={255} value={state.tripDestination} error={fieldError(error, 'trip_destination')} onChange={(event) => setState((current) => ({ ...current, tripDestination: event.target.value }))} />
                    <Input label="Purpose" maxLength={255} value={state.purpose} error={fieldError(error, 'purpose')} onChange={(event) => setState((current) => ({ ...current, purpose: event.target.value }))} />
                </div>
                <Textarea label="Remarks" maxLength={5000} value={state.remarks} error={fieldError(error, 'remarks')} onChange={(event) => setState((current) => ({ ...current, remarks: event.target.value }))} />
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{chart ? 'Save chart' : 'Create draft'}</Button>
                </div>
            </form>
        </Modal>
    );
}

function Summary({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-slate-500">{label}</p>
            <p className="font-medium text-slate-900">{value}</p>
        </div>
    );
}

function initialState(chart: RentalRunningChart | null): RunningChartFormState {
    const assignment: RentalLookupOption | null = chart?.assignment ? {
        id: chart.assignment.id,
        code: chart.assignment.agreement?.code ?? chart.assignment.agreement?.name ?? `#${chart.assignment.id}`,
        name: chart.assignment.vehicle?.name ?? chart.assignment.vehicle?.code ?? `Assignment #${chart.assignment.id}`,
        agreement: chart.assignment.agreement ?? null,
        vehicle: chart.assignment.vehicle ?? null,
        ownerAgreement: chart.assignment.owner_agreement ?? null,
        driver: chart.driver ?? null,
        selfDrive: chart.driver == null,
        odometerAvailable: chart.start_odometer !== null,
        vehicleOdometerReading: chart.assignment.vehicle?.odometer_reading ?? null,
    } : null;
    return {
        assignment,
        startsAt: toLocalDateTime(chart?.starts_at),
        endsAt: toLocalDateTime(chart?.ends_at),
        startOdometer: chart?.start_odometer ?? '',
        endOdometer: chart?.end_odometer ?? '',
        garageKm: chart?.garage_km ?? '',
        normalOvertimeHours: chart?.normal_overtime_hours ?? '0',
        doubleOvertimeHours: chart?.double_overtime_hours ?? '0',
        tripleOvertimeHours: chart?.triple_overtime_hours ?? '0',
        nightOutCount: String(chart?.night_out_count ?? 0),
        acMode: chart?.ac_mode ?? '',
        tripOrigin: chart?.trip_origin ?? '',
        tripDestination: chart?.trip_destination ?? '',
        purpose: chart?.purpose ?? '',
        odometerVarianceReason: chart?.odometer_variance_reason ?? '',
        remarks: chart?.remarks ?? '',
    };
}

function distancePreview(start: string, end: string, garage: string): { total: string; commercial: string; error?: string } | null {
    if (!start.trim() || !end.trim() || !isDecimalString(start) || !isDecimalString(end)) return null;
    const garageKm = garage.trim() || '0';
    if (!isDecimalString(garageKm)) return null;
    if (compareDecimalStrings(end, start) < 0) {
        return { total: '—', commercial: '—', error: 'End KM cannot be lower than Start KM.' };
    }
    const total = subtractDecimal(end, start);
    if (compareDecimalStrings(garageKm, total) > 0) {
        return { total, commercial: '—', error: 'Garage KM cannot exceed Total KM.' };
    }
    return { total, commercial: subtractDecimal(total, garageKm) };
}

function localDate(value: string): string {
    return value.slice(0, 10);
}

function nullable(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function toLocalDateTime(value?: string | null): string {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value.slice(0, 16);
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);
    return local.toISOString().slice(0, 16);
}
