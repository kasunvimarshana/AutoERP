import type { NamedResource } from '@/shared/types/common';
import {
    addDecimal,
    multiplyDecimal,
    nonNegativeDecimal,
    percentageOfDecimal,
    subtractDecimal,
} from '@/shared/utils/decimal';

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

export interface PurchaseLinePreview {
    subtotal: string;
    discount: string;
    tax: string;
    charge: string;
    total: string;
}

export function previewLineAmounts(line: EditablePurchaseLine): PurchaseLinePreview {
    const subtotal = multiplyDecimal(line.ordered_quantity, line.unit_price);
    const discount = line.discount_calculation_type === 'percentage'
        ? percentageOfDecimal(subtotal, line.discount_rate)
        : line.discount_amount;
    const taxBase = nonNegativeDecimal(subtractDecimal(subtotal, discount));
    const tax = line.tax_calculation_type === 'percentage'
        ? percentageOfDecimal(taxBase, line.tax_rate)
        : line.tax_amount;
    const charge = line.charge_calculation_type === 'percentage'
        ? percentageOfDecimal(subtotal, line.charge_rate)
        : line.charge_amount;

    return {
        subtotal,
        discount,
        tax,
        charge,
        total: nonNegativeDecimal(addDecimal(addDecimal(subtractDecimal(subtotal, discount), tax), charge)),
    };
}

export function previewLineTotal(line: EditablePurchaseLine): string {
    return previewLineAmounts(line).total;
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
