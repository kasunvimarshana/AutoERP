import { useCallback, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { searchEmployees } from '@/modules/hr/hrApi';
import type { EmployeeSummary } from '@/modules/hr/hrTypes';
import { createRentalUsageLog } from '../vehicleRentalApi';
import type { RentalAgreementVehicle, RentalUsageLog } from '../vehicleRentalTypes';

const today = () => new Date().toISOString().slice(0, 10);

export function UsageLogEditor({ agreementId, allocations, onSaved }: {
    agreementId: number;
    allocations: RentalAgreementVehicle[];
    onSaved: (log: RentalUsageLog) => void;
}) {
    const active = allocations.filter((row) => ['allocated', 'active'].includes(row.status));
    const [driver, setDriver] = useState<EmployeeSummary | null>(null);
    const [form, setForm] = useState({
        agreement_vehicle_id: String(active[0]?.id ?? ''),
        usage_date: today(),
        start_time: '',
        end_time: '',
        start_odometer: active[0]?.start_odometer ?? '',
        end_odometer: '',
        comparative_km: '',
        trip_from: '',
        trip_to: '',
        trip_purpose: '',
        status: 'draft',
        remarks: '',
    });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const searchDriver = useCallback((query: string, signal: AbortSignal) => searchEmployees(query, signal), []);

    return (
        <Panel title="New running chart row">
            <ErrorAlert error={error} />
            <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" onSubmit={async (event) => {
                event.preventDefault();
                setBusy(true);
                setError(null);
                try {
                    const allocation = active.find((row) => row.id === Number(form.agreement_vehicle_id));
                    const saved = await createRentalUsageLog(agreementId, {
                        ...form,
                        agreement_vehicle_id: Number(form.agreement_vehicle_id),
                        vehicle_id: allocation?.vehicle_id,
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
                    setForm((current) => ({ ...current, end_odometer: '', comparative_km: '', trip_from: '', trip_to: '', trip_purpose: '', remarks: '' }));
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setBusy(false);
                }
            }}>
                <Select label="Agreement vehicle" value={form.agreement_vehicle_id} error={fieldError(error, 'agreement_vehicle_id')} options={active.map((row) => ({
                    value: row.id,
                    label: row.vehicle?.registration_number ?? row.vehicle?.name ?? `Vehicle ${row.vehicle_id}`,
                }))} onChange={(event) => {
                    const allocation = active.find((row) => row.id === Number(event.target.value));
                    setForm({ ...form, agreement_vehicle_id: event.target.value, start_odometer: allocation?.end_odometer ?? allocation?.start_odometer ?? '' });
                }} />
                <GenericLookupSelect label="Driver" value={driver} onChange={setDriver} search={searchDriver} formatLabel={(row) => `${row.employee_number} ${row.display_name}`} error={fieldError(error, 'driver_id')} />
                <Input label="Usage date" type="date" value={form.usage_date} error={fieldError(error, 'usage_date')} onChange={(event) => setForm({ ...form, usage_date: event.target.value })} />
                <Select label="Status" value={form.status} options={['draft', 'submitted', 'approved'].map((value) => ({ value, label: value }))} onChange={(event) => setForm({ ...form, status: event.target.value })} />
                <Input label="ON time" type="time" value={form.start_time} onChange={(event) => setForm({ ...form, start_time: event.target.value })} />
                <Input label="OFF time" type="time" value={form.end_time} onChange={(event) => setForm({ ...form, end_time: event.target.value })} />
                <DecimalInput label="Start KM" value={form.start_odometer} error={fieldError(error, 'start_odometer')} onChange={(event) => setForm({ ...form, start_odometer: event.target.value })} />
                <DecimalInput label="Finish KM" value={form.end_odometer} error={fieldError(error, 'end_odometer')} onChange={(event) => setForm({ ...form, end_odometer: event.target.value })} />
                <DecimalInput label="Comparative KM" value={form.comparative_km} onChange={(event) => setForm({ ...form, comparative_km: event.target.value })} />
                <Input label="From" value={form.trip_from} onChange={(event) => setForm({ ...form, trip_from: event.target.value })} />
                <Input label="To" value={form.trip_to} onChange={(event) => setForm({ ...form, trip_to: event.target.value })} />
                <Input label="Purpose" value={form.trip_purpose} onChange={(event) => setForm({ ...form, trip_purpose: event.target.value })} />
                <div className="md:col-span-2 xl:col-span-3"><Textarea label="Remarks" value={form.remarks} onChange={(event) => setForm({ ...form, remarks: event.target.value })} /></div>
                <div className="flex items-end justify-end"><Button type="submit" loading={busy}>Add usage</Button></div>
            </form>
        </Panel>
    );
}
