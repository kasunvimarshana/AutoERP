import { useCallback, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { createVehicleServiceJob, updateVehicleServiceJob } from '../vehicleServiceApi';
import type { CommissionType, VehicleServiceJob, VehicleServiceJobPayload } from '../vehicleServiceTypes';

const today = () => new Date().toISOString().slice(0, 10);
const decimal = (value: string, fallback = '0.000000') => value.trim() || fallback;

export function VehicleServiceJobForm({ job }: { job?: VehicleServiceJob }) {
    const navigate = useNavigate();
    const [customer, setCustomer] = useState<NamedResource | null>(job?.customer ?? null);
    const [vehicle, setVehicle] = useState<NamedResource | null>(job?.vehicle ?? null);
    const [supervisor, setSupervisor] = useState<NamedResource | null>(job?.supervisor ?? null);
    const [form, setForm] = useState({
        job_date: job?.job_date ?? today(),
        expected_delivery_date: job?.expected_delivery_date ?? '',
        supervisor_commission_type: job?.supervisor_commission_type ?? 'none' as CommissionType,
        supervisor_commission_value: job?.supervisor_commission_value ?? '0.000000',
        odometer_reading: job?.odometer_reading ?? '',
        fuel_level: job?.fuel_level ?? '',
        priority: job?.priority ?? 'normal',
        customer_complaint: job?.inspection?.customer_complaint ?? '',
        notes: job?.notes ?? '',
    });
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const errorFor = (key: string) => fieldError(error, key);

    const searchCustomer = useCallback(async (query: string, signal: AbortSignal): Promise<NamedResource[]> => {
        return lookupApi.customers(query, signal);
    }, []);
    const searchVehicle = useCallback(async (query: string, signal: AbortSignal): Promise<NamedResource[]> => {
        if (!customer) return [];
        return lookupApi.serviceVehicles(customer.id, query, signal);
    }, [customer]);
    const searchSupervisor = useCallback(async (query: string, signal: AbortSignal): Promise<NamedResource[]> => {
        return lookupApi.availableEmployees(query, signal);
    }, []);

    const payload = (): VehicleServiceJobPayload => ({
        job_date: form.job_date,
        expected_delivery_date: form.expected_delivery_date || undefined,
        customer_id: customer?.id ?? 0,
        vehicle_id: vehicle?.id ?? 0,
        supervisor_employee_id: supervisor?.id,
        supervisor_commission_type: form.supervisor_commission_type,
        supervisor_commission_value: decimal(form.supervisor_commission_value),
        odometer_reading: form.odometer_reading || undefined,
        fuel_level: form.fuel_level || undefined,
        priority: form.priority || undefined,
        customer_complaint: form.customer_complaint || undefined,
        notes: form.notes || undefined,
    });

    return (
        <form className="space-y-5" onSubmit={async (event) => {
            event.preventDefault();
            setSubmitting(true);
            setError(null);
            try {
                const saved = job
                    ? await updateVehicleServiceJob(job.id, payload())
                    : await createVehicleServiceJob(payload());
                navigate(`/vehicle-service/jobs/${saved.id}`);
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setSubmitting(false);
            }
        }}>
            <ErrorAlert error={error} />
            <Panel title="Service job">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <GenericLookupSelect label="Customer" value={customer} onChange={(value) => { setCustomer(value); setVehicle(null); }} search={searchCustomer} formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()} error={errorFor('customer_id')} />
                    <GenericLookupSelect label="Vehicle" value={vehicle} onChange={setVehicle} search={searchVehicle} formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()} error={errorFor('vehicle_id')} placeholder={customer ? 'Search customer vehicles' : 'Select a customer first'} />
                    <GenericLookupSelect label="Supervisor" value={supervisor} onChange={setSupervisor} search={searchSupervisor} formatLabel={(value) => `${value.code ?? ''} ${value.name}`.trim()} error={errorFor('supervisor_employee_id')} />
                    <Input label="Job date" type="date" value={form.job_date} error={errorFor('job_date')} onChange={(event) => setForm({ ...form, job_date: event.target.value })} />
                    <Input label="Expected delivery" type="date" value={form.expected_delivery_date} error={errorFor('expected_delivery_date')} onChange={(event) => setForm({ ...form, expected_delivery_date: event.target.value })} />
                    <Input label="Odometer" type="number" min="0" step="0.000001" value={form.odometer_reading} error={errorFor('odometer_reading')} onChange={(event) => setForm({ ...form, odometer_reading: event.target.value })} />
                    <Input label="Fuel level" value={form.fuel_level} error={errorFor('fuel_level')} onChange={(event) => setForm({ ...form, fuel_level: event.target.value })} />
                    <Select label="Priority" value={form.priority} options={[
                        { value: 'low', label: 'Low' },
                        { value: 'normal', label: 'Normal' },
                        { value: 'high', label: 'High' },
                        { value: 'urgent', label: 'Urgent' },
                    ]} onChange={(event) => setForm({ ...form, priority: event.target.value })} />
                    <Select label="Supervisor commission" value={form.supervisor_commission_type} options={[
                        { value: 'none', label: 'None' },
                        { value: 'fixed', label: 'Fixed' },
                        { value: 'percentage', label: 'Percentage' },
                    ]} onChange={(event) => setForm({ ...form, supervisor_commission_type: event.target.value as CommissionType })} />
                    <Input label="Commission value" type="number" min="0" step="0.000001" value={form.supervisor_commission_value} error={errorFor('supervisor_commission_value')} onChange={(event) => setForm({ ...form, supervisor_commission_value: event.target.value })} />
                </div>
                <div className="mt-4 grid gap-4 lg:grid-cols-2">
                    <Textarea label="Customer complaint" value={form.customer_complaint} error={errorFor('customer_complaint')} onChange={(event) => setForm({ ...form, customer_complaint: event.target.value })} />
                    <Textarea label="Notes" value={form.notes} error={errorFor('notes')} onChange={(event) => setForm({ ...form, notes: event.target.value })} />
                </div>
            </Panel>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                <Button type="submit" loading={submitting}>{job ? 'Save job' : 'Save draft'}</Button>
            </div>
        </form>
    );
}
