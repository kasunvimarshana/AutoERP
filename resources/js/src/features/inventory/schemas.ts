import { z } from 'zod';
import { optionalDecimal, optionalInteger, optionalTrimmedString, requiredInteger } from '../shared/form-utils';

export const transferOrderFormSchema = z.object({
    from_warehouse_id: requiredInteger('Choose a source warehouse.'),
    to_warehouse_id: requiredInteger('Choose a destination warehouse.'),
    transfer_number: z.string().trim().min(1, 'Transfer number is required.').max(255, 'Transfer number must be 255 characters or less.'),
    status: z.enum(['draft', 'approved', 'in_transit', 'received', 'cancelled']).default('draft'),
    request_date: z.string().trim().min(1, 'Request date is required.'),
    expected_date: optionalTrimmedString(50, 'Expected date must be 50 characters or less.'),
    notes: optionalTrimmedString(4000, 'Notes must be 4000 characters or less.'),
    lines: z.array(
        z.object({
            product_id: requiredInteger('Choose a product.'),
            uom_id: requiredInteger('Choose a unit of measure.'),
            requested_qty: z.coerce.number().positive('Requested quantity must be greater than zero.'),
            unit_cost: optionalDecimal('Enter a valid unit cost.'),
            from_location_id: optionalInteger('Choose a valid source location.'),
            to_location_id: optionalInteger('Choose a valid destination location.'),
        }),
    ).min(1, 'Add at least one transfer line.'),
});

export type TransferOrderFormInput = z.input<typeof transferOrderFormSchema>;
export type TransferOrderFormValues = z.output<typeof transferOrderFormSchema>;

export const cycleCountFormSchema = z.object({
    warehouse_id: requiredInteger('Choose a warehouse.'),
    location_id: optionalInteger('Choose a valid location.'),
    counted_by_user_id: optionalInteger('Choose a valid user.'),
    lines: z.array(
        z.object({
            product_id: requiredInteger('Choose a product.'),
            counted_qty: optionalDecimal('Enter a valid counted quantity.'),
            unit_cost: optionalDecimal('Enter a valid unit cost.'),
        }),
    ).min(1, 'Add at least one cycle count line.'),
});

export type CycleCountFormInput = z.input<typeof cycleCountFormSchema>;
export type CycleCountFormValues = z.output<typeof cycleCountFormSchema>;

export const stockReservationFormSchema = z.object({
    product_id: requiredInteger('Choose a product.'),
    location_id: requiredInteger('Choose a location.'),
    quantity: z.coerce.number().positive('Reservation quantity must be greater than zero.'),
    reserved_for_type: optionalTrimmedString(255, 'Reserved-for type must be 255 characters or less.'),
    reserved_for_id: optionalInteger('Enter a valid reservation target ID.'),
    expires_at: optionalTrimmedString(50, 'Expiry date must be 50 characters or less.'),
});

export type StockReservationFormInput = z.input<typeof stockReservationFormSchema>;
export type StockReservationFormValues = z.output<typeof stockReservationFormSchema>;

export const valuationConfigFormSchema = z.object({
    org_unit_id: optionalInteger('Choose a valid organization unit.'),
    warehouse_id: optionalInteger('Choose a valid warehouse.'),
    product_id: optionalInteger('Choose a valid product.'),
    transaction_type: optionalTrimmedString(50, 'Transaction type must be 50 characters or less.'),
    valuation_method: z.enum(['fifo', 'lifo', 'fefo', 'weighted_average', 'standard', 'specific']),
    allocation_strategy: z.enum(['fifo', 'lifo', 'fefo', 'nearest_bin', 'manual']),
    is_active: z.boolean().default(true),
});

export type ValuationConfigFormInput = z.input<typeof valuationConfigFormSchema>;
export type ValuationConfigFormValues = z.output<typeof valuationConfigFormSchema>;
