import { z } from 'zod';
import { optionalInteger, optionalTrimmedString } from '../shared/form-utils';

export const organizationUnitFormSchema = z.object({
    name: z.string().trim().min(1, 'Organization unit name is required.').max(255, 'Name must be 255 characters or less.'),
    code: optionalTrimmedString(100, 'Code must be 100 characters or less.'),
    type_id: optionalInteger('Choose a valid organization unit type.'),
    parent_id: optionalInteger('Choose a valid parent organization unit.'),
    manager_user_id: optionalInteger('Choose a valid manager user.'),
    description: optionalTrimmedString(4000, 'Description must be 4000 characters or less.'),
    is_active: z.boolean().default(true),
});

export const organizationUnitTypeFormSchema = z.object({
    name: z.string().trim().min(1, 'Type name is required.').max(255, 'Type name must be 255 characters or less.'),
    level: z.preprocess((value) => Number(value), z.number().int().min(0, 'Level must be zero or greater.')),
    is_active: z.boolean().default(true),
});

export const organizationUnitAssignmentFormSchema = z.object({
    user_id: z.preprocess((value) => Number(value), z.number().int().positive('Choose a valid user.')),
    role: optionalTrimmedString(255, 'Role must be 255 characters or less.'),
    is_primary: z.boolean().default(false),
});

export type OrganizationUnitFormInput = z.input<typeof organizationUnitFormSchema>;
export type OrganizationUnitFormValues = z.output<typeof organizationUnitFormSchema>;
export type OrganizationUnitTypeFormInput = z.input<typeof organizationUnitTypeFormSchema>;
export type OrganizationUnitTypeFormValues = z.output<typeof organizationUnitTypeFormSchema>;
export type OrganizationUnitAssignmentFormInput = z.input<typeof organizationUnitAssignmentFormSchema>;
export type OrganizationUnitAssignmentFormValues = z.output<typeof organizationUnitAssignmentFormSchema>;
