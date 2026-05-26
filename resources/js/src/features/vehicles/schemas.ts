import { z } from 'zod';
import { emptyToUndefined, optionalDecimal, optionalInteger, optionalTrimmedString } from '../shared/form-utils';

function optionalYear() {
    return z.preprocess((value) => {
        const normalized = emptyToUndefined(value);
        if (normalized === undefined) {
            return undefined;
        }

        return Number(normalized);
    }, z.number().int('Enter a valid year.').min(1900, 'Year must be 1900 or later.').max(2100, 'Year must be 2100 or earlier.').optional());
}

export const vehicleFormSchema = z.object({
    org_unit_id: optionalInteger('Choose a valid organization unit.'),
    customer_id: optionalInteger('Choose a valid customer.'),
    supplier_id: optionalInteger('Choose a valid supplier.'),
    ownership_type: z.enum(['company_owned', 'third_party_owned', 'customer_owned', 'leased'], { message: 'Choose a valid ownership type.' }),
    asset_code: optionalTrimmedString(120, 'Vehicle number must be 120 characters or less.'),
    make: z.string().trim().min(1, 'Make is required.').max(120, 'Make must be 120 characters or less.'),
    model: z.string().trim().min(1, 'Model is required.').max(120, 'Model must be 120 characters or less.'),
    year: optionalYear(),
    vin: optionalTrimmedString(64, 'VIN must be 64 characters or less.'),
    registration_number: optionalTrimmedString(64, 'Registration number must be 64 characters or less.'),
    chassis_number: optionalTrimmedString(64, 'Chassis number must be 64 characters or less.'),
    fuel_type: z.enum(['petrol', 'diesel', 'hybrid', 'electric', 'cng', 'lpg', 'other'], { message: 'Choose a valid fuel type.' }),
    transmission: z.enum(['manual', 'automatic', 'cvt', 'semi_automatic', 'other'], { message: 'Choose a valid transmission type.' }),
    odometer: optionalDecimal('Enter a valid mileage.').refine((value) => value === undefined || value >= 0, 'Mileage cannot be negative.'),
    rental_status: z.enum(['available', 'reserved', 'rented', 'blocked'], { message: 'Choose a valid availability status.' }),
    service_status: z.enum(['none', 'in_maintenance', 'under_repair', 'awaiting_parts', 'quality_check', 'ready_for_pickup', 'returned_to_fleet'], {
        message: 'Choose a valid service status.',
    }),
    next_maintenance_due_at: optionalTrimmedString(40, 'Enter a valid maintenance date.'),
    primary_image_path: optionalTrimmedString(500, 'Image path must be 500 characters or less.'),
    color: optionalTrimmedString(80, 'Color must be 80 characters or less.'),
    engine_number: optionalTrimmedString(120, 'Engine number must be 120 characters or less.'),
    notes: optionalTrimmedString(1000, 'Notes must be 1000 characters or less.'),
    is_active: z.boolean().default(true),
});

export type VehicleFormInput = z.input<typeof vehicleFormSchema>;
export type VehicleFormValues = z.output<typeof vehicleFormSchema>;
