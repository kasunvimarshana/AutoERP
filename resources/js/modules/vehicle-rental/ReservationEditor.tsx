import { useState, useEffect, type FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { apiClient } from '@/shared/api/apiClient';
import { createReservation, updateReservation, type RentalReservation, type StoreReservationPayload } from './reservationApi';

interface Option { value: number; label: string }

export function ReservationEditor({
    reservation,
    onSaved,
    onCancel,
}: {
    reservation?: RentalReservation;
    onSaved: () => void;
    onCancel: () => void;
}) {
    const isEdit = Boolean(reservation);
    const [customerId, setCustomerId] = useState(reservation ? String(reservation.customer.id) : '');
    const [vehicleId, setVehicleId] = useState(reservation?.requested_vehicle ? String(reservation.requested_vehicle.id) : '');
    const [rentalMode, setRentalMode] = useState(reservation ? reservation.rental_mode : 'self_drive');
    const [billingCycle, setBillingCycle] = useState(reservation ? reservation.billing_cycle : 'daily');
    const [startAt, setStartAt] = useState(reservation ? reservation.requested_start_at.slice(0, 16) : '');
    const [endAt, setEndAt] = useState(reservation ? reservation.requested_end_at.slice(0, 16) : '');
    const [currencyId, setCurrencyId] = useState(reservation ? String(reservation.currency.id) : '');
    const [estimatedAmount, setEstimatedAmount] = useState(reservation ? reservation.estimated_amount : '0.00');
    const [estimatedDeposit, setEstimatedDeposit] = useState(reservation ? reservation.estimated_deposit_amount : '0.00');
    const [source, setSource] = useState(reservation?.source ?? 'phone');
    const [remarks, setRemarks] = useState(reservation?.remarks ?? '');

    const [customers, setCustomers] = useState<Option[]>([]);
    const [vehicles, setVehicles] = useState<Option[]>([]);
    const [currencies, setCurrencies] = useState<Option[]>([]);

    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        apiClient.get<{ data: Array<{ id: number; name: string }> }>('/api/v1/customers?per_page=100')
            .then(r => setCustomers(r.data.data.map(c => ({ value: c.id, label: c.name }))))
            .catch(() => {});
        apiClient.get<{ data: Array<{ id: number; vehicle_number: string; registration_number?: string }> }>('/api/v1/vehicles?per_page=100')
            .then(r => setVehicles(r.data.data.map(v => ({ value: v.id, label: v.registration_number ?? v.vehicle_number }))))
            .catch(() => {});
        apiClient.get<{ data: Array<{ id: number; code: string; name: string }> }>('/api/v1/reference-data/currencies')
            .then(r => {
                setCurrencies(r.data.data.map(c => ({ value: c.id, label: `${c.code} – ${c.name}` })));
                if (!currencyId && r.data.data.length > 0) setCurrencyId(String(r.data.data[0].id));
            })
            .catch(() => {});
    }, [currencyId]);

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        setError(null);

        const payload: StoreReservationPayload = {
            customer_id: Number(customerId),
            requested_vehicle_id: vehicleId ? Number(vehicleId) : null,
            rental_mode: rentalMode,
            billing_cycle: billingCycle,
            requested_start_at: startAt,
            requested_end_at: endAt,
            currency_id: Number(currencyId),
            estimated_amount: estimatedAmount,
            estimated_deposit_amount: estimatedDeposit,
            source,
            remarks: remarks.trim() || null,
        };

        try {
            if (isEdit && reservation) {
                await updateReservation(reservation.id, reservation.row_version, payload);
            } else {
                await createReservation(payload);
            }
            onSaved();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={submit} className="mt-4 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 className="text-lg font-semibold text-slate-900">{isEdit ? `Edit Reservation ${reservation?.reservation_number}` : 'New Rental Reservation'}</h3>
            <ErrorAlert error={error} inline />

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Select
                    label="Customer"
                    value={customerId}
                    onChange={e => setCustomerId(e.target.value)}
                    required
                    options={[{ value: '', label: 'Select customer…' }, ...customers]}
                    error={error?.fields.customer_id?.[0]}
                />
                <Select
                    label="Requested Vehicle (Optional)"
                    value={vehicleId}
                    onChange={e => setVehicleId(e.target.value)}
                    options={[{ value: '', label: 'Any available vehicle' }, ...vehicles]}
                    error={error?.fields.requested_vehicle_id?.[0]}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Select
                    label="Rental Mode"
                    value={rentalMode}
                    onChange={e => setRentalMode(e.target.value)}
                    options={[
                        { value: 'self_drive', label: 'Self Drive' },
                        { value: 'with_driver', label: 'With Driver' },
                    ]}
                />
                <Select
                    label="Billing Cycle"
                    value={billingCycle}
                    onChange={e => setBillingCycle(e.target.value)}
                    options={[
                        { value: 'daily', label: 'Daily Rental' },
                        { value: 'monthly', label: 'Monthly Rental' },
                    ]}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Input
                    label="Requested Start Date & Time"
                    type="datetime-local"
                    value={startAt}
                    onChange={e => setStartAt(e.target.value)}
                    required
                    error={error?.fields.requested_start_at?.[0]}
                />
                <Input
                    label="Requested End Date & Time"
                    type="datetime-local"
                    value={endAt}
                    onChange={e => setEndAt(e.target.value)}
                    required
                    error={error?.fields.requested_end_at?.[0]}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Select
                    label="Currency"
                    value={currencyId}
                    onChange={e => setCurrencyId(e.target.value)}
                    required
                    options={[{ value: '', label: 'Select currency…' }, ...currencies]}
                    error={error?.fields.currency_id?.[0]}
                />
                <Input
                    label="Estimated Rent Amount"
                    value={estimatedAmount}
                    onChange={e => setEstimatedAmount(e.target.value)}
                    inputMode="decimal"
                    error={error?.fields.estimated_amount?.[0]}
                />
                <Input
                    label="Estimated Deposit Amount"
                    value={estimatedDeposit}
                    onChange={e => setEstimatedDeposit(e.target.value)}
                    inputMode="decimal"
                    error={error?.fields.estimated_deposit_amount?.[0]}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Select
                    label="Inquiry Source"
                    value={source}
                    onChange={e => setSource(e.target.value)}
                    options={[
                        { value: 'phone', label: 'Phone Inquiry' },
                        { value: 'walk_in', label: 'Walk-in' },
                        { value: 'email', label: 'Email' },
                        { value: 'website', label: 'Website Booking' },
                    ]}
                />
                <Textarea
                    label="Remarks & Notes"
                    value={remarks}
                    onChange={e => setRemarks(e.target.value)}
                    rows={2}
                    error={error?.fields.remarks?.[0]}
                />
            </div>

            <div className="flex flex-wrap gap-2 border-t border-slate-200 pt-4">
                <Button type="submit" loading={saving}>{isEdit ? 'Save Changes' : 'Create Reservation'}</Button>
                <Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button>
            </div>
        </form>
    );
}
