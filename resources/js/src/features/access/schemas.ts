import { z } from 'zod';
import { optionalInteger, optionalTrimmedString } from '../shared/form-utils';

export const userFormSchema = z.object({
    email: z.string().trim().email('Enter a valid email address.'),
    first_name: z.string().trim().min(1, 'First name is required.').max(255, 'First name must be 255 characters or less.'),
    last_name: z.string().trim().min(1, 'Last name is required.').max(255, 'Last name must be 255 characters or less.'),
    phone: optionalTrimmedString(20, 'Phone must be 20 characters or less.'),
    org_unit_id: optionalInteger('Choose a valid organization unit.'),
    active: z.boolean().default(true),
    roles: z.array(z.number().int().positive()).default([]),
    address_line1: optionalTrimmedString(255, 'Address line 1 must be 255 characters or less.'),
    address_line2: optionalTrimmedString(255, 'Address line 2 must be 255 characters or less.'),
    city: optionalTrimmedString(255, 'City must be 255 characters or less.'),
    state: optionalTrimmedString(255, 'State must be 255 characters or less.'),
    postal_code: optionalTrimmedString(50, 'Postal code must be 50 characters or less.'),
    country: optionalTrimmedString(255, 'Country must be 255 characters or less.'),
});

export const roleFormSchema = z.object({
    name: z.string().trim().min(1, 'Role name is required.').max(255, 'Role name must be 255 characters or less.'),
    permission_ids: z.array(z.number().int().positive()).default([]),
});

export const profileFormSchema = z.object({
    first_name: z.string().trim().min(1, 'First name is required.').max(255, 'First name must be 255 characters or less.'),
    last_name: z.string().trim().min(1, 'Last name is required.').max(255, 'Last name must be 255 characters or less.'),
    phone: optionalTrimmedString(20, 'Phone must be 20 characters or less.'),
    address_line1: optionalTrimmedString(255, 'Address line 1 must be 255 characters or less.'),
    address_line2: optionalTrimmedString(255, 'Address line 2 must be 255 characters or less.'),
    city: optionalTrimmedString(255, 'City must be 255 characters or less.'),
    state: optionalTrimmedString(255, 'State must be 255 characters or less.'),
    postal_code: optionalTrimmedString(50, 'Postal code must be 50 characters or less.'),
    country: optionalTrimmedString(255, 'Country must be 255 characters or less.'),
});

export const changePasswordSchema = z
    .object({
        current_password: z.string().min(1, 'Current password is required.'),
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z.string().min(1, 'Password confirmation is required.'),
    })
    .superRefine((value, context) => {
        if (value.password !== value.password_confirmation) {
            context.addIssue({
                code: z.ZodIssueCode.custom,
                message: 'Password confirmation must match the new password.',
                path: ['password_confirmation'],
            });
        }
    });

export function buildAddressPayload(values: {
    address_line1?: string;
    address_line2?: string;
    city?: string;
    state?: string;
    postal_code?: string;
    country?: string;
}) {
    const address = {
        line1: values.address_line1 ?? null,
        line2: values.address_line2 ?? null,
        city: values.city ?? null,
        state: values.state ?? null,
        postal_code: values.postal_code ?? null,
        country: values.country ?? null,
    };

    return Object.values(address).some((value) => value !== null && value !== '') ? address : null;
}

export function normalizeAddressDefaults(address: Record<string, unknown> | null | undefined) {
    return {
        address_line1: typeof address?.line1 === 'string' ? address.line1 : '',
        address_line2: typeof address?.line2 === 'string' ? address.line2 : '',
        city: typeof address?.city === 'string' ? address.city : '',
        state: typeof address?.state === 'string' ? address.state : '',
        postal_code: typeof address?.postal_code === 'string' ? address.postal_code : '',
        country: typeof address?.country === 'string' ? address.country : '',
    };
}

export type UserFormInput = z.input<typeof userFormSchema>;
export type UserFormValues = z.output<typeof userFormSchema>;
export type RoleFormInput = z.input<typeof roleFormSchema>;
export type RoleFormValues = z.output<typeof roleFormSchema>;
export type ProfileFormInput = z.input<typeof profileFormSchema>;
export type ProfileFormValues = z.output<typeof profileFormSchema>;
export type ChangePasswordFormInput = z.input<typeof changePasswordSchema>;
export type ChangePasswordFormValues = z.output<typeof changePasswordSchema>;
