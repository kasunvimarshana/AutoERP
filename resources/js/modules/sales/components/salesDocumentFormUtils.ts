import type { NamedResource } from '@/shared/types/common';
import {
    addDecimal,
    nonNegativeDecimal,
    percentageOfDecimal,
    subtractDecimal,
    sumDecimals,
} from '@/shared/utils/decimal';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import type { SalesOrder, SalesQuotation } from '../salesTypes';
import { previewLineAmounts, type EditableSalesLine } from './salesLineModel';
import type { EditableSalesAdjustment } from './salesHeaderAdjustmentModel';

export type { EditableSalesAdjustment } from './salesHeaderAdjustmentModel';
export type { EditableSalesLine } from './salesLineModel';

export interface SalesDocumentTotals {
    subtotal: string;
    discount_total: string;
    tax_total: string;
    charge_total: string;
    header_increase_total: string;
    header_decrease_total: string;
    grand_total: string;
}

export type SalesDocument = SalesQuotation | SalesOrder;

export function todayDate(): string {
    return businessDateInputValue();
}

export function asResource(value: NamedResource | null | undefined): NamedResource | null {
    return value?.id ? value : null;
}

export function lineFromDocument(
    line: NonNullable<SalesDocument['lines']>[number],
): EditableSalesLine {
    const itemVariantId = 'item_variant_id' in line && typeof line.item_variant_id === 'number'
        ? line.item_variant_id
        : null;

    return {
        client_key: `sales-line-${'id' in line && line.id ? line.id : `${Date.now()}-${Math.random()}`}`,
        item: asResource(line.item),
        item_variant: asResource('item_variant' in line ? line.item_variant : null),
        item_variant_id: itemVariantId,
        uom: asResource(line.uom),
        description: line.description ?? '',
        quantity: line.quantity ?? line.ordered_quantity ?? '1.000000',
        unit_price: line.unit_price,
        discount_calculation_type: line.discount_calculation_type ?? 'fixed',
        discount_rate: line.discount_rate ?? '0.000000',
        discount_amount: line.discount_amount,
        tax_calculation_type: line.tax_calculation_type ?? 'fixed',
        tax_rate: line.tax_rate ?? '0.000000',
        tax_amount: line.tax_amount,
        tax_group_id: '',
        charge_calculation_type: line.charge_calculation_type ?? 'fixed',
        charge_rate: line.charge_rate ?? '0.000000',
        charge_amount: line.charge_amount,
    };
}

export function adjustmentFromDocument(
    adjustment: NonNullable<SalesDocument['adjustments']>[number],
): EditableSalesAdjustment {
    return {
        name: adjustment.name,
        adjustment_type: adjustment.adjustment_type,
        effect: adjustment.effect,
        calculation_type: adjustment.calculation_type,
        calculation_base: adjustment.calculation_base ?? 'subtotal',
        rate: adjustment.rate,
        amount: adjustment.amount,
        allocation_method: adjustment.allocation_method,
        description: adjustment.description ?? '',
    };
}

export function salesDocumentTotals(
    lines: EditableSalesLine[],
    adjustments: EditableSalesAdjustment[],
): SalesDocumentTotals {
    const values = lines.map(previewLineAmounts);
    const subtotal = sumDecimals(values.map((line) => line.subtotal));
    const discount = sumDecimals(values.map((line) => line.discount));
    const tax = sumDecimals(values.map((line) => line.tax));
    const charge = sumDecimals(values.map((line) => line.charge));
    const afterDiscount = sumDecimals(
        values.map((line) => subtractDecimal(line.subtotal, line.discount)),
    );
    const afterLines = sumDecimals(values.map((line) => line.total));
    const amounts = adjustments.map((adjustment) => {
        if (adjustment.calculation_type === 'fixed') return adjustment.amount;

        const basis = adjustment.calculation_base === 'subtotal'
            ? subtotal
            : adjustment.calculation_base === 'subtotal_after_line_discount'
                ? afterDiscount
                : afterLines;

        return percentageOfDecimal(basis, adjustment.rate);
    });
    const increases = sumDecimals(
        adjustments.flatMap((row, index) => row.effect === 'increase' ? [amounts[index]] : []),
    );
    const decreases = sumDecimals(
        adjustments.flatMap((row, index) => row.effect === 'decrease' ? [amounts[index]] : []),
    );

    return {
        subtotal,
        discount_total: discount,
        tax_total: tax,
        charge_total: charge,
        header_increase_total: increases,
        header_decrease_total: decreases,
        grand_total: nonNegativeDecimal(
            subtractDecimal(addDecimal(afterLines, increases), decreases),
        ),
    };
}
