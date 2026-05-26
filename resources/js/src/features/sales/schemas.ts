import { z } from 'zod';
import { optionalDecimal, optionalInteger, optionalTrimmedString, requiredInteger } from '../shared/form-utils';

export const salesOrderFormSchema = z.object({
    customer_id: requiredInteger('Choose a customer.'),
    warehouse_id: requiredInteger('Choose a warehouse.'),
    currency_id: requiredInteger('Enter a valid currency ID.'),
    order_date: z.string().trim().min(1, 'Order date is required.'),
    requested_delivery_date: optionalTrimmedString(50, 'Requested delivery date must be 50 characters or less.'),
    org_unit_id: optionalInteger('Choose a valid organization unit.'),
    price_list_id: optionalInteger('Choose a valid price list.'),
    exchange_rate: optionalDecimal('Enter a valid exchange rate.'),
    subtotal: optionalDecimal('Enter a valid subtotal.'),
    tax_total: optionalDecimal('Enter a valid tax total.'),
    discount_total: optionalDecimal('Enter a valid discount total.'),
    grand_total: optionalDecimal('Enter a valid grand total.'),
    notes: optionalTrimmedString(4000, 'Notes must be 4000 characters or less.'),
});

export type SalesOrderFormInput = z.input<typeof salesOrderFormSchema>;
export type SalesOrderFormValues = z.output<typeof salesOrderFormSchema>;
