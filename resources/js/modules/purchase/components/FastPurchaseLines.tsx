import { DecimalInput } from '@/shared/components/DecimalInput';
import { Select } from '@/shared/components/Select';
import { Button } from '@/shared/components/Button';
import { subtractDecimal, multiplyDecimal } from '@/shared/utils/decimal';
import type { FastPurchaseContext } from '../purchaseTypes';
import { ItemLookupSelect, UomLookupSelect } from './PurchaseLookups';
import type { NamedResource } from '@/shared/types/common';

export interface FastPurchaseLineRow {
    key: string;
    item: NamedResource | null;
    uom: NamedResource | null;
    description: string;
    quantity: string;
    unit_cost: string;
    discount_amount: string;
    tax_group_id: string;
}

interface FastPurchaseLinesProps {
    rows: FastPurchaseLineRow[];
    context: FastPurchaseContext | null;
    errorFor: (field: string) => string | undefined;
    onChange: (rows: FastPurchaseLineRow[]) => void;
}

export function blankFastPurchaseLine(): FastPurchaseLineRow {
    return {
        key: typeof crypto !== 'undefined' && 'randomUUID' in crypto ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`,
        item: null,
        uom: null,
        description: '',
        quantity: '1.000000',
        unit_cost: '',
        discount_amount: '0.000000',
        tax_group_id: '',
    };
}

export function FastPurchaseLines({ rows, context, errorFor, onChange }: FastPurchaseLinesProps) {
    const update = (index: number, patch: Partial<FastPurchaseLineRow>) => {
        onChange(rows.map((row, rowIndex) => (rowIndex === index ? { ...row, ...patch } : row)));
    };
    const addRow = () => onChange([...rows, blankFastPurchaseLine()]);
    const removeRow = (index: number) => onChange(rows.length === 1 ? [blankFastPurchaseLine()] : rows.filter((_, rowIndex) => rowIndex !== index));

    return (
        <div className="space-y-3">
            <div className="overflow-x-auto rounded-lg border border-slate-200">
                <table className="min-w-[980px] w-full table-fixed border-collapse text-sm">
                    <thead className="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th className="w-64 px-3 py-2">Item</th>
                            <th className="w-40 px-3 py-2">UOM</th>
                            <th className="w-32 px-3 py-2">Qty</th>
                            <th className="w-36 px-3 py-2">Unit cost</th>
                            <th className="w-36 px-3 py-2">Discount</th>
                            <th className="w-44 px-3 py-2">Tax</th>
                            <th className="w-36 px-3 py-2 text-right">Line total</th>
                            <th className="w-24 px-3 py-2" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 bg-white">
                        {rows.map((row, index) => {
                            const estimatedTotal = subtractDecimal(
                                multiplyDecimal(row.quantity || '0.000000', row.unit_cost || '0.000000'),
                                row.discount_amount || '0.000000',
                            );

                            return (
                                <tr key={row.key} className="align-top">
                                    <td className="px-3 py-3">
                                        <ItemLookupSelect
                                            value={row.item}
                                            onChange={(item) => update(index, { item, description: item?.name ?? row.description })}
                                            error={errorFor(`lines.${index}.item_id`)}
                                        />
                                        <input
                                            className="mt-2 min-h-9 w-full rounded-lg border border-slate-300 px-3 py-1.5 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                                            value={row.description}
                                            onChange={(event) => update(index, { description: event.target.value })}
                                            placeholder="Description"
                                        />
                                    </td>
                                    <td className="px-3 py-3">
                                        <UomLookupSelect value={row.uom} onChange={(uom) => update(index, { uom })} error={errorFor(`lines.${index}.uom_id`)} />
                                    </td>
                                    <td className="px-3 py-3">
                                        <DecimalInput
                                            value={row.quantity}
                                            onChange={(event) => update(index, { quantity: event.target.value })}
                                            error={errorFor(`lines.${index}.quantity`)}
                                            onKeyDown={(event) => {
                                                if (event.key === 'Enter' && index === rows.length - 1) {
                                                    event.preventDefault();
                                                    addRow();
                                                }
                                            }}
                                        />
                                    </td>
                                    <td className="px-3 py-3">
                                        <DecimalInput value={row.unit_cost} onChange={(event) => update(index, { unit_cost: event.target.value })} error={errorFor(`lines.${index}.unit_cost`)} />
                                    </td>
                                    <td className="px-3 py-3">
                                        <DecimalInput value={row.discount_amount} onChange={(event) => update(index, { discount_amount: event.target.value })} error={errorFor(`lines.${index}.discount_amount`)} />
                                    </td>
                                    <td className="px-3 py-3">
                                        <Select
                                            value={row.tax_group_id}
                                            onChange={(event) => update(index, { tax_group_id: event.target.value })}
                                            options={(context?.tax_groups ?? []).map((group) => ({ value: group.id, label: `${group.code ?? ''} ${group.name ?? ''}`.trim() }))}
                                            placeholder="Default"
                                            error={errorFor(`lines.${index}.tax_group_id`)}
                                        />
                                    </td>
                                    <td className="px-3 py-3 text-right font-medium text-slate-800">{estimatedTotal}</td>
                                    <td className="px-3 py-3 text-right">
                                        <Button type="button" variant="ghost" className="min-h-9 px-3" onClick={() => removeRow(index)}>Remove</Button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
            <Button type="button" variant="secondary" onClick={addRow}>Add line</Button>
        </div>
    );
}
