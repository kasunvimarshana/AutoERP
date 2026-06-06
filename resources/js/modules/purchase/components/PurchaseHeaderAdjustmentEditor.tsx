import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';

export interface EditableHeaderAdjustment {
    name: string;
    adjustment_type: string;
    effect: 'increase' | 'decrease';
    calculation_type: 'fixed' | 'percentage';
    calculation_base: 'subtotal' | 'subtotal_after_line_discount' | 'subtotal_after_line_adjustments';
    rate: string;
    amount: string;
    allocation_method: string;
    description: string;
}

export function emptyHeaderAdjustment(): EditableHeaderAdjustment {
    return {
        name: '',
        adjustment_type: 'freight',
        effect: 'increase',
        calculation_type: 'fixed',
        calculation_base: 'subtotal',
        rate: '0.000000',
        amount: '0.000000',
        allocation_method: 'proportional',
        description: '',
    };
}

const adjustmentTypes = [
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

const calculationBases = [
    { value: 'subtotal', label: 'subtotal' },
    { value: 'subtotal_after_line_discount', label: 'after line discount' },
    { value: 'subtotal_after_line_adjustments', label: 'after line adjustments' },
];

export function PurchaseHeaderAdjustmentEditor({ adjustments, onChange, errorFor }: {
    adjustments: EditableHeaderAdjustment[];
    onChange: (adjustments: EditableHeaderAdjustment[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const update = (index: number, adjustment: EditableHeaderAdjustment) => {
        onChange(adjustments.map((current, currentIndex) => currentIndex === index ? adjustment : current));
    };

    return (
        <div className="space-y-4">
            {adjustments.map((adjustment, index) => (
                <div key={index} className="rounded-lg border border-slate-200 p-4">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Input label="Name" value={adjustment.name} error={errorFor(`adjustments.${index}.name`)} onChange={(event) => update(index, { ...adjustment, name: event.target.value })} />
                        <Select label="Type" value={adjustment.adjustment_type} options={adjustmentTypes} error={errorFor(`adjustments.${index}.adjustment_type`)} onChange={(event) => update(index, { ...adjustment, adjustment_type: event.target.value })} />
                        <Select label="Effect" value={adjustment.effect} options={[{ value: 'increase', label: 'increase' }, { value: 'decrease', label: 'decrease' }]} error={errorFor(`adjustments.${index}.effect`)} onChange={(event) => update(index, { ...adjustment, effect: event.target.value as 'increase' | 'decrease' })} />
                        <Select label="Calculation" value={adjustment.calculation_type} options={[{ value: 'fixed', label: 'fixed' }, { value: 'percentage', label: 'percentage' }]} error={errorFor(`adjustments.${index}.calculation_type`)} onChange={(event) => update(index, { ...adjustment, calculation_type: event.target.value as 'fixed' | 'percentage' })} />
                    </div>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Select label="Base" value={adjustment.calculation_base} options={calculationBases} error={errorFor(`adjustments.${index}.calculation_base`)} onChange={(event) => update(index, { ...adjustment, calculation_base: event.target.value as EditableHeaderAdjustment['calculation_base'] })} />
                        <Input label="Rate" type="number" min="0" step="0.000001" value={adjustment.rate} error={errorFor(`adjustments.${index}.rate`)} onChange={(event) => update(index, { ...adjustment, rate: event.target.value })} />
                        <Input label="Amount" type="number" min="0" step="0.000001" value={adjustment.amount} error={errorFor(`adjustments.${index}.amount`)} onChange={(event) => update(index, { ...adjustment, amount: event.target.value })} />
                        <Select label="Allocation" value={adjustment.allocation_method} options={[{ value: 'proportional', label: 'proportional' }, { value: 'manual', label: 'manual' }, { value: 'first_invoice', label: 'first invoice' }, { value: 'last_invoice', label: 'last invoice' }]} error={errorFor(`adjustments.${index}.allocation_method`)} onChange={(event) => update(index, { ...adjustment, allocation_method: event.target.value })} />
                        <Input label="Description" value={adjustment.description} onChange={(event) => update(index, { ...adjustment, description: event.target.value })} />
                    </div>
                    <div className="mt-3 flex justify-end">
                        <Button type="button" variant="ghost" onClick={() => onChange(adjustments.filter((_, currentIndex) => currentIndex !== index))}>Remove adjustment</Button>
                    </div>
                </div>
            ))}
            <Button type="button" variant="secondary" onClick={() => onChange([...adjustments, emptyHeaderAdjustment()])}>Add adjustment</Button>
        </div>
    );
}
