import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import type { VehicleFormInput, VehicleStatus } from '../types/vehicle.types';

type VehicleFormProps = {
    initialValue: VehicleFormInput;
    isSubmitting?: boolean;
    onSubmit: (value: VehicleFormInput) => Promise<void> | void;
};

const statusOptions = [
    { label: 'Draft', value: 'draft' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'In Service', value: 'in_service' },
    { label: 'In Rental', value: 'in_rental' },
    { label: 'Under Maintenance', value: 'under_maintenance' },
    { label: 'Unavailable', value: 'unavailable' },
    { label: 'Sold', value: 'sold' },
    { label: 'Archived', value: 'archived' },
];

const usageProfileOptions = [
    { label: 'Dual', value: 'dual' },
    { label: 'Service Only', value: 'service_only' },
    { label: 'Rent Only', value: 'rent_only' },
    { label: 'Internal', value: 'internal' },
];

function Field({
    children,
    label,
}: {
    children: ReactNode;
    label: string;
}) {
    return (
        <label className="block">
            <span className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</span>
            <div className="mt-1">{children}</div>
        </label>
    );
}

export function VehicleForm({ initialValue, isSubmitting = false, onSubmit }: VehicleFormProps) {
    const [value, setValue] = useState<VehicleFormInput>(initialValue);

    const update = <TKey extends keyof VehicleFormInput>(key: TKey, nextValue: VehicleFormInput[TKey]) => {
        setValue((current) => ({ ...current, [key]: nextValue }));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        void onSubmit(value);
    };

    return (
        <form className="space-y-5" onSubmit={submit}>
            <Card className="p-5">
                <h2 className="text-base font-bold text-slate-950">Vehicle Identity</h2>
                <div className="mt-4 grid gap-4 md:grid-cols-3">
                    <Field label="Vehicle code">
                        <Input value={value.code} onChange={(event) => update('code', event.target.value)} placeholder="VEH-001" />
                    </Field>
                    <Field label="Registration number">
                        <Input value={value.registrationNumber} onChange={(event) => update('registrationNumber', event.target.value)} placeholder="WP ABC-1234" />
                    </Field>
                    <Field label="VIN / chassis">
                        <Input value={value.vin} onChange={(event) => update('vin', event.target.value)} />
                    </Field>
                    <Field label="Brand / make">
                        <Input value={value.brand} onChange={(event) => update('brand', event.target.value)} />
                    </Field>
                    <Field label="Model">
                        <Input value={value.model} onChange={(event) => update('model', event.target.value)} />
                    </Field>
                    <Field label="Year">
                        <Input inputMode="numeric" value={value.year} onChange={(event) => update('year', event.target.value)} />
                    </Field>
                    <Field label="Category">
                        <Input value={value.category} onChange={(event) => update('category', event.target.value)} placeholder="Van, Pickup, Sedan" />
                    </Field>
                    <Field label="Color">
                        <Input value={value.color} onChange={(event) => update('color', event.target.value)} />
                    </Field>
                    <Field label="Current odometer">
                        <Input inputMode="numeric" value={value.currentOdometer} onChange={(event) => update('currentOdometer', event.target.value)} />
                    </Field>
                </div>
            </Card>

            <Card className="p-5">
                <h2 className="text-base font-bold text-slate-950">Profile & Eligibility</h2>
                <div className="mt-4 grid gap-4 md:grid-cols-3">
                    <Field label="Usage profile">
                        <Select options={usageProfileOptions} value={value.usageProfile} onChange={(event) => update('usageProfile', event.target.value)} />
                    </Field>
                    <Field label="Status">
                        <Select options={statusOptions} value={value.status} onChange={(event) => update('status', event.target.value as VehicleStatus)} />
                    </Field>
                    <Field label="Fuel type">
                        <Input value={value.fuelType} onChange={(event) => update('fuelType', event.target.value)} />
                    </Field>
                    <Field label="Transmission">
                        <Input value={value.transmissionType} onChange={(event) => update('transmissionType', event.target.value)} />
                    </Field>
                    <Field label="Registration expiry">
                        <Input type="date" value={value.registrationExpiry} onChange={(event) => update('registrationExpiry', event.target.value)} />
                    </Field>
                    <Field label="Insurance expiry">
                        <Input type="date" value={value.insuranceExpiry} onChange={(event) => update('insuranceExpiry', event.target.value)} />
                    </Field>
                </div>
                <div className="mt-4 grid gap-3 md:grid-cols-2">
                    <label className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-800">
                        <Checkbox checked={value.serviceEnabled} onChange={(event) => update('serviceEnabled', event.target.checked)} />
                        Service enabled
                    </label>
                    <label className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-800">
                        <Checkbox checked={value.rentalEnabled} onChange={(event) => update('rentalEnabled', event.target.checked)} />
                        Rental enabled
                    </label>
                </div>
            </Card>

            <div className="flex justify-end">
                <Button disabled={isSubmitting} type="submit">
                    {isSubmitting ? 'Saving...' : 'Save Vehicle'}
                </Button>
            </div>
        </form>
    );
}
