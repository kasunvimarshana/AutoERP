import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { ApiError, fieldError, toApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { getRentalAgreement, savePickupInspection } from '../vehicleRentalApi';

const nowLocal = () => {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
};

export default function PickupInspectionPage() {
    const agreementId = Number(useParams().id);
    const allocationId = Number(useParams().allocationId);
    const navigate = useNavigate();
    const agreement = useApi((signal) => getRentalAgreement(agreementId, signal), [agreementId]);
    const allocation = agreement.data?.vehicles.find((row) => row.id === allocationId);
    const [form, setForm] = useState({ inspected_at: nowLocal(), odometer: '', fuel_level: '', condition_notes: '' });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    if (agreement.loading) return <LoadingState />;
    if (!allocation) return <ErrorAlert error={agreement.error ?? new ApiError('Rental allocation was not found.', 404)} />;
    return (
        <>
            <ContentHeader title="Pickup inspection" description={`${agreement.data?.agreement_number} / ${allocation.vehicle?.registration_number ?? allocation.vehicle?.name}`} />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                setBusy(true);
                setError(null);
                try {
                    await savePickupInspection(agreementId, allocationId, { ...form, odometer: form.odometer || allocation.start_odometer, fuel_level: form.fuel_level || undefined });
                    navigate(`/vehicle-rental/agreements/${agreementId}`);
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setBusy(false);
                }
            }}>
                <ErrorAlert error={error} />
                <Panel title="Handover condition">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Input label="Inspected at" type="datetime-local" value={form.inspected_at} error={fieldError(error, 'inspected_at')} onChange={(event) => setForm({ ...form, inspected_at: event.target.value })} />
                        <DecimalInput label="Odometer" value={form.odometer || allocation.start_odometer} error={fieldError(error, 'odometer')} onChange={(event) => setForm({ ...form, odometer: event.target.value })} />
                        <DecimalInput label="Fuel level %" value={form.fuel_level} error={fieldError(error, 'fuel_level')} onChange={(event) => setForm({ ...form, fuel_level: event.target.value })} />
                        <Textarea label="Condition notes" value={form.condition_notes} onChange={(event) => setForm({ ...form, condition_notes: event.target.value })} />
                    </div>
                </Panel>
                <div className="flex justify-end"><Button type="submit" loading={busy}>Save pickup inspection</Button></div>
            </form>
        </>
    );
}
