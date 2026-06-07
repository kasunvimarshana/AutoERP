import { useEffect, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { getVehicleServiceInspection, saveVehicleServiceInspection } from '../vehicleServiceApi';

export default function VehicleServiceInspectionTab({ jobId }: { jobId: number }) {
    const result = useApi((signal) => getVehicleServiceInspection(jobId, signal), [jobId]);
    const [form, setForm] = useState({ customer_complaint: '', inspection_notes: '', diagnosis: '', recommended_work: '', odometer_reading: '', fuel_level: '' });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    useEffect(() => {
        if (result.data) setForm({
            customer_complaint: result.data.customer_complaint ?? '',
            inspection_notes: result.data.inspection_notes ?? '',
            diagnosis: result.data.diagnosis ?? '',
            recommended_work: result.data.recommended_work ?? '',
            odometer_reading: result.data.odometer_reading ?? '',
            fuel_level: result.data.fuel_level ?? '',
        });
    }, [result.data]);
    if (result.loading) return <LoadingState />;

    return (
        <form className="space-y-4" onSubmit={async (event) => {
            event.preventDefault();
            setSaving(true);
            setError(null);
            try {
                result.setData(await saveVehicleServiceInspection(jobId, form));
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setSaving(false);
            }
        }}>
            <ErrorAlert error={error ?? result.error} />
            <div className="grid gap-4 lg:grid-cols-2">
                <Textarea label="Customer complaint" value={form.customer_complaint} error={fieldError(error, 'customer_complaint')} onChange={(event) => setForm({ ...form, customer_complaint: event.target.value })} />
                <Textarea label="Inspection notes" value={form.inspection_notes} error={fieldError(error, 'inspection_notes')} onChange={(event) => setForm({ ...form, inspection_notes: event.target.value })} />
                <Textarea label="Diagnosis" value={form.diagnosis} error={fieldError(error, 'diagnosis')} onChange={(event) => setForm({ ...form, diagnosis: event.target.value })} />
                <Textarea label="Recommended work" value={form.recommended_work} error={fieldError(error, 'recommended_work')} onChange={(event) => setForm({ ...form, recommended_work: event.target.value })} />
                <DecimalInput label="Odometer" value={form.odometer_reading} onChange={(event) => setForm({ ...form, odometer_reading: event.target.value })} />
                <Input label="Fuel level" value={form.fuel_level} onChange={(event) => setForm({ ...form, fuel_level: event.target.value })} />
            </div>
            <Button type="submit" loading={saving}>Save inspection</Button>
        </form>
    );
}
