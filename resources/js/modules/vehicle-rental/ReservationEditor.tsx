import { useEffect, useState, type FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { requestLookup } from '@/shared/api/lookupRequest';
import { endpoints } from '@/shared/api/endpoints';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { DriverMode, RentalBasis } from './agreements';
import { createReservation, updateReservation, type RentalReservation, type StoreReservationPayload } from './reservationApi';

const currencies = (params: LookupLoadParams) => requestLookup<NamedResource>(endpoints.currencies, params);
const vehicles = (params: LookupLoadParams) => requestLookup<Record<string, unknown>>(`${endpoints.vehicles}/lookup/active`, params)
    .then(result => ({ ...result, data: result.data.map(row => ({ id: Number(row.id), name: String(row.registration_number ?? row.vehicle_number), code: String(row.vehicle_number) })) }));

export function ReservationEditor({
    reservation,
    initialVehicleId,
    onSaved,
    onCancel,
}: {
    reservation?: RentalReservation;
    initialVehicleId?: number;
    onSaved: () => void;
    onCancel: () => void;
}) {
    const isEdit = Boolean(reservation);
    const [customer, setCustomer] = useState<NamedResource | null>(reservation?.customer ? { id: reservation.customer.id, name: reservation.customer.name } : null);
    const [vehicle, setVehicle] = useState<NamedResource | null>(reservation?.requested_vehicle ? { id: reservation.requested_vehicle.id, name: reservation.requested_vehicle.label } : null);
    const [rentalMode, setRentalMode] = useState(reservation ? reservation.rental_mode : DriverMode.SelfDrive);
    const [billingCycle, setBillingCycle] = useState(reservation ? reservation.billing_cycle : RentalBasis.Daily);
    const [startAt, setStartAt] = useState(reservation ? reservation.requested_start_at.slice(0, 16) : '');
    const [endAt, setEndAt] = useState(reservation ? reservation.requested_end_at.slice(0, 16) : '');
    const [currency, setCurrency] = useState<NamedResource | null>(reservation?.currency ? { id: reservation.currency.id, name: reservation.currency.code, code: reservation.currency.code } : null);
    const [estimatedAmount, setEstimatedAmount] = useState(reservation ? reservation.estimated_amount : '0.00');
    const [estimatedDeposit, setEstimatedDeposit] = useState(reservation ? reservation.estimated_deposit_amount : '0.00');
    const [source, setSource] = useState(reservation?.source ?? 'phone');
    const [remarks, setRemarks] = useState(reservation?.remarks ?? '');

    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        if (!reservation && initialVehicleId && !vehicle) {
            const controller = new AbortController();
            vehicles({ search: '', page: 1, perPage: 100, signal: controller.signal })
                .then(result => {
                    const found = result.data.find(v => v.id === initialVehicleId);
                    if (found) {
                        setVehicle(found);
                    }
                })
                .catch(() => {});
            return () => controller.abort();
        }
    }, [initialVehicleId, reservation, vehicle]);

    async function submit(event: FormEvent) {
        event.preventDefault();
        if (!customer || !currency || !startAt || !endAt) return;
        setSaving(true);
        setError(null);

        const payload: StoreReservationPayload = {
            customer_id: customer.id,
            requested_vehicle_id: vehicle ? vehicle.id : null,
            rental_mode: rentalMode,
            billing_cycle: billingCycle,
            requested_start_at: startAt,
            requested_end_at: endAt,
            currency_id: currency.id,
            estimated_amount: estimatedAmount.trim() || '0.00',
            estimated_deposit_amount: estimatedDeposit.trim() || '0.00',
            source: source.trim() || null,
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
                <LookupSelect
                    label="Customer"
                    value={customer}
                    onChange={setCustomer}
                    search={lookupApi.customers}
                    required
                    error={error?.fields.customer_id?.[0]}
                    loadOnOpen
                    minSearchLength={0}
                />
                <LookupSelect
                    label="Requested Vehicle (Optional)"
                    value={vehicle}
                    onChange={setVehicle}
                    search={vehicles}
                    error={error?.fields.requested_vehicle_id?.[0]}
                    loadOnOpen
                    minSearchLength={0}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Select
                    label="Rental Mode"
                    value={rentalMode}
                    onChange={e => setRentalMode(e.target.value)}
                    options={[
                        { value: DriverMode.SelfDrive, label: 'Self Drive' },
                        { value: DriverMode.WithDriver, label: 'With Driver' },
                    ]}
                />
                <Select
                    label="Billing Cycle"
                    value={billingCycle}
                    onChange={e => setBillingCycle(e.target.value)}
                    options={[
                        { value: RentalBasis.Daily, label: 'Daily Rental' },
                        { value: RentalBasis.Monthly, label: 'Monthly Rental' },
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
                    min={startAt || undefined}
                    onChange={e => setEndAt(e.target.value)}
                    required
                    error={error?.fields.requested_end_at?.[0]}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <LookupSelect
                    label="Currency"
                    value={currency}
                    onChange={setCurrency}
                    search={currencies}
                    required
                    error={error?.fields.currency_id?.[0]}
                    loadOnOpen
                    minSearchLength={0}
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
                <Button type="submit" loading={saving} disabled={!customer || !currency || !startAt || !endAt}>{isEdit ? 'Save Changes' : 'Create Reservation'}</Button>
                <Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button>
            </div>
        </form>
    );
}
