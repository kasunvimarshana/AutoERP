import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { isNonNegativeDecimal } from '@/shared/utils/decimal';
import {
    formatAdjustmentSummary,
    type EditableHeaderAdjustment,
} from './purchaseHeaderAdjustmentModel';
import type { PurchaseAdjustmentCatalogueEntry } from '../purchaseTypes';

type AdjustmentFormErrors = Partial<Record<'name' | 'amount' | 'rate', string>>;

const fallbackAdjustmentTypes = [
    'discount',
    'tax',
    'freight',
    'charge',
    'credit_note',
    'debit_note',
    'withholding',
    'rounding',
    'other',
].map((value) => ({ value, label: value.replaceAll('_', ' ') }));

const calculationOptions = [
    { value: 'fixed', label: 'Fixed' },
    { value: 'percentage', label: 'Percentage' },
];
const effectOptions = [
    { value: 'increase', label: 'Increase' },
    { value: 'decrease', label: 'Decrease' },
];
const allocationOptions = [
    { value: 'proportional', label: 'Proportional' },
    { value: 'manual', label: 'Manual' },
    { value: 'first_invoice', label: 'First invoice' },
    { value: 'last_invoice', label: 'Last invoice' },
];
const calculationBases = [
    { value: 'subtotal', label: 'Subtotal' },
    { value: 'subtotal_after_line_discount', label: 'After line discount' },
    { value: 'subtotal_after_line_adjustments', label: 'After line adjustments' },
];

export function PurchaseHeaderAdjustmentForm({ adjustment, mode, catalogue, errorFor, onSave, onCancel }: {
    adjustment: EditableHeaderAdjustment;
    mode: 'create' | 'edit';
    catalogue: PurchaseAdjustmentCatalogueEntry[];
    errorFor: (field: string) => string | undefined;
    onSave: (adjustment: EditableHeaderAdjustment) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(adjustment);
    const [errors, setErrors] = useState<AdjustmentFormErrors>({});
    const set = <K extends keyof EditableHeaderAdjustment>(key: K, value: EditableHeaderAdjustment[K]) => {
        setDraft((current) => ({ ...current, [key]: value }));
        setErrors((current) => ({ ...current, [key]: undefined }));
    };
    const formError = (field: keyof AdjustmentFormErrors) => errors[field] ?? errorFor(field);
    const selectedCatalogue = catalogue.find((entry) => entry.type === draft.adjustment_type);
    const adjustmentTypes = catalogue.length
        ? catalogue.map((entry) => ({ value: entry.type, label: entry.default_name }))
        : fallbackAdjustmentTypes;
    const effectChoices = selectedCatalogue?.allowed_effects.length
        ? selectedCatalogue.allowed_effects.map((value) => ({ value, label: value === 'increase' ? 'Increase' : 'Decrease' }))
        : effectOptions;
    const effectReadonly = effectChoices.length === 1;

    const applyCatalogue = (type: string) => {
        const entry = catalogue.find((row) => row.type === type);
        if (!entry) {
            set('adjustment_type', type);
            return;
        }

        setDraft((current) => ({
            ...current,
            adjustment_type: type,
            name: current.name.trim() === '' || current.name === selectedCatalogue?.default_name ? entry.default_name : current.name,
            effect: entry.default_effect,
            calculation_type: entry.default_calculation_type,
            calculation_base: entry.default_calculation_base as EditableHeaderAdjustment['calculation_base'],
            finance_mapping_label: entry.finance_mapping_label,
            cost_treatment: entry.cost_treatment,
            tax_treatment: entry.tax_treatment,
            mapping_source: 'catalogue',
        }));
    };

    return (
        <form className="space-y-5" onSubmit={(event) => {
            event.preventDefault();
            const nextErrors = validateAdjustmentForm(draft);
            setErrors(nextErrors);
            if (Object.keys(nextErrors).length > 0) return;
            onSave(draft);
        }}>
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Basic Details</h3>
                    <p className="text-sm text-slate-500">Define the adjustment name, type, effect, and calculation.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Input label="Name" value={draft.name} error={formError('name')} onChange={(event) => set('name', event.target.value)} />
                    <Select label="Type" value={draft.adjustment_type} options={adjustmentTypes} error={errorFor('adjustment_type')} onChange={(event) => applyCatalogue(event.target.value)} />
                    <Select label="Effect" value={draft.effect} options={effectChoices} disabled={effectReadonly} error={errorFor('effect')} onChange={(event) => set('effect', event.target.value as 'increase' | 'decrease')} />
                    <Select label="Calculation" value={draft.calculation_type} options={calculationOptions} error={errorFor('calculation_type')} onChange={(event) => set('calculation_type', event.target.value as 'fixed' | 'percentage')} />
                    {draft.calculation_type === 'percentage' && <Select label="Base" value={draft.calculation_base} options={calculationBases} error={errorFor('calculation_base')} onChange={(event) => set('calculation_base', event.target.value as EditableHeaderAdjustment['calculation_base'])} />}
                    {draft.calculation_type === 'percentage'
                        ? <DecimalInput label="Rate (%)" value={draft.rate} error={formError('rate')} onChange={(event) => set('rate', event.target.value)} />
                        : <DecimalInput label="Amount" value={draft.amount} error={formError('amount')} onChange={(event) => set('amount', event.target.value)} />}
                    <Select label="Allocation" value={draft.allocation_method} options={allocationOptions} error={errorFor('allocation_method')} onChange={(event) => set('allocation_method', event.target.value)} />
                </div>
            </section>

            <details className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <summary className="cursor-pointer font-semibold text-slate-800">Advanced</summary>
                <div className="mt-4">
                    <Input label="Description" value={draft.description} onChange={(event) => set('description', event.target.value)} />
                </div>
            </details>

            <div className="rounded-lg border border-slate-200 p-4 text-sm">
                <h3 className="font-semibold text-slate-900">Adjustment Preview</h3>
                <div className="mt-2 text-slate-700">{formatAdjustmentSummary(draft)}</div>
                {draft.finance_mapping_label && <div className="mt-2 text-slate-600">Finance mapping: {draft.finance_mapping_label}</div>}
            </div>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit">{mode === 'edit' ? 'Save adjustment' : 'Add adjustment'}</Button>
            </div>
        </form>
    );
}

function validateAdjustmentForm(adjustment: EditableHeaderAdjustment): AdjustmentFormErrors {
    const errors: AdjustmentFormErrors = {};
    if (!adjustment.name.trim()) errors.name = 'Enter an adjustment name.';
    if (adjustment.calculation_type === 'percentage' && !isNonNegativeDecimal(adjustment.rate)) errors.rate = 'Rate cannot be negative.';
    if (adjustment.calculation_type === 'fixed' && !isNonNegativeDecimal(adjustment.amount)) errors.amount = 'Amount cannot be negative.';
    return errors;
}
