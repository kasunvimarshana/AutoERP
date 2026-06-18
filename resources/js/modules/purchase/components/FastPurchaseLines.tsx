import { useEffect, useRef, useState } from 'react';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Select } from '@/shared/components/Select';
import { Button } from '@/shared/components/Button';
import { subtractDecimal, multiplyDecimal } from '@/shared/utils/decimal';
import type { FastPurchaseContext, PurchaseItemContext } from '../purchaseTypes';
import { getPurchaseItemContext } from '../purchaseApi';
import { ItemLookupSelect } from './PurchaseLookups';
import type { NamedResource } from '@/shared/types/common';

export interface FastPurchaseLineRow {
    key: string;
    item: NamedResource | null;
    item_variant: NamedResource | null;
    uom: NamedResource | null;
    description: string;
    quantity: string;
    unit_cost: string;
    discount_amount: string;
    tax_group_id: string;
    auto_uom: boolean;
    auto_price: boolean;
    price_source_label?: string;
}

interface FastPurchaseLinesProps {
    rows: FastPurchaseLineRow[];
    context: FastPurchaseContext | null;
    supplierId?: number;
    currencyId?: number;
    warehouseId?: number;
    errorFor: (field: string) => string | undefined;
    onChange: (rows: FastPurchaseLineRow[]) => void;
}

export function blankFastPurchaseLine(): FastPurchaseLineRow {
    return {
        key: typeof crypto !== 'undefined' && 'randomUUID' in crypto ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`,
        item: null,
        item_variant: null,
        uom: null,
        description: '',
        quantity: '1.000000',
        unit_cost: '',
        discount_amount: '0.000000',
        tax_group_id: '',
        auto_uom: true,
        auto_price: true,
    };
}

export function FastPurchaseLines({ rows, context, supplierId, currencyId, warehouseId, errorFor, onChange }: FastPurchaseLinesProps) {
    const rowsRef = useRef(rows);
    useEffect(() => {
        rowsRef.current = rows;
    }, [rows]);

    const update = (index: number, patch: Partial<FastPurchaseLineRow>) => {
        onChange(rowsRef.current.map((row, rowIndex) => (rowIndex === index ? { ...row, ...patch } : row)));
    };
    const updateWith = (index: number, mapper: (row: FastPurchaseLineRow) => FastPurchaseLineRow) => {
        onChange(rowsRef.current.map((row, rowIndex) => (rowIndex === index ? mapper(row) : row)));
    };
    const addRow = () => onChange([...rowsRef.current, blankFastPurchaseLine()]);
    const removeRow = (index: number) => onChange(rowsRef.current.length === 1 ? [blankFastPurchaseLine()] : rowsRef.current.filter((_, rowIndex) => rowIndex !== index));

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
                                <FastPurchaseLine
                                    key={row.key}
                                    row={row}
                                    index={index}
                                    context={context}
                                    supplierId={supplierId}
                                    currencyId={currencyId}
                                    warehouseId={warehouseId}
                                    estimatedTotal={estimatedTotal}
                                    isLast={index === rows.length - 1}
                                    errorFor={errorFor}
                                    addRow={addRow}
                                    removeRow={removeRow}
                                    update={update}
                                    updateWith={updateWith}
                                />
                            );
                        })}
                    </tbody>
                </table>
            </div>
            <Button type="button" variant="secondary" onClick={addRow}>Add line</Button>
        </div>
    );
}

function FastPurchaseLine({ row, index, context, supplierId, currencyId, warehouseId, estimatedTotal, isLast, errorFor, addRow, removeRow, update, updateWith }: {
    row: FastPurchaseLineRow;
    index: number;
    context: FastPurchaseContext | null;
    supplierId?: number;
    currencyId?: number;
    warehouseId?: number;
    estimatedTotal: string;
    isLast: boolean;
    errorFor: (field: string) => string | undefined;
    addRow: () => void;
    removeRow: (index: number) => void;
    update: (index: number, patch: Partial<FastPurchaseLineRow>) => void;
    updateWith: (index: number, mapper: (row: FastPurchaseLineRow) => FastPurchaseLineRow) => void;
}) {
    const [purchaseContext, setPurchaseContext] = useState<PurchaseItemContext | null>(null);
    const [loadingContext, setLoadingContext] = useState(false);

    useEffect(() => {
        if (!row.item?.id) {
            setPurchaseContext(null);
            return;
        }

        const controller = new AbortController();
        setLoadingContext(true);
        void getPurchaseItemContext(row.item.id, {
            supplier_id: supplierId,
            item_variant_id: row.item_variant?.id,
            currency_id: currencyId,
            warehouse_id: warehouseId,
        }, controller.signal)
            .then((next) => {
                setPurchaseContext(next);
                const defaultUom = next.allowed_purchase_uoms.find((unit) => unit.id === next.default_purchase_uom_id)?.uom ?? null;
                updateWith(index, (current) => {
                    if (current.key !== row.key || current.item?.id !== row.item?.id) {
                        return current;
                    }

                    return {
                        ...current,
                        description: current.description || next.description || current.item?.name || '',
                        uom: current.auto_uom ? defaultUom : current.uom,
                        unit_cost: current.auto_price ? next.unit_price ?? current.unit_cost : current.unit_cost,
                        tax_group_id: !current.tax_group_id && typeof next.tax_defaults?.tax_group_id === 'number' ? String(next.tax_defaults.tax_group_id) : current.tax_group_id,
                        price_source_label: next.price_source_label,
                    };
                });
            })
            .catch(() => undefined)
            .finally(() => setLoadingContext(false));

        return () => controller.abort();
    }, [row.item?.id, row.item_variant?.id, supplierId, currencyId, warehouseId]);

    return (
        <tr className="align-top">
            <td className="px-3 py-3">
                <ItemLookupSelect
                    value={row.item}
                    onChange={(item) => update(index, { item, item_variant: null, description: item?.name ?? '', uom: null, unit_cost: '', auto_uom: true, auto_price: true })}
                    error={errorFor(`lines.${index}.item_id`)}
                />
                <input
                    className="mt-2 min-h-9 w-full rounded-lg border border-slate-300 px-3 py-1.5 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                    value={row.description}
                    onChange={(event) => update(index, { description: event.target.value })}
                    placeholder="Description"
                />
            </td>
            <td className="px-3 py-3 space-y-2">
                <Select
                    value={row.item_variant?.id ?? ''}
                    onChange={(event) => {
                        const variant = purchaseContext?.variants.find((option) => String(option.id) === event.target.value) ?? null;
                        update(index, { item_variant: variant });
                    }}
                    options={(purchaseContext?.variants ?? []).map((variant) => ({ value: variant.id, label: `${variant.code ?? ''} ${variant.name ?? ''}`.trim() }))}
                    placeholder="Variant"
                    error={errorFor(`lines.${index}.item_variant_id`)}
                />
                <Select
                    value={row.uom?.id ?? ''}
                    onChange={(event) => {
                        const unit = purchaseContext?.allowed_purchase_uoms.find((option) => String(option.id) === event.target.value);
                        update(index, { uom: unit?.uom ?? null, auto_uom: false });
                    }}
                    options={(purchaseContext?.allowed_purchase_uoms ?? []).map((unit) => ({ value: unit.id, label: `${unit.uom?.code ?? ''} ${unit.uom?.name ?? ''}`.trim() }))}
                    placeholder={loadingContext ? 'Loading...' : 'UOM'}
                    error={errorFor(`lines.${index}.uom_id`)}
                />
            </td>
            <td className="px-3 py-3">
                <DecimalInput
                    value={row.quantity}
                    onChange={(event) => update(index, { quantity: event.target.value })}
                    error={errorFor(`lines.${index}.quantity`)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter' && isLast) {
                            event.preventDefault();
                            addRow();
                        }
                    }}
                />
            </td>
            <td className="px-3 py-3">
                <DecimalInput value={row.unit_cost} onChange={(event) => update(index, { unit_cost: event.target.value, auto_price: false })} error={errorFor(`lines.${index}.unit_cost`)} />
                {row.price_source_label && <div className="mt-1 text-xs text-slate-500">{row.price_source_label}</div>}
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
}
