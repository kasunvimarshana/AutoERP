import { FormEvent, useState, type ReactNode } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { FormSection } from '../../../shared/components/erp/ErpUi';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import type { VehicleInput } from '../types/vehicle.types';

const emptyInput: VehicleInput = {
    registrationNumber: '',
    status: 'active',
    vehicleCode: '',
};

type VehicleFormProps = {
    initialValue?: VehicleInput;
    onCancel: () => void;
    onSubmit: (input: VehicleInput) => Promise<void>;
    submitLabel: string;
};

function fieldClass(hasError: boolean) {
    return hasError ? 'border-red-300 focus:border-red-400 focus:ring-red-100' : '';
}

export function VehicleForm({
    initialValue = emptyInput,
    onCancel,
    onSubmit,
    submitLabel,
}: VehicleFormProps) {
    const [value, setValue] = useState<VehicleInput>(initialValue);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [submitting, setSubmitting] = useState(false);

    function update<K extends keyof VehicleInput>(key: K, nextValue: VehicleInput[K]) {
        setValue((current) => ({ ...current, [key]: nextValue }));
    }

    function validate() {
        const nextErrors: Record<string, string[]> = {};

        if (!value.vehicleCode.trim()) nextErrors.vehicle_code = ['Vehicle code is required.'];
        if (!value.registrationNumber.trim()) nextErrors.registration_number = ['Registration number is required.'];
        if (value.year !== undefined && (!Number.isInteger(value.year) || value.year < 1886 || value.year > new Date().getFullYear() + 1)) {
            nextErrors.year = [`Year must be between 1886 and ${new Date().getFullYear() + 1}.`];
        }

        setErrors(nextErrors);

        return Object.keys(nextErrors).length === 0;
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setFormError('');

        if (!validate()) return;

        setSubmitting(true);

        try {
            await onSubmit(value);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save this vehicle.');
            }
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <form className="space-y-6" onSubmit={handleSubmit}>
            {formError ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

            <FormSection title="Basic information">
                <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Vehicle code" error={errors.vehicle_code?.[0]}>
                        <Input className={fieldClass(Boolean(errors.vehicle_code))} value={value.vehicleCode} onChange={(event) => update('vehicleCode', event.target.value)} />
                    </Field>
                    <Field label="Registration number" error={errors.registration_number?.[0]}>
                        <Input className={fieldClass(Boolean(errors.registration_number))} value={value.registrationNumber} onChange={(event) => update('registrationNumber', event.target.value)} />
                    </Field>
                    <Field label="Chassis number">
                        <Input value={value.chassisNumber ?? ''} onChange={(event) => update('chassisNumber', event.target.value)} />
                    </Field>
                    <Field label="Engine number">
                        <Input value={value.engineNumber ?? ''} onChange={(event) => update('engineNumber', event.target.value)} />
                    </Field>
                    <Field label="Organization unit ID" error={errors.organization_unit_id?.[0]}>
                        <Input className={fieldClass(Boolean(errors.organization_unit_id))} inputMode="numeric" value={value.organizationUnitId ?? ''} onChange={(event) => update('organizationUnitId', event.target.value ? Number(event.target.value) : undefined)} />
                    </Field>
                    <Field label="Status">
                        <select className="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={value.status} onChange={(event) => update('status', event.target.value as VehicleInput['status'])}>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </Field>
                </div>
            </FormSection>

            <FormSection title="Registration and technical details">
                <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Make">
                        <Input value={value.make ?? ''} onChange={(event) => update('make', event.target.value)} />
                    </Field>
                    <Field label="Model">
                        <Input value={value.model ?? ''} onChange={(event) => update('model', event.target.value)} />
                    </Field>
                    <Field label="Year" error={errors.year?.[0]}>
                        <Input className={fieldClass(Boolean(errors.year))} inputMode="numeric" min={1886} type="number" value={value.year ?? ''} onChange={(event) => update('year', event.target.value ? Number(event.target.value) : undefined)} />
                    </Field>
                    <Field label="Color">
                        <Input value={value.color ?? ''} onChange={(event) => update('color', event.target.value)} />
                    </Field>
                    <Field label="Vehicle type">
                        <Input value={value.vehicleType ?? ''} onChange={(event) => update('vehicleType', event.target.value)} />
                    </Field>
                    <Field label="Fuel type">
                        <Input value={value.fuelType ?? ''} onChange={(event) => update('fuelType', event.target.value)} />
                    </Field>
                    <Field label="Transmission type">
                        <Input value={value.transmissionType ?? ''} onChange={(event) => update('transmissionType', event.target.value)} />
                    </Field>
                    <Field label="Ownership type">
                        <Input value={value.ownershipType ?? ''} onChange={(event) => update('ownershipType', event.target.value)} />
                    </Field>
                </div>
            </FormSection>

            <FormSection title="Status and notes">
                <Field label="Notes">
                    <textarea className="erp-textarea min-h-28" value={value.notes ?? ''} onChange={(event) => update('notes', event.target.value)} />
                </Field>
            </FormSection>

            <div className="flex justify-end gap-3">
                <Button disabled={submitting} onClick={onCancel} variant="secondary">Cancel</Button>
                <Button disabled={submitting} type="submit" variant="blue">{submitting ? 'Saving' : submitLabel}</Button>
            </div>
        </form>
    );
}

function Field({ children, error, label }: { children: ReactNode; error?: string; label: string }) {
    return (
        <label className="space-y-2 text-sm font-semibold text-slate-700">
            <span>{label}</span>
            {children}
            {error ? <span className="block text-xs font-medium text-red-600">{error}</span> : null}
        </label>
    );
}
