import { FormEvent, useState, type ReactNode } from 'react';
import { ApiError } from '../../services/api/apiErrors';
import { FormSection } from '../components/erp/ErpUi';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import type { PartyInput } from './party.types';

const emptyInput: PartyInput = {
    address: null,
    code: '',
    creditLimit: '0',
    name: '',
    paymentTermsDays: 0,
    status: 'active',
};

type PartyFormProps = {
    codeField: 'customer_code' | 'supplier_code';
    initialValue?: PartyInput;
    noun: string;
    onCancel: () => void;
    onSubmit: (input: PartyInput) => Promise<void>;
    submitLabel: string;
};

function fieldClass(hasError: boolean) {
    return hasError ? 'border-red-300 focus:border-red-400 focus:ring-red-100' : '';
}

export function PartyForm({
    codeField,
    initialValue = emptyInput,
    noun,
    onCancel,
    onSubmit,
    submitLabel,
}: PartyFormProps) {
    const [value, setValue] = useState<PartyInput>(initialValue);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [submitting, setSubmitting] = useState(false);

    function update<K extends keyof PartyInput>(key: K, nextValue: PartyInput[K]) {
        setValue((current) => ({ ...current, [key]: nextValue }));
    }

    function updateAddress(key: string, nextValue: string) {
        setValue((current) => ({
            ...current,
            address: {
                addressLine1: '',
                city: '',
                postalCode: '',
                ...current.address,
                [key]: nextValue,
            },
        }));
    }

    function validate() {
        const nextErrors: Record<string, string[]> = {};

        if (!value.code.trim()) nextErrors[codeField] = [`${noun} code is required.`];
        if (!value.name.trim()) nextErrors.name = [`${noun} name is required.`];
        if (value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.email)) nextErrors.email = ['Enter a valid email address.'];
        if (!Number.isFinite(Number(value.creditLimit)) || Number(value.creditLimit) < 0) nextErrors.credit_limit = ['Credit limit must be zero or greater.'];
        if (!Number.isInteger(Number(value.paymentTermsDays)) || Number(value.paymentTermsDays) < 0) {
            nextErrors.payment_terms_days = ['Payment terms must be a non-negative whole number.'];
        }

        const hasAddress = Boolean(
            value.address &&
                Object.values(value.address).some((entry) => typeof entry === 'string' && entry.trim() !== ''),
        );

        if (hasAddress && value.address) {
            if (!value.address.addressLine1.trim()) nextErrors['address.address_line_1'] = ['Address line 1 is required.'];
            if (!value.address.city.trim()) nextErrors['address.city'] = ['City is required.'];
            if (!value.address.postalCode.trim()) nextErrors['address.postal_code'] = ['Postal code is required.'];
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
            const hasAddress = Boolean(
                value.address &&
                    Object.values(value.address).some((entry) => typeof entry === 'string' && entry.trim() !== ''),
            );
            await onSubmit({ ...value, address: hasAddress ? value.address : null });
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError(`Unable to save this ${noun.toLowerCase()}.`);
            }
        } finally {
            setSubmitting(false);
        }
    }

    const address = value.address ?? { addressLine1: '', city: '', postalCode: '' };

    return (
        <form className="space-y-6" onSubmit={handleSubmit}>
            {formError ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

            <FormSection title="Basic information">
                <div className="grid gap-4 md:grid-cols-2">
                    <Field label={`${noun} code`} error={errors[codeField]?.[0]}>
                        <Input className={fieldClass(Boolean(errors[codeField]))} value={value.code} onChange={(event) => update('code', event.target.value)} />
                    </Field>
                    <Field label="Name" error={errors.name?.[0]}>
                        <Input className={fieldClass(Boolean(errors.name))} value={value.name} onChange={(event) => update('name', event.target.value)} />
                    </Field>
                    <Field label="Display name">
                        <Input value={value.displayName ?? ''} onChange={(event) => update('displayName', event.target.value)} />
                    </Field>
                    <Field label="Status">
                        <select className="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={value.status} onChange={(event) => update('status', event.target.value as PartyInput['status'])}>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </Field>
                    <Field label="Email" error={errors.email?.[0]}>
                        <Input className={fieldClass(Boolean(errors.email))} type="email" value={value.email ?? ''} onChange={(event) => update('email', event.target.value)} />
                    </Field>
                    <Field label="Phone">
                        <Input value={value.phone ?? ''} onChange={(event) => update('phone', event.target.value)} />
                    </Field>
                    <Field label="Mobile">
                        <Input value={value.mobile ?? ''} onChange={(event) => update('mobile', event.target.value)} />
                    </Field>
                    <Field label="Organization unit ID">
                        <Input inputMode="numeric" value={value.organizationUnitId ?? ''} onChange={(event) => update('organizationUnitId', event.target.value ? Number(event.target.value) : undefined)} />
                    </Field>
                </div>
            </FormSection>

            <FormSection description="Amounts are stored without a hardcoded currency. Live balance integration remains separate." title="Credit and payment terms">
                <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Credit limit" error={errors.credit_limit?.[0]}>
                        <Input className={fieldClass(Boolean(errors.credit_limit))} inputMode="decimal" value={value.creditLimit} onChange={(event) => update('creditLimit', event.target.value)} />
                    </Field>
                    <Field label="Payment terms (days)" error={errors.payment_terms_days?.[0]}>
                        <Input className={fieldClass(Boolean(errors.payment_terms_days))} inputMode="numeric" min={0} type="number" value={value.paymentTermsDays} onChange={(event) => update('paymentTermsDays', Number(event.target.value))} />
                    </Field>
                    <Field label="Tax number">
                        <Input value={value.taxNumber ?? ''} onChange={(event) => update('taxNumber', event.target.value)} />
                    </Field>
                    <Field label="VAT number">
                        <Input value={value.vatNumber ?? ''} onChange={(event) => update('vatNumber', event.target.value)} />
                    </Field>
                </div>
            </FormSection>

            <FormSection description="Leave all address fields blank when no primary address is required." title="Contact address">
                <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Label">
                        <Input value={address.label ?? ''} onChange={(event) => updateAddress('label', event.target.value)} />
                    </Field>
                    <Field label="Address line 1" error={errors['address.address_line_1']?.[0]}>
                        <Input className={fieldClass(Boolean(errors['address.address_line_1']))} value={address.addressLine1} onChange={(event) => updateAddress('addressLine1', event.target.value)} />
                    </Field>
                    <Field label="Address line 2">
                        <Input value={address.addressLine2 ?? ''} onChange={(event) => updateAddress('addressLine2', event.target.value)} />
                    </Field>
                    <Field label="City" error={errors['address.city']?.[0]}>
                        <Input className={fieldClass(Boolean(errors['address.city']))} value={address.city} onChange={(event) => updateAddress('city', event.target.value)} />
                    </Field>
                    <Field label="State / province">
                        <Input value={address.stateProvince ?? ''} onChange={(event) => updateAddress('stateProvince', event.target.value)} />
                    </Field>
                    <Field label="Postal code" error={errors['address.postal_code']?.[0]}>
                        <Input className={fieldClass(Boolean(errors['address.postal_code']))} value={address.postalCode} onChange={(event) => updateAddress('postalCode', event.target.value)} />
                    </Field>
                    <Field label="Country">
                        <Input value={address.countryName ?? ''} onChange={(event) => updateAddress('countryName', event.target.value)} />
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
