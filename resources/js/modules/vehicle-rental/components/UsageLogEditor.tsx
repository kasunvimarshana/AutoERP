import { useCallback, useEffect, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { searchEmployees } from '@/modules/hr/hrApi';
import type { EmployeeSummary } from '@/modules/hr/hrTypes';
import { createRentalUsageLog } from '../vehicleRentalApi';
import type { RentalUsageLog } from '../vehicleRentalTypes';

export function UsageLogEditor({
    agreementId,
    agreementVehicleId,
    startOdometer,
    initialUsageDate,
    initialStartTime,
    onSaved,
}: {
    agreementId: number;
    agreementVehicleId: number;
    startOdometer: string;
    initialUsageDate: string;
    initialStartTime: string;
    onSaved: (log: RentalUsageLog) => void;
}) {
    const [driver, setDriver] = useState<EmployeeSummary | null>(null);
    const [form, setForm] = useState({
        usage_date: initialUsageDate,
        start_time: initialStartTime,
        end_time: '',
        start_odometer: startOdometer,
        end_odometer: '',
        comparative_km: '',
        trip_from: '',
        trip_to: '',
        trip_purpose: '',
        remarks: '',
    });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const searchDriver = useCallback((params: LookupLoadParams) => searchEmployees(params), []);
    const dirty = Boolean(
        form.end_odometer
        || form.comparative_km
        || form.trip_from
        || form.trip_to
        || form.trip_purpose
        || form.remarks
        || driver,
    );
    useUnsavedChanges(dirty && !busy);

    useEffect(() => {
        if (!form.end_odometer) {
            setForm((current) => ({ ...current, start_odometer: startOdometer }));
        }
    }, [form.end_odometer, startOdometer]);

    useEffect(() => {
        setForm((current) => ({
            ...current,
            usage_date: initialUsageDate,
            start_time: initialStartTime,
        }));
    }, [initialStartTime, initialUsageDate]);

    return (
        <Panel title="3. Enter physical usage">
            <ErrorAlert error={error} />
            <form className="space-y-4" onSubmit={async (event) => {
                event.preventDefault();
                setBusy(true);
                setError(null);
                try {
                    const saved = await createRentalUsageLog(agreementId, {
                        ...form,
                        agreement_vehicle_id: agreementVehicleId,
                        driver_id: driver?.id,
                        start_time: form.start_time || undefined,
                        end_time: form.end_time || undefined,
                        comparative_km: form.comparative_km || undefined,
                        trip_from: form.trip_from || undefined,
                        trip_to: form.trip_to || undefined,
                        trip_purpose: form.trip_purpose || undefined,
                        remarks: form.remarks || undefined,
                    });
                    onSaved(saved);
                    setDriver(null);
                    setForm((current) => ({ ...current, end_odometer: '', comparative_km: '', trip_from: '', trip_to: '', trip_purpose: '', remarks: '' }));
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setBusy(false);
                }
            }}>
                <fieldset className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <legend className="sr-only">Physical usage</legend>
                    <GenericLookupSelect label="Driver" value={driver} onChange={setDriver} search={searchDriver} formatLabel={(row) => `${row.employee_number} ${row.display_name}`} error={fieldError(error, 'driver_id')} />
                    <Input label="OFF time" type="time" value={form.end_time} onChange={(event) => setForm({ ...form, end_time: event.target.value })} />
                    <DecimalInput label="Start KM" value={form.start_odometer} error={fieldError(error, 'start_odometer')} onChange={(event) => setForm({ ...form, start_odometer: event.target.value })} />
                    <DecimalInput label="Finish KM" value={form.end_odometer} error={fieldError(error, 'end_odometer')} onChange={(event) => setForm({ ...form, end_odometer: event.target.value })} />
                </fieldset>
                <details className="rounded-lg border border-slate-200 bg-slate-50">
                    <summary className="min-h-11 cursor-pointer px-4 py-3 text-sm font-semibold text-slate-800">Trip details and notes</summary>
                    <fieldset className="grid gap-4 border-t border-slate-200 p-4 md:grid-cols-2 xl:grid-cols-4">
                        <legend className="sr-only">Optional trip details</legend>
                        <DecimalInput label="Comparative KM" value={form.comparative_km} onChange={(event) => setForm({ ...form, comparative_km: event.target.value })} />
                        <Input label="From" value={form.trip_from} onChange={(event) => setForm({ ...form, trip_from: event.target.value })} />
                        <Input label="To" value={form.trip_to} onChange={(event) => setForm({ ...form, trip_to: event.target.value })} />
                        <Input label="Purpose" value={form.trip_purpose} onChange={(event) => setForm({ ...form, trip_purpose: event.target.value })} />
                        <div className="md:col-span-2 xl:col-span-4"><Textarea label="Remarks" value={form.remarks} onChange={(event) => setForm({ ...form, remarks: event.target.value })} /></div>
                    </fieldset>
                </details>
                <div className="flex justify-end"><Button type="submit" loading={busy}>Save draft running chart</Button></div>
            </form>
        </Panel>
    );
}
