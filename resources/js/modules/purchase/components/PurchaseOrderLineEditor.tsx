import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import type { NamedResource } from '@/shared/types/common';
import { ItemLookupSelect, UomLookupSelect } from './PurchaseLookups';

export interface EditablePurchaseLine {
    item: NamedResource | null;
    uom: NamedResource | null;
    description: string;
    ordered_quantity: string;
    unit_price: string;
    discount_amount: string;
    tax_amount: string;
    charge_amount: string;
}

function decimal(value: string): number {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
}

export function previewLineTotal(line: EditablePurchaseLine): string {
    const total = (decimal(line.ordered_quantity) * decimal(line.unit_price))
        - decimal(line.discount_amount)
        + decimal(line.tax_amount)
        + decimal(line.charge_amount);

    return Math.max(total, 0).toFixed(6);
}

export function emptyPurchaseLine(): EditablePurchaseLine {
    return {
        item: null,
        uom: null,
        description: '',
        ordered_quantity: '1.000000',
        unit_price: '0.000000',
        discount_amount: '0.000000',
        tax_amount: '0.000000',
        charge_amount: '0.000000',
    };
}

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
                        <Input label="Discount" type="number" min="0" step="0.000001" value={line.discount_amount} error={errorFor(`lines.${index}.discount_amount`)} onChange={(event) => update(index, { ...line, discount_amount: event.target.value })} />
                        <Input label="Tax" type="number" min="0" step="0.000001" value={line.tax_amount} error={errorFor(`lines.${index}.tax_amount`)} onChange={(event) => update(index, { ...line, tax_amount: event.target.value })} />
                        <Input label="Charge" type="number" min="0" step="0.000001" value={line.charge_amount} error={errorFor(`lines.${index}.charge_amount`)} onChange={(event) => update(index, { ...line, charge_amount: event.target.value })} />
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
