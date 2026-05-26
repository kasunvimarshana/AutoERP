import { z } from 'zod';
import { optionalDecimal, optionalInteger, optionalTrimmedString } from '../shared/form-utils';

export const warehouseFormSchema = z.object({
    org_unit_id: optionalInteger('Choose a valid organization unit.'),
    name: z.string().trim().min(1, 'Warehouse name is required.').max(255, 'Warehouse name must be 255 characters or less.'),
    code: optionalTrimmedString(100, 'Warehouse code must be 100 characters or less.'),
    image_path: optionalTrimmedString(500, 'Image path must be 500 characters or less.'),
    type: z.enum(['standard', 'virtual', 'transit', 'quarantine'], { message: 'Choose a valid warehouse type.' }),
    address_id: optionalInteger('Choose a valid address.'),
    is_active: z.boolean().default(true),
    is_default: z.boolean().default(false),
});

export type WarehouseFormInput = z.input<typeof warehouseFormSchema>;
export type WarehouseFormValues = z.output<typeof warehouseFormSchema>;

export const warehouseLocationFormSchema = z.object({
    parent_id: optionalInteger('Choose a valid parent location.'),
    name: z.string().trim().min(1, 'Location name is required.').max(255, 'Location name must be 255 characters or less.'),
    code: optionalTrimmedString(100, 'Location code must be 100 characters or less.'),
    type: z.enum(['zone', 'aisle', 'rack', 'shelf', 'bin', 'staging', 'dispatch'], { message: 'Choose a valid location type.' }),
    is_active: z.boolean().default(true),
    is_pickable: z.boolean().default(true),
    is_receivable: z.boolean().default(true),
    capacity: optionalDecimal('Enter a valid capacity.'),
});

export type WarehouseLocationFormInput = z.input<typeof warehouseLocationFormSchema>;
export type WarehouseLocationFormValues = z.output<typeof warehouseLocationFormSchema>;
