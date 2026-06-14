import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { RentalPartySelect } from '../components/RentalPartySelect';
import { createRentalReservation } from '../vehicleRentalApi';
import type { RentalDirection, RentalPartyType, RentalType } from '../vehicleRentalTypes';

const localDateTime = (days = 0) => {
    const date = new Date(Date.now() + days * 86_400_000);
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
};

export default function RentalReservationCreatePage() {
    const navigate = useNavigate();
    const [party, setParty] = useState<NamedResource | null>(null);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [form, setForm] = useState({
        direction: 'outbound' as RentalDirection,
        party_type: 'customer' as RentalPartyType,
        rental_type: 'daily' as RentalType,
        start_at: localDateTime(),
        expected_end_at: localDateTime(1),
        remarks: '',
    });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    return (
        <>
            <ContentHeader title="New rental reservation" description="Reserve a period and optional vehicle before creating the agreement." />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                if (busy) return;
                setBusy(true);
                setError(null);
                try {
                    await createRentalReservation({
                        ...form,
                        party_id: party?.id,
                        vehicle_id: vehicle?.id,
                        remarks: form.remarks || undefined,
                    });
                    navigate('/vehicle-rental/reservations');
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setBusy(false);
                }
            }}>
                <ErrorAlert error={error} />
                <Panel title="Reservation">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Select label="Direction" value={form.direction} options={[
                            { value: 'outbound', label: 'Outbound rental' },
                            { value: 'inbound', label: 'Inbound hire-in' },
                        ]} onChange={(event) => {
                            const direction = event.target.value as RentalDirection;
                            setParty(null);
                            setForm({ ...form, direction, party_type: direction === 'outbound' ? 'customer' : 'supplier' });
                        }} />
                        {form.direction === 'inbound' && <Select label="Party type" value={form.party_type} options={[
                            { value: 'supplier', label: 'Supplier' },
                            { value: 'owner', label: 'Owner' },
                        ]} onChange={(event) => { setParty(null); setForm({ ...form, party_type: event.target.value as RentalPartyType }); }} />}
                        <RentalPartySelect partyType={form.party_type} value={party} onChange={setParty} error={fieldError(error, 'party_id')} />
                        <Select label="Rental type" value={form.rental_type} options={['hourly', 'daily', 'weekly', 'monthly', 'lease', 'subscription', 'with_driver', 'without_driver'].map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setForm({ ...form, rental_type: event.target.value as RentalType })} />
                        <VehicleLookupSelect value={vehicle} onChange={setVehicle} error={fieldError(error, 'vehicle_id')} />
                        <Input label="Start" type="datetime-local" value={form.start_at} error={fieldError(error, 'start_at')} onChange={(event) => setForm({ ...form, start_at: event.target.value })} />
                        <Input label="Expected end" type="datetime-local" value={form.expected_end_at} error={fieldError(error, 'expected_end_at')} onChange={(event) => setForm({ ...form, expected_end_at: event.target.value })} />
                        <div className="md:col-span-2 xl:col-span-3"><Textarea label="Remarks" value={form.remarks} onChange={(event) => setForm({ ...form, remarks: event.target.value })} /></div>
                    </div>
                </Panel>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                    <Button type="submit" loading={busy}>Save reservation</Button>
                </div>
            </form>
        </>
    );
}
