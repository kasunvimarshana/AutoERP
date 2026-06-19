import type { NamedResource } from '@/shared/types/common';
import {
    addDecimal,
    multiplyDecimal,
    percentageOfDecimal,
    subtractDecimal,
} from '@/shared/utils/decimal';

export type SalesLineCalculationType = 'fixed' | 'percentage';
export type SalesLineTaxMode = 'manual' | 'tax_group';
export type SalesLinePricingState = 'auto' | 'manual_confirmed' | 'persisted';

export interface EditableSalesLine {
    client_key: string;
    item: NamedResource | null;
    item_variant: NamedResource | null;
    item_variant_id?: number | null;
    uom: NamedResource | null;
    price_source_label?: string;
    price_source?: string | null;
    price_source_id?: number | null;
    pricing_context_hash?: string | null;
    pricing_state?: SalesLinePricingState;
    manual_price_confirmed?: boolean;
    auto_price?: boolean;
    auto_uom?: boolean;
    description: string;
    quantity: string;
    unit_price: string;
    discount_calculation_type: SalesLineCalculationType;
    discount_rate: string;
    discount_amount: string;
    tax_calculation_type: SalesLineCalculationType;
    tax_rate: string;
    tax_amount: string;
    tax_group_id: string;
    charge_calculation_type: SalesLineCalculationType;
    charge_rate: string;
    charge_amount: string;
}

export interface SalesLinePreview {
    subtotal: string;
    discount: string;
    tax: string;
    charge: string;
    total: string;
}

export type SalesLineField =
    | 'item_id'
    | 'item_variant_id'
    | 'uom_id'
    | 'description'
    | 'quantity'
    | 'unit_price'
    | 'pricing_mode'
    | 'manual_price_confirmed'
    | 'pricing_context_hash'
    | 'discount_calculation_type'
    | 'discount_rate'
    | 'discount_amount'
    | 'tax_calculation_type'
    | 'tax_rate'
    | 'tax_amount'
    | 'tax_group_id'
    | 'charge_calculation_type'
    | 'charge_rate'
    | 'charge_amount';

export interface SalesLineTaxGroupOption {
    id: number;
    code?: string | null;
    name?: string | null;
}

export interface SalesLineEditorConfig {
    unitLabel: string;
    taxMode: SalesLineTaxMode;
    taxGroupOptions?: SalesLineTaxGroupOption[];
    defaultLine?: Partial<EditableSalesLine>;
    unitPriceMustBePositive?: boolean;
    emptyMessage: string;
}

export function salesLineKey(): string {
    return typeof crypto !== 'undefined' && 'randomUUID' in crypto
        ? crypto.randomUUID()
        : `${Date.now()}-${Math.random()}`;
}

export function emptySalesLine(overrides: Partial<EditableSalesLine> = {}): EditableSalesLine {
    return {
        client_key: salesLineKey(),
        item: null,
        item_variant: null,
        item_variant_id: null,
        uom: null,
        price_source_label: undefined,
        price_source: null,
        price_source_id: null,
        pricing_context_hash: null,
        pricing_state: 'auto',
        manual_price_confirmed: false,
        auto_price: true,
        auto_uom: true,
        description: '',
        quantity: '1.000000',
        unit_price: '0.000000',
        discount_calculation_type: 'fixed',
        discount_rate: '0.000000',
        discount_amount: '0.000000',
        tax_calculation_type: 'fixed',
        tax_rate: '0.000000',
        tax_amount: '0.000000',
        tax_group_id: '',
        charge_calculation_type: 'fixed',
        charge_rate: '0.000000',
        charge_amount: '0.000000',
        ...overrides,
    };
}

export function previewLineAmounts(line: EditableSalesLine): SalesLinePreview {
    const subtotal = multiplyDecimal(line.quantity, line.unit_price);
    const discount = line.discount_calculation_type === 'percentage'
        ? percentageOfDecimal(subtotal, line.discount_rate)
        : line.discount_amount;
    const taxBase = subtractDecimal(subtotal, discount);
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
        total: addDecimal(addDecimal(subtractDecimal(subtotal, discount), tax), charge),
    };
}

export function previewLineTotal(line: EditableSalesLine): string {
    return previewLineAmounts(line).total;
}

export function formatSalesLineItem(line: EditableSalesLine): string {
    if (!line.item) return '-';
    return [line.item.code, line.item.name].filter(Boolean).join(' - ');
}

export function formatSalesLineDiscount(line: EditableSalesLine): string {
    return line.discount_calculation_type === 'percentage' ? `${line.discount_rate}%` : line.discount_amount;
}

export function formatSalesLineTax(line: EditableSalesLine, config: SalesLineEditorConfig): string {
    if (config.taxMode === 'tax_group') {
        const option = config.taxGroupOptions?.find((group) => String(group.id) === line.tax_group_id);
        return option ? [option.code, option.name].filter(Boolean).join(' - ') : 'Default';
    }

    return line.tax_calculation_type === 'percentage' ? `${line.tax_rate}%` : line.tax_amount;
}

export function formatSalesLineCharge(line: EditableSalesLine): string {
    return line.charge_calculation_type === 'percentage' ? `${line.charge_rate}%` : line.charge_amount;
}
