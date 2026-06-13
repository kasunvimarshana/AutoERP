import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { isNonNegativeDecimal, isPositiveDecimal } from '@/shared/utils/decimal';
import { ItemLookupSelect, UomLookupSelect } from './PurchaseLookups';
import {
    previewLineAmounts,
    type EditablePurchaseLine,
} from './purchaseOrderLineModel';

type LineFormErrors = Partial<Record<'item' | 'uom' | 'ordered_quantity' | 'unit_price', string>>;

const calculationOptions = [
    { value: 'fixed', label: 'Fixed' },
    { value: 'percentage', label: 'Percentage' },
];

export function PurchaseOrderLineForm({ line, mode, errorFor, onSave, onCancel }: {
    line: EditablePurchaseLine;
    mode: 'create' | 'edit';
    errorFor: (field: string) => string | undefined;
    onSave: (line: EditablePurchaseLine) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(line);
    const [errors, setErrors] = useState<LineFormErrors>({});
    const set = <K extends keyof EditablePurchaseLine>(key: K, value: EditablePurchaseLine[K]) => {
        setDraft((current) => ({ ...current, [key]: value }));
        setErrors((current) => ({ ...current, [key]: undefined }));
    };
    const formError = (field: keyof LineFormErrors) => errors[field] ?? errorFor(field);
    const preview = previewLineAmounts(draft);

    return (
        <form className="space-y-5" onSubmit={(event) => {
            event.preventDefault();
            const nextErrors = validateLineForm(draft);
            setErrors(nextErrors);
            if (Object.keys(nextErrors).length > 0) return;
            onSave(draft);
        }}>
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Basic Details</h3>
                    <p className="text-sm text-slate-500">Item, quantity, UOM, and price are enough for most lines.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <ItemLookupSelect value={draft.item} onChange={(item) => set('item', item)} error={formError('item') ?? errorFor('item_id')} />
                    <DecimalInput label="Quantity" value={draft.ordered_quantity} error={formError('ordered_quantity')} onChange={(event) => set('ordered_quantity', event.target.value)} />
                    <UomLookupSelect value={draft.uom} onChange={(uom) => set('uom', uom)} error={formError('uom') ?? errorFor('uom_id')} />
                    <DecimalInput label="Unit price" value={draft.unit_price} error={formError('unit_price')} onChange={(event) => set('unit_price', event.target.value)} />
                    <Input className="sm:col-span-2" label="Description" value={draft.description} onChange={(event) => set('description', event.target.value)} />
                </div>
            </section>

            <details className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <summary className="cursor-pointer font-semibold text-slate-800">Advanced pricing</summary>
                <p className="mt-1 text-sm text-slate-500">Advanced pricing is optional.</p>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <Select label="Discount type" value={draft.discount_calculation_type} options={calculationOptions} error={errorFor('discount_calculation_type')} onChange={(event) => set('discount_calculation_type', event.target.value as 'fixed' | 'percentage')} />
                    <DecimalInput label={draft.discount_calculation_type === 'percentage' ? 'Discount value (%)' : 'Discount value'} value={draft.discount_calculation_type === 'percentage' ? draft.discount_rate : draft.discount_amount} error={errorFor(draft.discount_calculation_type === 'percentage' ? 'discount_rate' : 'discount_amount')} onChange={(event) => set(draft.discount_calculation_type === 'percentage' ? 'discount_rate' : 'discount_amount', event.target.value)} />
                    <Select label="Tax type" value={draft.tax_calculation_type} options={calculationOptions} error={errorFor('tax_calculation_type')} onChange={(event) => set('tax_calculation_type', event.target.value as 'fixed' | 'percentage')} />
                    <DecimalInput label={draft.tax_calculation_type === 'percentage' ? 'Tax value (%)' : 'Tax value'} value={draft.tax_calculation_type === 'percentage' ? draft.tax_rate : draft.tax_amount} error={errorFor(draft.tax_calculation_type === 'percentage' ? 'tax_rate' : 'tax_amount')} onChange={(event) => set(draft.tax_calculation_type === 'percentage' ? 'tax_rate' : 'tax_amount', event.target.value)} />
                    <Select label="Charge type" value={draft.charge_calculation_type} options={calculationOptions} error={errorFor('charge_calculation_type')} onChange={(event) => set('charge_calculation_type', event.target.value as 'fixed' | 'percentage')} />
                    <DecimalInput label={draft.charge_calculation_type === 'percentage' ? 'Charge value (%)' : 'Charge value'} value={draft.charge_calculation_type === 'percentage' ? draft.charge_rate : draft.charge_amount} error={errorFor(draft.charge_calculation_type === 'percentage' ? 'charge_rate' : 'charge_amount')} onChange={(event) => set(draft.charge_calculation_type === 'percentage' ? 'charge_rate' : 'charge_amount', event.target.value)} />
                </div>
            </details>

            <div className="rounded-lg border border-slate-200 p-4 text-sm">
                <h3 className="font-semibold text-slate-900">Line Preview</h3>
                <div className="mt-3 grid gap-2 sm:grid-cols-5">
                    <PreviewValue label="Subtotal" value={preview.subtotal} />
                    <PreviewValue label="Discount" value={preview.discount} />
                    <PreviewValue label="Tax" value={preview.tax} />
                    <PreviewValue label="Charge" value={preview.charge} />
                    <PreviewValue label="Total" value={preview.total} />
                </div>
            </div>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit">{mode === 'edit' ? 'Save line' : 'Add line'}</Button>
            </div>
        </form>
    );
}

function validateLineForm(line: EditablePurchaseLine): LineFormErrors {
    const errors: LineFormErrors = {};
    if (!line.item) errors.item = 'Select an item.';
    if (!line.uom) errors.uom = 'Select a UOM.';
    if (!isPositiveDecimal(line.ordered_quantity)) errors.ordered_quantity = 'Quantity must be greater than zero.';
    if (!isNonNegativeDecimal(line.unit_price)) errors.unit_price = 'Unit price cannot be negative.';
    return errors;
}

function PreviewValue({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block tabular-nums text-slate-900">{value}</strong></div>;
}
