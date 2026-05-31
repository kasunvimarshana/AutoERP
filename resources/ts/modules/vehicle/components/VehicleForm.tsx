import { FormEvent, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import type { Vehicle, VehicleFieldErrors, VehicleFormInput, VehicleStatus, VehicleUsageProfile } from '../types/vehicle.types';

type VehicleFormProps = {
    errors?: VehicleFieldErrors;
    globalError?: string;
    isSaving?: boolean;
    mode: 'create' | 'edit';
    onSubmit: (input: VehicleFormInput) => Promise<void> | void;
    vehicle?: Vehicle;
};

const statusOptions: Array<{ label: string; value: VehicleStatus }> = [
    { label: 'Draft', value: 'draft' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'In service', value: 'in_service' },
    { label: 'In rental', value: 'in_rental' },
    { label: 'Under maintenance', value: 'under_maintenance' },
    { label: 'Unavailable', value: 'unavailable' },
    { label: 'Sold', value: 'sold' },
    { label: 'Archived', value: 'archived' },
];

const usageProfileOptions: Array<{ label: string; value: VehicleUsageProfile }> = [
    { label: 'Dual', value: 'dual' },
    { label: 'Service only', value: 'service_only' },
    { label: 'Rental only', value: 'rent_only' },
    { label: 'Internal', value: 'internal' },
];

function Field({ children, label }: { children: ReactNode; label: string }) {
    return (
        <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{label}</label>
            {children}
        </div>
    );
}

function formString(formData: FormData, key: string): string {
    return String(formData.get(key) ?? '').trim();
}

function formBoolean(formData: FormData, key: string): boolean {
    return formData.get(key) === 'true';
}

function firstError(errors: VehicleFieldErrors, ...keys: string[]): string | undefined {
    for (const key of keys) {
        const message = errors[key]?.[0];
        if (message) {
            return message;
        }
    }

    return undefined;
}

function vehicleValue(vehicle: Vehicle | undefined, key: keyof Vehicle, fallback = ''): string {
    return vehicle?.[key] === undefined || vehicle?.[key] === null ? fallback : String(vehicle[key]);
}

function buildVehicleInput(formData: FormData): VehicleFormInput {
    return {
        category: formString(formData, 'category'),
        code: formString(formData, 'vehicle_code'),
        color: formString(formData, 'color'),
        currentOdometer: formString(formData, 'current_odometer') || '0',
        fuelType: formString(formData, 'fuel_type'),
        insuranceExpiry: formString(formData, 'insurance_expiry'),
        lastServiceDate: formString(formData, 'last_service_date'),
        lastServiceOdometer: formString(formData, 'last_service_odometer'),
        make: formString(formData, 'make'),
        model: formString(formData, 'model'),
        nextServiceDueDate: formString(formData, 'next_service_due_date'),
        nextServiceDueOdometer: formString(formData, 'next_service_due_odometer'),
        registrationExpiry: formString(formData, 'registration_expiry'),
        registrationNumber: formString(formData, 'license_plate'),
        rentalEnabled: formBoolean(formData, 'rental_enabled'),
        seatingCapacity: formString(formData, 'seating_capacity'),
        serviceEnabled: formBoolean(formData, 'service_enabled'),
        status: (formString(formData, 'status') || 'draft') as VehicleStatus,
        transmission: formString(formData, 'transmission'),
        usageProfile: (formString(formData, 'usage_profile') || 'dual') as VehicleUsageProfile,
        vin: formString(formData, 'vin'),
        year: formString(formData, 'year'),
    };
}

export function vehicleToFormDefaults(vehicle?: Vehicle): VehicleFormInput {
    return {
        category: vehicleValue(vehicle, 'category'),
        code: vehicleValue(vehicle, 'code'),
        color: vehicleValue(vehicle, 'color'),
        currentOdometer: vehicleValue(vehicle, 'currentOdometer', '0'),
        fuelType: vehicleValue(vehicle, 'fuelType'),
        insuranceExpiry: vehicleValue(vehicle, 'insuranceExpiry'),
        lastServiceDate: vehicleValue(vehicle, 'lastServiceDate'),
        lastServiceOdometer: vehicleValue(vehicle, 'lastServiceOdometer'),
        make: vehicleValue(vehicle, 'make'),
        model: vehicleValue(vehicle, 'model'),
        nextServiceDueDate: vehicleValue(vehicle, 'nextServiceDueDate'),
        nextServiceDueOdometer: vehicleValue(vehicle, 'nextServiceDueOdometer'),
        registrationExpiry: vehicleValue(vehicle, 'registrationExpiry'),
        registrationNumber: vehicleValue(vehicle, 'registrationNumber'),
        rentalEnabled: vehicle?.rentalEnabled ?? false,
        seatingCapacity: vehicleValue(vehicle, 'seatingCapacity'),
        serviceEnabled: vehicle?.serviceEnabled ?? true,
        status: (vehicle?.status || 'draft') as VehicleStatus,
        transmission: vehicleValue(vehicle, 'transmission'),
        usageProfile: (vehicle?.usageProfile || 'dual') as VehicleUsageProfile,
        vin: vehicleValue(vehicle, 'vin'),
        year: vehicleValue(vehicle, 'year'),
    };
}

export function VehicleForm({ errors = {}, globalError = '', isSaving = false, mode, onSubmit, vehicle }: VehicleFormProps) {
    const defaults = vehicleToFormDefaults(vehicle);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        void onSubmit(buildVehicleInput(new FormData(event.currentTarget)));
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {globalError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{globalError}</div> : null}

                <FormSection
                    description="Vehicle identity and eligibility are persisted by the Vehicle backend. Ownership is managed separately as history."
                    title="Vehicle Identity"
                >
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Field label="Vehicle code">
                            <Input defaultValue={defaults.code} name="vehicle_code" placeholder="VEH-0001" />
                            <FieldError message={firstError(errors, 'vehicle_code')} />
                        </Field>
                        <Field label="Registration number">
                            <Input defaultValue={defaults.registrationNumber} name="license_plate" placeholder="WP ABC-1234" />
                            <FieldError message={firstError(errors, 'license_plate')} />
                        </Field>
                        <Field label="VIN / chassis">
                            <Input defaultValue={defaults.vin} name="vin" />
                            <FieldError message={firstError(errors, 'vin')} />
                        </Field>
                        <Field label="Make / brand">
                            <Input defaultValue={defaults.make} name="make" placeholder="Toyota" />
                            <FieldError message={firstError(errors, 'make')} />
                        </Field>
                        <Field label="Model">
                            <Input defaultValue={defaults.model} name="model" placeholder="HiAce" />
                            <FieldError message={firstError(errors, 'model')} />
                        </Field>
                        <Field label="Year">
                            <Input defaultValue={defaults.year} inputMode="numeric" name="year" />
                            <FieldError message={firstError(errors, 'year')} />
                        </Field>
                        <Field label="Category">
                            <Input defaultValue={defaults.category} name="category" placeholder="Van, sedan, pickup" />
                            <FieldError message={firstError(errors, 'category')} />
                        </Field>
                        <Field label="Color">
                            <Input defaultValue={defaults.color} name="color" />
                            <FieldError message={firstError(errors, 'color')} />
                        </Field>
                        <Field label="Current odometer">
                            <Input defaultValue={defaults.currentOdometer} inputMode="numeric" name="current_odometer" />
                            <FieldError message={firstError(errors, 'current_odometer')} />
                        </Field>
                    </div>
                </FormSection>

                <FormSection description="Backend validates status and eligibility. Service and rental workflows only consume this context." title="Profile">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Field label="Usage profile">
                            <Select defaultValue={defaults.usageProfile} name="usage_profile" options={usageProfileOptions} />
                            <FieldError message={firstError(errors, 'usage_profile')} />
                        </Field>
                        <Field label="Status">
                            <Select defaultValue={defaults.status} name="status" options={statusOptions} />
                            <FieldError message={firstError(errors, 'status')} />
                        </Field>
                        <Field label="Fuel type">
                            <Input defaultValue={defaults.fuelType} name="fuel_type" />
                            <FieldError message={firstError(errors, 'fuel_type')} />
                        </Field>
                        <Field label="Transmission">
                            <Input defaultValue={defaults.transmission} name="transmission" />
                            <FieldError message={firstError(errors, 'transmission')} />
                        </Field>
                        <Field label="Seating capacity">
                            <Input defaultValue={defaults.seatingCapacity} inputMode="numeric" name="seating_capacity" />
                            <FieldError message={firstError(errors, 'seating_capacity')} />
                        </Field>
                        <div className="grid gap-3 md:col-span-2 xl:col-span-1">
                            <label className="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800">
                                <Checkbox defaultChecked={defaults.serviceEnabled} name="service_enabled" value="true" />
                                Service enabled
                            </label>
                            <label className="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800">
                                <Checkbox defaultChecked={defaults.rentalEnabled} name="rental_enabled" value="true" />
                                Rental enabled
                            </label>
                        </div>
                    </div>
                </FormSection>

                <FormSection description="Registration, insurance, and maintenance due status are displayed as backend fields. The frontend does not calculate compliance or due state." title="Registration, Insurance, and Service References">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Field label="Registration expiry">
                            <Input defaultValue={defaults.registrationExpiry} name="registration_expiry" type="date" />
                            <FieldError message={firstError(errors, 'registration_expiry')} />
                        </Field>
                        <Field label="Insurance expiry">
                            <Input defaultValue={defaults.insuranceExpiry} name="insurance_expiry" type="date" />
                            <FieldError message={firstError(errors, 'insurance_expiry')} />
                        </Field>
                        <Field label="Last service date">
                            <Input defaultValue={defaults.lastServiceDate} name="last_service_date" type="date" />
                            <FieldError message={firstError(errors, 'last_service_date')} />
                        </Field>
                        <Field label="Last service odometer">
                            <Input defaultValue={defaults.lastServiceOdometer} inputMode="numeric" name="last_service_odometer" />
                            <FieldError message={firstError(errors, 'last_service_odometer')} />
                        </Field>
                        <Field label="Next service due date">
                            <Input defaultValue={defaults.nextServiceDueDate} name="next_service_due_date" type="date" />
                            <FieldError message={firstError(errors, 'next_service_due_date')} />
                        </Field>
                        <Field label="Next service due odometer">
                            <Input defaultValue={defaults.nextServiceDueOdometer} inputMode="numeric" name="next_service_due_odometer" />
                            <FieldError message={firstError(errors, 'next_service_due_odometer')} />
                        </Field>
                    </div>
                </FormSection>

                <div className="flex justify-end gap-3">
                    <Link to={vehicle ? `/vehicles/${vehicle.id}` : '/vehicles'}>
                        <Button type="button" variant="secondary">Cancel</Button>
                    </Link>
                    <Button disabled={isSaving} type="submit" variant="blue">
                        {isSaving ? 'Saving...' : mode === 'edit' ? 'Update Vehicle' : 'Create Vehicle'}
                    </Button>
                </div>
            </div>

            <PreviewPanel
                rows={[
                    { label: 'Ownership', value: 'History-aware tab after save' },
                    { label: 'Availability', value: 'Backend validation endpoint' },
                    { label: 'Billing/provider payable', value: 'Owned by business modules' },
                ]}
                subtitle="The form collects data only. Backend APIs own validation, status, and eligibility decisions."
                title="Backend-owned boundaries"
            />
        </form>
    );
}
