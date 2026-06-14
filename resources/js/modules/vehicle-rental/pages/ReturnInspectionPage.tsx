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
import { getRentalAgreement, saveReturnInspection } from '../vehicleRentalApi';

const nowLocal = () => {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
};

export default function ReturnInspectionPage() {
    const agreementId = Number(useParams().id);
    const allocationId = Number(useParams().allocationId);
    const navigate = useNavigate();
    const agreement = useApi((signal) => getRentalAgreement(agreementId, signal), [agreementId]);
    const allocation = agreement.data?.vehicles.find((row) => row.id === allocationId);
    const [form, setForm] = useState({ inspected_at: nowLocal(), odometer: '', fuel_level: '', condition_notes: '', damage_notes: '', damage_amount: '0.000000', is_damage_billable: false });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    if (agreement.loading) return <LoadingState />;
    if (!allocation) return <ErrorAlert error={agreement.error ?? new ApiError('Rental allocation was not found.', 404)} />;
    return (
        <>
            <ContentHeader title="Return inspection" description={`${agreement.data?.agreement_number} / ${allocation.vehicle?.registration_number ?? allocation.vehicle?.name}`} />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                setBusy(true);
                setError(null);
                try {
                    await saveReturnInspection(agreementId, allocationId, { ...form, fuel_level: form.fuel_level || undefined });
                    navigate(`/vehicle-rental/agreements/${agreementId}`);
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setBusy(false);
                }
            }}>
                <ErrorAlert error={error} />
                <Panel title="Return condition">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Input label="Inspected at" type="datetime-local" value={form.inspected_at} error={fieldError(error, 'inspected_at')} onChange={(event) => setForm({ ...form, inspected_at: event.target.value })} />
                        <DecimalInput label="Odometer" value={form.odometer} error={fieldError(error, 'odometer')} onChange={(event) => setForm({ ...form, odometer: event.target.value })} />
                        <DecimalInput label="Fuel level %" value={form.fuel_level} error={fieldError(error, 'fuel_level')} onChange={(event) => setForm({ ...form, fuel_level: event.target.value })} />
                        <DecimalInput label="Damage amount" value={form.damage_amount} error={fieldError(error, 'damage_amount')} onChange={(event) => setForm({ ...form, damage_amount: event.target.value })} />
                        <label className="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" checked={form.is_damage_billable} onChange={(event) => setForm({ ...form, is_damage_billable: event.target.checked })} /> Bill damage to rental party</label>
                        <Textarea label="Condition notes" value={form.condition_notes} onChange={(event) => setForm({ ...form, condition_notes: event.target.value })} />
                        <div className="md:col-span-2"><Textarea label="Damage notes" value={form.damage_notes} onChange={(event) => setForm({ ...form, damage_notes: event.target.value })} /></div>
                    </div>
                </Panel>
                <div className="flex justify-end"><Button type="submit" loading={busy}>Save return inspection</Button></div>
            </form>
        </>
    );
}
