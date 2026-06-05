import { FormEvent, useState, type ReactNode } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import type { UomInput } from '../types/uom.types';

const emptyInput: UomInput = {
    decimalPrecision: 2,
    isBase: false,
    name: '',
    status: 'active',
    uomCode: '',
};

export function UomForm({
    initialValue = emptyInput,
    onCancel,
    onSubmit,
    submitLabel,
}: {
    initialValue?: UomInput;
    onCancel: () => void;
    onSubmit: (input: UomInput) => Promise<void>;
    submitLabel: string;
}) {
    const [value, setValue] = useState<UomInput>(initialValue);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [submitting, setSubmitting] = useState(false);

    function update<K extends keyof UomInput>(key: K, nextValue: UomInput[K]) {
        setValue((current) => ({ ...current, [key]: nextValue }));
    }

    function validate() {
        const nextErrors: Record<string, string[]> = {};
        if (!value.uomCode.trim()) nextErrors.uom_code = ['UOM code is required.'];
        if (!value.name.trim()) nextErrors.name = ['Name is required.'];
        if (!Number.isInteger(value.decimalPrecision) || value.decimalPrecision < 0 || value.decimalPrecision > 8) {
            nextErrors.decimal_precision = ['Decimal precision must be a whole number from 0 to 8.'];
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
                setFormError('Unable to save this unit of measure.');
            }
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <form className="space-y-6" onSubmit={handleSubmit}>
            {formError ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}
            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 className="text-base font-bold text-slate-950">Unit details</h2>
                <div className="mt-4 grid gap-4 md:grid-cols-2">
                    <Field error={errors.uom_code?.[0]} label="UOM code">
                        <Input value={value.uomCode} onChange={(event) => update('uomCode', event.target.value)} placeholder="e.g. PCS" />
                    </Field>
                    <Field error={errors.name?.[0]} label="Name">
                        <Input value={value.name} onChange={(event) => update('name', event.target.value)} placeholder="e.g. Each" />
                    </Field>
                    <Field label="Symbol">
                        <Input value={value.symbol ?? ''} onChange={(event) => update('symbol', event.target.value)} placeholder="e.g. pcs" />
                    </Field>
                    <Field error={errors.decimal_precision?.[0]} label="Decimal precision">
                        <Input min={0} max={8} type="number" value={value.decimalPrecision} onChange={(event) => update('decimalPrecision', Number(event.target.value))} />
                    </Field>
                    <Field label="Status">
                        <select className="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={value.status} onChange={(event) => update('status', event.target.value as UomInput['status'])}>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </Field>
                    <label className="flex h-11 items-center gap-3 self-end rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-700">
                        <input checked={value.isBase} onChange={(event) => update('isBase', event.target.checked)} type="checkbox" />
                        Base unit
                    </label>
                </div>
            </section>
            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <Field label="Notes">
                    <textarea className="min-h-28 w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm outline-none focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-100" value={value.notes ?? ''} onChange={(event) => update('notes', event.target.value)} />
                </Field>
            </section>
            <div className="flex justify-end gap-3">
                <Button disabled={submitting} onClick={onCancel} variant="secondary">Cancel</Button>
                <Button disabled={submitting} type="submit" variant="blue">{submitting ? 'Saving' : submitLabel}</Button>
            </div>
        </form>
    );
}

function Field({ children, error, label }: { children: ReactNode; error?: string; label: string }) {
    return <label className="space-y-2 text-sm font-semibold text-slate-700"><span>{label}</span>{children}{error ? <span className="block text-xs font-medium text-red-600">{error}</span> : null}</label>;
}
