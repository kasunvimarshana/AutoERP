import { useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Textarea } from '@/shared/components/Textarea';
import { saveVehicleServiceInspection } from '../vehicleServiceApi';
import type { VehicleServiceInspection, VehicleServiceInspectionPayload } from '../vehicleServiceTypes';

const emptyInspection: VehicleServiceInspectionPayload = {
    customer_complaint: '',
    inspection_notes: '',
    diagnosis: '',
    recommended_work: '',
    odometer_reading: '',
    fuel_level: '',
};

function inspectionPayload(inspection: VehicleServiceInspection | null): VehicleServiceInspectionPayload {
    if (!inspection) return { ...emptyInspection };
    return {
        customer_complaint: inspection.customer_complaint ?? '',
        inspection_notes: inspection.inspection_notes ?? '',
        diagnosis: inspection.diagnosis ?? '',
        recommended_work: inspection.recommended_work ?? '',
        odometer_reading: inspection.odometer_reading ?? '',
        fuel_level: inspection.fuel_level ?? '',
    };
}

export default function VehicleServiceInspectionTab({
    jobId,
    expectedVersion,
    initialValue,
    onSaved,
}: {
    jobId: number;
    expectedVersion: number;
    initialValue?: VehicleServiceInspection | null;
    onSaved: (inspection: VehicleServiceInspection, nextVersion: number) => void;
}) {
    return (
        <VehicleServiceInspectionEditor
            jobId={jobId}
            expectedVersion={expectedVersion}
            initialValue={initialValue ?? null}
            onJobSaved={onSaved}
        />
    );
}

function VehicleServiceInspectionEditor({ jobId, expectedVersion, initialValue, onJobSaved }: {
    jobId: number;
    expectedVersion: number;
    initialValue: VehicleServiceInspection | null;
    onJobSaved: (inspection: VehicleServiceInspection, nextVersion: number) => void;
}) {
    const [form, setForm] = useState<VehicleServiceInspectionPayload>(() => inspectionPayload(initialValue));
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    return (
        <form className="space-y-4" onSubmit={async (event) => {
            event.preventDefault();
            setSaving(true);
            setError(null);
            try {
                const inspection = await saveVehicleServiceInspection(jobId, { ...form, expected_version: expectedVersion });
                setForm(inspectionPayload(inspection));
                onJobSaved(inspection, expectedVersion + 1);
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setSaving(false);
            }
        }}>
            <ErrorAlert error={error} />
            <div className="grid gap-4 lg:grid-cols-2">
                <Textarea label="Customer complaint" value={form.customer_complaint ?? ''} error={fieldError(error, 'customer_complaint')} onChange={(event) => setForm({ ...form, customer_complaint: event.target.value })} />
                <Textarea label="Inspection notes" value={form.inspection_notes ?? ''} error={fieldError(error, 'inspection_notes')} onChange={(event) => setForm({ ...form, inspection_notes: event.target.value })} />
                <Textarea label="Diagnosis" value={form.diagnosis ?? ''} error={fieldError(error, 'diagnosis')} onChange={(event) => setForm({ ...form, diagnosis: event.target.value })} />
                <Textarea label="Recommended work" value={form.recommended_work ?? ''} error={fieldError(error, 'recommended_work')} onChange={(event) => setForm({ ...form, recommended_work: event.target.value })} />
                <DecimalInput label="Odometer" value={form.odometer_reading ?? ''} error={fieldError(error, 'odometer_reading')} onChange={(event) => setForm({ ...form, odometer_reading: event.target.value })} />
                <Input label="Fuel level" value={form.fuel_level ?? ''} error={fieldError(error, 'fuel_level')} onChange={(event) => setForm({ ...form, fuel_level: event.target.value })} />
            </div>
            <Button type="submit" loading={saving}>Save inspection</Button>
        </form>
    );
}
