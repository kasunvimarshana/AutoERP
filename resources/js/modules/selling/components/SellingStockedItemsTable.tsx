/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { Fragment, useRef, useState, type Dispatch, type SetStateAction } from 'react';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Button } from '@/shared/components/Button';
import { addDecimal, compareDecimalStrings, subtractDecimal } from '@/shared/utils/decimal';
import { formatMoney } from '@/shared/utils/formatMoney';
import type { ItemLookupResource } from '@/shared/api/lookupApi';
import type { NamedResource } from '@/shared/types/common';
import { InventoryDimensionFields, type InventoryDimensionValue } from '@/modules/inventory/components/InventoryDimensionFields';
import type { SaleDiscountType } from '../sellingApi';
import { SaleDiscountInput } from './SaleDiscountInput';

const ONE_QUANTITY = '1.000000';

export interface SaleDraftLine {
    key: string;
    item: ItemLookupResource;
    quantity: string;
    discountType: SaleDiscountType;
    discountValue: string;
    dimensions: InventoryDimensionValue;
}

export function SellingStockedItemsTable({ lines, setLines, warehouse, currency, currencyCode, subtotal, lineDiscount, lineTotal }: {
    lines: SaleDraftLine[];
    setLines: Dispatch<SetStateAction<SaleDraftLine[]>>;
    warehouse: NamedResource | null;
    currency: string;
    currencyCode?: string;
    subtotal: string;
    lineDiscount: (line: SaleDraftLine) => string;
    lineTotal: (line: SaleDraftLine) => string;
}) {
    const quantityInputs = useRef<Record<string, HTMLInputElement | null>>({});
    const [expandedLines, setExpandedLines] = useState<Set<string>>(() => new Set());
    const updateLine = (key: string, changes: Partial<SaleDraftLine>) => setLines((current) => current.map((line) => line.key === key ? { ...line, ...changes } : line));
    const toggleDimensions = (key: string) => setExpandedLines((current) => {
        const next = new Set(current);
        if (next.has(key)) next.delete(key);
        else next.add(key);
        return next;
    });

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <p className="border-b border-slate-200 bg-slate-50 px-4 py-2 text-xs text-slate-600 2xl:hidden">Scroll sideways to view all item details.</p>
            <div className="overflow-x-auto">
                <table className="w-full min-w-[1200px] table-fixed text-left text-sm">
                    <colgroup>
                        <col className="w-[24%]" /><col className="w-[16%]" /><col className="w-[7%]" />
                        <col className="w-[14%]" /><col className="w-[18%]" /><col className="w-[11%]" /><col className="w-[10%]" />
                    </colgroup>
                    <thead className="bg-slate-100 text-slate-700">
                        <tr>
                            <th className="px-4 py-3 font-semibold">Item</th>
                            <th className="px-3 py-3 font-semibold">Qty</th>
                            <th className="px-3 py-3 font-semibold">Unit</th>
                            <th className="px-3 py-3 text-right font-semibold">Unit price ({currencyCode ?? currency})</th>
                            <th className="px-3 py-3 font-semibold">Discount</th>
                            <th className="px-3 py-3 text-right font-semibold">Line total ({currencyCode ?? currency})</th>
                            <th className="px-3 py-3 text-center font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200">
                        {lines.map((line) => (
                            <Fragment key={line.key}>
                            <tr>
                                <td className="px-4 py-4 align-middle">
                                    <div className="break-words font-semibold text-slate-900">{line.item.name}</div>
                                    {line.item.code && <div className="mt-0.5 text-xs text-slate-500">{line.item.code}</div>}
                                    <button type="button" className="mt-2 text-xs font-semibold text-slate-600 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" aria-label={`Dimensions for ${line.item.name}`} aria-expanded={expandedLines.has(line.key)} onClick={() => toggleDimensions(line.key)}>{expandedLines.has(line.key) ? '▾' : '▸'} Dimensions</button>
                                </td>
                                <td className="px-3 py-4 align-middle">
                                    <div className="flex items-center gap-1">
                                        <Button type="button" variant="secondary" size="sm" className="h-10 w-9 shrink-0" aria-label={`Decrease ${line.item.name} quantity`} disabled={compareDecimalStrings(line.quantity || '0', ONE_QUANTITY) <= 0} onClick={() => updateLine(line.key, { quantity: subtractDecimal(line.quantity, ONE_QUANTITY) })}>−</Button>
                                        <DecimalInput ref={(input) => { quantityInputs.current[line.key] = input; }} aria-label={`Quantity for ${line.item.name}`} className="min-w-0 text-center tabular-nums" maxFractionDigits={3} value={line.quantity} onChange={(event) => updateLine(line.key, { quantity: event.target.value })} />
                                        <Button type="button" variant="secondary" size="sm" className="h-10 w-9 shrink-0" aria-label={`Increase ${line.item.name} quantity`} onClick={() => updateLine(line.key, { quantity: addDecimal(line.quantity || '0', ONE_QUANTITY) })}>+</Button>
                                    </div>
                                </td>
                                <td className="px-3 py-4 align-middle text-slate-600">{line.item.base_uom?.code ?? '—'}</td>
                                <td className="px-3 py-4 text-right align-middle font-medium tabular-nums text-slate-900">{line.item.resolved_sales_unit_price == null ? 'Not configured' : formatMoney(line.item.resolved_sales_unit_price, currencyCode)}</td>
                                <td className="px-3 py-4 align-middle">
                                    <SaleDiscountInput label="Discount" ariaLabel={`Discount for ${line.item.name}`} currency={currency} type={line.discountType} value={line.discountValue} onTypeChange={(discountType) => updateLine(line.key, { discountType })} onValueChange={(discountValue) => updateLine(line.key, { discountValue })} hideLabel />
                                    <p className="mt-1 text-xs text-slate-500">Applied: {formatMoney(lineDiscount(line), currencyCode)}</p>
                                </td>
                                <td className="px-3 py-4 text-right align-middle font-semibold tabular-nums text-slate-900">{line.item.resolved_sales_unit_price == null ? '—' : formatMoney(lineTotal(line), currencyCode)}</td>
                                <td className="px-3 py-4 align-middle">
                                    <div className="flex justify-center gap-1">
                                        <button type="button" className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-blue-600 transition hover:border-blue-200 hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" onClick={() => { setExpandedLines((current) => new Set(current).add(line.key)); quantityInputs.current[line.key]?.focus(); }} aria-label={`Edit ${line.item.name}`} title={`Edit ${line.item.name}`}>
                                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.9" className="h-5 w-5" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.75v2.5h2.5L14.5 8l-2.5-2.5-8.25 8.25Z" /><path strokeLinecap="round" strokeLinejoin="round" d="m10.75 4.75 2.5 2.5" /></svg>
                                        </button>
                                        <button type="button" className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-rose-600 transition hover:border-rose-200 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500" onClick={() => setLines((current) => current.filter((row) => row.key !== line.key))} aria-label={`Remove ${line.item.name}`} title={`Remove ${line.item.name}`}>
                                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.9" className="h-5 w-5" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M5.75 7.25h8.5" /><path strokeLinecap="round" strokeLinejoin="round" d="M8 7.25V5.75a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1.5" /><path strokeLinecap="round" strokeLinejoin="round" d="M7 7.25v6a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-6" /><path strokeLinecap="round" strokeLinejoin="round" d="M8.75 9.25v3" /><path strokeLinecap="round" strokeLinejoin="round" d="M11.25 9.25v3" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            {expandedLines.has(line.key) && <tr className="bg-slate-50/60"><td colSpan={7} className="px-4 pb-4 pt-1"><InventoryDimensionFields item={line.item} warehouse={warehouse} value={line.dimensions} includeLocation={false} includeSerial includeUom={false} defaultOpen onChange={(dimensions) => updateLine(line.key, { dimensions })} /></td></tr>}
                            </Fragment>
                        ))}
                    </tbody>
                    <tfoot className="border-t border-slate-200 bg-slate-100 text-slate-900">
                        <tr><td colSpan={5} className="px-4 py-3 text-right font-medium text-slate-600">Items subtotal</td><td className="px-3 py-3 text-right font-semibold tabular-nums">{formatMoney(subtotal, currencyCode)}</td><td /></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    );
}
