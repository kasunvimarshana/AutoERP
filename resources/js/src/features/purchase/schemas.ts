import { z } from 'zod';
import { optionalDecimal, optionalInteger, optionalTrimmedString, requiredInteger } from '../shared/form-utils';

const optionalLineInteger = optionalInteger('Choose a valid value.');
const quantityGreaterThanZero = z.preprocess((value) => {
    if (value === '' || value === null || value === undefined) {
        return undefined;
    }

    return Number(value);
}, z.number({ message: 'Enter a valid quantity.' }).finite('Enter a valid quantity.').gt(0, 'Qty must be greater than 0.'));
const optionalNonNegativeDecimal = z.preprocess((value) => {
    if (value === '' || value === null || value === undefined) {
        return undefined;
    }

    return Number(value);
}, z.number({ message: 'Enter a valid number.' }).finite('Enter a valid number.').min(0, 'Value must be 0 or greater.').optional());
const requiredNonNegativeDecimal = z.preprocess((value) => {
    if (value === '' || value === null || value === undefined) {
        return undefined;
    }

    return Number(value);
}, z.number({ message: 'Enter a valid amount.' }).finite('Enter a valid amount.').min(0, 'Value must be 0 or greater.'));

const discountTypeSchema = z.union([z.literal('percentage'), z.literal('fixed'), z.literal('')]).optional();

export const purchaseOrderLineFormSchema = z.object({
    id: optionalLineInteger,
    item_id: requiredInteger('Item is required.'),
    variant_id: optionalLineInteger,
    description: optionalTrimmedString(1000, 'Description must be 1000 characters or less.'),
    uom_id: requiredInteger('UOM is required.'),
    ordered_qty: quantityGreaterThanZero,
    received_qty: optionalNonNegativeDecimal,
    rejected_qty: optionalNonNegativeDecimal,
    invoiced_qty: optionalNonNegativeDecimal,
    unit_price: requiredNonNegativeDecimal,
    discount_type: discountTypeSchema,
    discount_value: optionalNonNegativeDecimal,
    tax_group_id: optionalLineInteger,
    tax_amount: optionalNonNegativeDecimal,
    account_id: optionalLineInteger,
});

export const purchaseOrderFormSchema = z.object({
    supplier_id: requiredInteger('Choose a supplier.'),
    warehouse_id: requiredInteger('Choose a warehouse.'),
    currency_id: optionalInteger('Choose a currency.'),
    po_number: optionalTrimmedString(255, 'PO number must be 255 characters or less.'),
    order_date: z.string().trim().min(1, 'Order date is required.'),
    expected_date: optionalTrimmedString(50, 'Expected date must be 50 characters or less.'),
    organization_unit_id: optionalInteger('Choose a valid organization unit.'),
    exchange_rate: optionalDecimal('Enter a valid exchange rate.'),
    header_discount_type: discountTypeSchema,
    header_discount_value: optionalNonNegativeDecimal,
    header_tax_group_id: optionalLineInteger,
    notes: optionalTrimmedString(4000, 'Notes must be 4000 characters or less.'),
    lines: z.array(purchaseOrderLineFormSchema).min(1, 'Add at least one item line.'),
});

export type PurchaseOrderFormInput = z.input<typeof purchaseOrderFormSchema>;
export type PurchaseOrderFormValues = z.output<typeof purchaseOrderFormSchema>;

export const grnLineFormSchema = z.object({
    id: optionalLineInteger,
    purchase_order_line_id: optionalLineInteger,
    item_id: requiredInteger('Item is required.'),
    variant_id: optionalLineInteger,
    description: optionalTrimmedString(1000, 'Description must be 1000 characters or less.'),
    warehouse_id: optionalLineInteger,
    location_id: optionalLineInteger,
    uom_id: requiredInteger('UOM is required.'),
    expected_qty: optionalNonNegativeDecimal,
    received_qty: quantityGreaterThanZero,
    rejected_qty: optionalNonNegativeDecimal,
    unit_price: requiredNonNegativeDecimal,
    tax_group_id: optionalLineInteger,
});

export const grnFormSchema = z.object({
    supplier_id: requiredInteger('Choose a supplier.'),
    warehouse_id: requiredInteger('Choose a warehouse.'),
    purchase_order_id: optionalInteger('Choose a valid purchase order.'),
    currency_id: optionalInteger('Choose a currency.'),
    grn_number: optionalTrimmedString(255, 'GRN number must be 255 characters or less.'),
    received_date: z.string().trim().min(1, 'Received date is required.'),
    exchange_rate: optionalDecimal('Enter a valid exchange rate.'),
    notes: optionalTrimmedString(4000, 'Notes must be 4000 characters or less.'),
    lines: z.array(grnLineFormSchema).min(1, 'Add at least one received item line.'),
});

export type GrnFormInput = z.input<typeof grnFormSchema>;
export type GrnFormValues = z.output<typeof grnFormSchema>;
