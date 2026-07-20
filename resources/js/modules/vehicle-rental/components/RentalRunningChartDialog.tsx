import { useEffect, useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { notifySuccess } from '@/shared/notifications/appToast';
import { createRentalRunningChart, updateRentalRunningChart } from '../vehicleRentalApi';
import type {
    RentalAcMode,
    RentalReference,
    RentalRunningChart,
    RentalRunningChartPayload,
} from '../vehicleRentalTypes';
import { RentalAssignmentLookup, type RentalLookupOption } from './VehicleRentalLookups';

interface RunningChartFormState {
    assignment: RentalReference | null;
    operationalDate: string;
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

export function RentalRunningChartDialog({
    open,
    chart,
    onClose,
    onSaved,
}: {
    open: boolean;
    chart: RentalRunningChart | null;
    onClose: () => void;
    onSaved: (chart: RentalRunningChart) => void;
}) {
    const [state, setState] = useState<RunningChartFormState>(() => initialState(null));
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (!open) return;
        setState(initialState(chart));
        setError(null);
        setSubmitting(false);
    }, [chart, open]);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setError(null);
        try {
            const payload: RentalRunningChartPayload = {
                assignment_id: chart ? undefined : state.assignment?.id ?? 0,
                operational_date: state.operationalDate,
                starts_at: state.startsAt,
                ends_at: state.endsAt,
                start_odometer: state.startOdometer,
                end_odometer: state.endOdometer,
                garage_km: state.garageKm || '0',
                normal_overtime_hours: state.normalOvertimeHours || '0',
                double_overtime_hours: state.doubleOvertimeHours || '0',
                triple_overtime_hours: state.tripleOvertimeHours || '0',
                night_out_count: Number.parseInt(state.nightOutCount || '0', 10),
                ac_mode: state.acMode || null,
                trip_origin: nullable(state.tripOrigin),
                trip_destination: nullable(state.tripDestination),
                purpose: nullable(state.purpose),
                odometer_variance_reason: nullable(state.odometerVarianceReason),
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

    return (
        <Modal open={open} title={chart ? `Edit ${chart.chart_number}` : 'New running chart'} onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-5" onSubmit={(event) => void submit(event)}>
                <ErrorAlert error={error} inline />
                <div className="grid gap-4 md:grid-cols-2">
                    <RentalAssignmentLookup
                        label="Customer-use assignment"
                        side="customer_use"
                        value={state.assignment}
                        required
                        disabled={Boolean(chart)}
                        onChange={(value: RentalLookupOption | null) => setState((current) => ({ ...current, assignment: value }))}
                        error={fieldError(error, 'assignment_id')}
                    />
                    <Input label="Operational date" type="date" required value={state.operationalDate} error={fieldError(error, 'operational_date')} onChange={(event) => setState((current) => ({ ...current, operationalDate: event.target.value }))} />
                    <Input label="Starts at" type="datetime-local" required value={state.startsAt} error={fieldError(error, 'starts_at')} onChange={(event) => setState((current) => ({ ...current, startsAt: event.target.value }))} />
                    <Input label="Ends at" type="datetime-local" required min={state.startsAt || undefined} value={state.endsAt} error={fieldError(error, 'ends_at')} onChange={(event) => setState((current) => ({ ...current, endsAt: event.target.value }))} />
                    <Input label="Start odometer" type="number" min="0" step="0.000001" required value={state.startOdometer} error={fieldError(error, 'start_odometer')} onChange={(event) => setState((current) => ({ ...current, startOdometer: event.target.value }))} />
                    <Input label="End odometer" type="number" min="0" step="0.000001" required value={state.endOdometer} error={fieldError(error, 'end_odometer')} onChange={(event) => setState((current) => ({ ...current, endOdometer: event.target.value }))} />
                    <Input label="Garage KM" type="number" min="0" step="0.000001" value={state.garageKm} error={fieldError(error, 'garage_km')} onChange={(event) => setState((current) => ({ ...current, garageKm: event.target.value }))} />
                    <Select label="AC mode" value={state.acMode} placeholder="Not applicable" options={[{ value: 'non_ac', label: 'Non-AC' }, { value: 'front_ac', label: 'Front AC' }, { value: 'dual_ac', label: 'Dual AC' }]} error={fieldError(error, 'ac_mode')} onChange={(event) => setState((current) => ({ ...current, acMode: event.target.value as RentalAcMode | '' }))} />
                    <Input label="Normal overtime hours" type="number" min="0" step="0.000001" value={state.normalOvertimeHours} error={fieldError(error, 'normal_overtime_hours')} onChange={(event) => setState((current) => ({ ...current, normalOvertimeHours: event.target.value }))} />
                    <Input label="Double overtime hours" type="number" min="0" step="0.000001" value={state.doubleOvertimeHours} error={fieldError(error, 'double_overtime_hours')} onChange={(event) => setState((current) => ({ ...current, doubleOvertimeHours: event.target.value }))} />
                    <Input label="Triple overtime hours" type="number" min="0" step="0.000001" value={state.tripleOvertimeHours} error={fieldError(error, 'triple_overtime_hours')} onChange={(event) => setState((current) => ({ ...current, tripleOvertimeHours: event.target.value }))} />
                    <Input label="Night-out count" type="number" min="0" step="1" value={state.nightOutCount} error={fieldError(error, 'night_out_count')} onChange={(event) => setState((current) => ({ ...current, nightOutCount: event.target.value }))} />
                    <Input label="Trip origin" maxLength={255} value={state.tripOrigin} error={fieldError(error, 'trip_origin')} onChange={(event) => setState((current) => ({ ...current, tripOrigin: event.target.value }))} />
                    <Input label="Trip destination" maxLength={255} value={state.tripDestination} error={fieldError(error, 'trip_destination')} onChange={(event) => setState((current) => ({ ...current, tripDestination: event.target.value }))} />
                    <Input label="Purpose" maxLength={255} value={state.purpose} error={fieldError(error, 'purpose')} onChange={(event) => setState((current) => ({ ...current, purpose: event.target.value }))} />
                </div>
                <Textarea label="Odometer variance reason" maxLength={500} hint="Required by the backend when the start odometer intentionally differs from the previous finalized chart." value={state.odometerVarianceReason} error={fieldError(error, 'odometer_variance_reason')} onChange={(event) => setState((current) => ({ ...current, odometerVarianceReason: event.target.value }))} />
                <Textarea label="Remarks" maxLength={5000} value={state.remarks} error={fieldError(error, 'remarks')} onChange={(event) => setState((current) => ({ ...current, remarks: event.target.value }))} />
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{chart ? 'Save chart' : 'Create draft'}</Button>
                </div>
            </form>
        </Modal>
    );
}

function initialState(chart: RentalRunningChart | null): RunningChartFormState {
    const assignment = chart?.assignment ? {
        id: chart.assignment.id,
        code: chart.assignment.agreement?.code ?? chart.assignment.agreement?.name,
        name: chart.assignment.vehicle?.name ?? chart.assignment.vehicle?.code,
    } : null;
    return {
        assignment,
        operationalDate: chart?.operational_date ?? '',
        startsAt: toLocalDateTime(chart?.starts_at),
        endsAt: toLocalDateTime(chart?.ends_at),
        startOdometer: chart?.start_odometer ?? '',
        endOdometer: chart?.end_odometer ?? '',
        garageKm: chart?.garage_km ?? '0',
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
