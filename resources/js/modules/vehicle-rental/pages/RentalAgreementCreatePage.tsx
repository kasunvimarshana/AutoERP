import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { RentalPartySelect } from '../components/RentalPartySelect';
import { createRentalAgreement, getRentalReservation } from '../vehicleRentalApi';
import type { RentalDirection, RentalPartyType, RentalType } from '../vehicleRentalTypes';

const localDateTime = (days = 0) => {
    const date = new Date(Date.now() + days * 86_400_000);
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
};
const today = () => new Date().toISOString().slice(0, 10);
const rateNames = [
    ['allowed_hours', 'Allowed hours'], ['allowed_km', 'Allowed KM'], ['extra_hour_rate', 'Extra hour rate'],
    ['extra_km_rate', 'Extra KM rate'], ['overtime_rate', 'Overtime rate'], ['double_overtime_rate', 'Double overtime rate'],
    ['night_shift_rate', 'Night shift rate'], ['weekend_rate', 'Weekend rate'], ['holiday_rate', 'Holiday rate'],
    ['driver_rate', 'Driver rate'], ['outstation_rate', 'Outstation rate'], ['day_out_rate', 'Day out rate'],
    ['night_out_rate', 'Night out rate'], ['fuel_rate', 'Fuel rate'], ['waiting_hour_rate', 'Waiting hour rate'],
] as const;

export default function RentalAgreementCreatePage() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const reservationId = Number(searchParams.get('reservation_id')) || 0;
    const requestedDirection = searchParams.get('direction') === 'inbound' ? 'inbound' : 'outbound';
    const reservation = useApi((signal) => reservationId ? getRentalReservation(reservationId, signal) : Promise.resolve(null), [reservationId]);
    const [party, setParty] = useState<NamedResource | null>(null);
    const [form, setForm] = useState({
        direction: requestedDirection as RentalDirection,
        party_type: (requestedDirection === 'inbound' ? 'supplier' : 'customer') as RentalPartyType,
        rental_type: 'daily' as RentalType,
        billing_cycle: 'final',
        billing_basis: 'contractual_period',
        proration_rule: 'exact_day_count',
        billing_timezone: 'UTC',
        billing_period_days: '',
        agreement_date: today(),
        start_at: localDateTime(),
        expected_end_at: localDateTime(1),
        remarks: '',
    });
    const [rate, setRate] = useState<Record<string, string>>({
        base_rate: '0.000000', rate_unit: 'day',
        ...Object.fromEntries(rateNames.map(([key]) => [key, '0.000000'])),
    });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    useEffect(() => {
        if (!reservation.data) return;
        setParty(reservation.data.party ?? null);
        setForm((current) => ({
            ...current,
            direction: reservation.data!.direction,
            party_type: reservation.data!.party_type,
            rental_type: reservation.data!.rental_type,
            start_at: reservation.data!.start_at.slice(0, 16),
            expected_end_at: reservation.data!.expected_end_at.slice(0, 16),
            remarks: reservation.data!.remarks ?? '',
        }));
    }, [reservation.data]);

    return (
        <>
            <ContentHeader title="New rental agreement" description={reservationId ? `Converting reservation ${reservation.data?.reservation_number ?? ''}` : 'Create the operational contract and freeze its rates.'} />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                if (busy) return;
                setBusy(true);
                setError(null);
                try {
                    const saved = await createRentalAgreement({
                        ...form,
                        billing_period_days: form.billing_period_days || undefined,
                        reservation_id: reservationId || undefined,
                        party_id: party?.id,
                        rate_snapshot: rate,
                        remarks: form.remarks || undefined,
                    });
                    navigate(`/vehicle-rental/agreements/${saved.id}`);
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setBusy(false);
                }
            }}>
                <ErrorAlert error={error ?? reservation.error} />
                <Panel title="Contract">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Select label="Direction" value={form.direction} disabled={Boolean(reservationId)} options={[{ value: 'outbound', label: 'Outbound rental' }, { value: 'inbound', label: 'Inbound hire-in' }]} onChange={(event) => {
                            const direction = event.target.value as RentalDirection;
                            setParty(null);
                            setForm({ ...form, direction, party_type: direction === 'outbound' ? 'customer' : 'supplier' });
                        }} />
                        {form.direction === 'inbound' && <Select label="Party type" value={form.party_type} disabled={Boolean(reservationId)} options={[{ value: 'supplier', label: 'Supplier' }, { value: 'owner', label: 'Owner' }]} onChange={(event) => { setParty(null); setForm({ ...form, party_type: event.target.value as RentalPartyType }); }} />}
                        <RentalPartySelect partyType={form.party_type} value={party} onChange={setParty} error={fieldError(error, 'party_id')} />
                        <Select label="Rental type" value={form.rental_type} options={['hourly', 'daily', 'weekly', 'monthly', 'lease', 'subscription', 'with_driver', 'without_driver'].map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setForm({ ...form, rental_type: event.target.value as RentalType })} />
                        <Select label="Billing cycle" value={form.billing_cycle} options={['hourly', 'per_trip', 'daily', 'weekly', 'monthly', 'anniversary_cycle', 'fixed_period', 'final'].map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setForm({ ...form, billing_cycle: event.target.value })} />
                        <Select label="Billing basis" value={form.billing_basis} options={['contractual_period', 'calendar_month', 'anniversary_month', 'fixed_30_day', 'exact_day_count'].map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setForm({ ...form, billing_basis: event.target.value })} />
                        <Select label="Proration" value={form.proration_rule} options={['exact_day_count', 'calendar_day', 'contractual_period'].map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setForm({ ...form, proration_rule: event.target.value })} />
                        <Input label="Billing time zone" value={form.billing_timezone} error={fieldError(error, 'billing_timezone')} onChange={(event) => setForm({ ...form, billing_timezone: event.target.value })} />
                        {form.billing_cycle === 'fixed_period' && <Input label="Billing period days" type="number" value={form.billing_period_days} error={fieldError(error, 'billing_period_days')} onChange={(event) => setForm({ ...form, billing_period_days: event.target.value })} />}
                        <Input label="Agreement date" type="date" value={form.agreement_date} error={fieldError(error, 'agreement_date')} onChange={(event) => setForm({ ...form, agreement_date: event.target.value })} />
                        <Input label="Start" type="datetime-local" value={form.start_at} error={fieldError(error, 'start_at')} onChange={(event) => setForm({ ...form, start_at: event.target.value })} />
                        <Input label="Expected end" type="datetime-local" value={form.expected_end_at} error={fieldError(error, 'expected_end_at')} onChange={(event) => setForm({ ...form, expected_end_at: event.target.value })} />
                        <div className="md:col-span-2 xl:col-span-4"><Textarea label="Terms / remarks" value={form.remarks} onChange={(event) => setForm({ ...form, remarks: event.target.value })} /></div>
                    </div>
                </Panel>
                <Panel title="Frozen rate snapshot">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <DecimalInput label="Base rate" value={rate.base_rate} error={fieldError(error, 'rate_snapshot.base_rate')} onChange={(event) => setRate({ ...rate, base_rate: event.target.value })} />
                        <Select label="Rate unit" value={rate.rate_unit} options={['hour', 'day', 'week', 'month', 'km', 'trip'].map((value) => ({ value, label: value }))} onChange={(event) => setRate({ ...rate, rate_unit: event.target.value })} />
                        {rateNames.map(([key, label]) => <DecimalInput key={key} label={label} value={rate[key]} error={fieldError(error, `rate_snapshot.${key}`)} onChange={(event) => setRate({ ...rate, [key]: event.target.value })} />)}
                    </div>
                </Panel>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                    <Button type="submit" loading={busy} disabled={reservation.loading}>Create agreement</Button>
                </div>
            </form>
        </>
    );
}
