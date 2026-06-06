import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { NamedResource } from '@/shared/types/common';
import { ItemLookupSelect, UomLookupSelect } from './PurchaseLookups';

export interface EditablePurchaseLine {
    item: NamedResource | null;
    uom: NamedResource | null;
    description: string;
    ordered_quantity: string;
    unit_price: string;
    discount_calculation_type: 'fixed' | 'percentage';
    discount_rate: string;
    discount_amount: string;
    tax_calculation_type: 'fixed' | 'percentage';
    tax_rate: string;
    tax_amount: string;
    charge_calculation_type: 'fixed' | 'percentage';
    charge_rate: string;
    charge_amount: string;
}

function decimal(value: string): number {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
}

export function previewLineTotal(line: EditablePurchaseLine): string {
    const subtotal = decimal(line.ordered_quantity) * decimal(line.unit_price);
    const discount = line.discount_calculation_type === 'percentage' ? subtotal * decimal(line.discount_rate) / 100 : decimal(line.discount_amount);
    const taxBase = Math.max(subtotal - discount, 0);
    const tax = line.tax_calculation_type === 'percentage' ? taxBase * decimal(line.tax_rate) / 100 : decimal(line.tax_amount);
    const charge = line.charge_calculation_type === 'percentage' ? subtotal * decimal(line.charge_rate) / 100 : decimal(line.charge_amount);
    const total = subtotal - discount + tax + charge;

    return Math.max(total, 0).toFixed(6);
}

export function emptyPurchaseLine(): EditablePurchaseLine {
    return {
        item: null,
        uom: null,
        description: '',
        ordered_quantity: '1.000000',
        unit_price: '0.000000',
        discount_calculation_type: 'fixed',
        discount_rate: '0.000000',
        discount_amount: '0.000000',
        tax_calculation_type: 'fixed',
        tax_rate: '0.000000',
        tax_amount: '0.000000',
        charge_calculation_type: 'fixed',
        charge_rate: '0.000000',
        charge_amount: '0.000000',
    };
}

const calculationOptions = [{ value: 'fixed', label: 'fixed' }, { value: 'percentage', label: 'percentage' }];

export function PurchaseOrderLineEditor({ lines, onChange, errorFor }: {
    lines: EditablePurchaseLine[];
    onChange: (lines: EditablePurchaseLine[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const update = (index: number, line: EditablePurchaseLine) => {
        onChange(lines.map((current, currentIndex) => currentIndex === index ? line : current));
    };

    return (
        <div className="space-y-4">
            {lines.map((line, index) => (
                <div key={index} className="rounded-lg border border-slate-200 p-4">
                    <div className="grid gap-4 lg:grid-cols-12">
                        <div className="lg:col-span-3">
                            <ItemLookupSelect value={line.item} onChange={(item) => update(index, { ...line, item })} error={errorFor(`lines.${index}.item_id`)} />
                        </div>
                        <div className="lg:col-span-2">
                            <UomLookupSelect value={line.uom} onChange={(uom) => update(index, { ...line, uom })} error={errorFor(`lines.${index}.uom_id`)} />
                        </div>
                        <Input className="lg:col-span-2" label="Quantity" type="number" min="0.000001" step="0.000001" value={line.ordered_quantity} error={errorFor(`lines.${index}.ordered_quantity`)} onChange={(event) => update(index, { ...line, ordered_quantity: event.target.value })} />
                        <Input className="lg:col-span-2" label="Unit price" type="number" min="0" step="0.000001" value={line.unit_price} error={errorFor(`lines.${index}.unit_price`)} onChange={(event) => update(index, { ...line, unit_price: event.target.value })} />
                        <div className="lg:col-span-3">
                            <p className="mb-1.5 text-sm font-medium text-slate-700">Line total</p>
                            <div className="min-h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm tabular-nums text-slate-900">{previewLineTotal(line)}</div>
                        </div>
                    </div>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Select label="Discount type" value={line.discount_calculation_type} options={calculationOptions} error={errorFor(`lines.${index}.discount_calculation_type`)} onChange={(event) => update(index, { ...line, discount_calculation_type: event.target.value as 'fixed' | 'percentage' })} />
                        <Input label={line.discount_calculation_type === 'percentage' ? 'Discount rate' : 'Discount amount'} type="number" min="0" step="0.000001" value={line.discount_calculation_type === 'percentage' ? line.discount_rate : line.discount_amount} error={errorFor(`lines.${index}.${line.discount_calculation_type === 'percentage' ? 'discount_rate' : 'discount_amount'}`)} onChange={(event) => update(index, line.discount_calculation_type === 'percentage' ? { ...line, discount_rate: event.target.value } : { ...line, discount_amount: event.target.value })} />
                        <Select label="Tax type" value={line.tax_calculation_type} options={calculationOptions} error={errorFor(`lines.${index}.tax_calculation_type`)} onChange={(event) => update(index, { ...line, tax_calculation_type: event.target.value as 'fixed' | 'percentage' })} />
                        <Input label={line.tax_calculation_type === 'percentage' ? 'Tax rate' : 'Tax amount'} type="number" min="0" step="0.000001" value={line.tax_calculation_type === 'percentage' ? line.tax_rate : line.tax_amount} error={errorFor(`lines.${index}.${line.tax_calculation_type === 'percentage' ? 'tax_rate' : 'tax_amount'}`)} onChange={(event) => update(index, line.tax_calculation_type === 'percentage' ? { ...line, tax_rate: event.target.value } : { ...line, tax_amount: event.target.value })} />
                        <Select label="Charge type" value={line.charge_calculation_type} options={calculationOptions} error={errorFor(`lines.${index}.charge_calculation_type`)} onChange={(event) => update(index, { ...line, charge_calculation_type: event.target.value as 'fixed' | 'percentage' })} />
                        <Input label={line.charge_calculation_type === 'percentage' ? 'Charge rate' : 'Charge amount'} type="number" min="0" step="0.000001" value={line.charge_calculation_type === 'percentage' ? line.charge_rate : line.charge_amount} error={errorFor(`lines.${index}.${line.charge_calculation_type === 'percentage' ? 'charge_rate' : 'charge_amount'}`)} onChange={(event) => update(index, line.charge_calculation_type === 'percentage' ? { ...line, charge_rate: event.target.value } : { ...line, charge_amount: event.target.value })} />
                        <Input label="Description" value={line.description} onChange={(event) => update(index, { ...line, description: event.target.value })} />
                    </div>
                    <div className="mt-3 flex justify-end">
                        <Button type="button" variant="ghost" disabled={lines.length === 1} onClick={() => onChange(lines.filter((_, currentIndex) => currentIndex !== index))}>Remove line</Button>
                    </div>
                </div>
            ))}
            <Button type="button" variant="secondary" onClick={() => onChange([...lines, emptyPurchaseLine()])}>Add line</Button>
        </div>
    );
}
