import { z } from 'zod';
import { optionalDecimal, optionalInteger, optionalTrimmedString } from '../shared/form-utils';

export const customerFormSchema = z.object({
    customer_code: optionalTrimmedString(255, 'Customer code must be 255 characters or less.'),
    name: z.string().trim().min(1, 'Customer name is required.').max(255, 'Customer name must be 255 characters or less.'),
    type: z.enum(['individual', 'company'], { message: 'Choose a valid customer type.' }),
    org_unit_id: optionalInteger('Choose a valid organization unit.'),
    tax_number: optionalTrimmedString(255, 'Tax number must be 255 characters or less.'),
    registration_number: optionalTrimmedString(255, 'Registration number must be 255 characters or less.'),
    currency_id: optionalInteger('Choose a valid currency.'),
    credit_limit: optionalDecimal('Enter a valid credit limit.'),
    payment_terms_days: optionalInteger('Enter valid payment terms.'),
    ar_account_id: optionalInteger('Choose a valid AR account.'),
    status: z.enum(['active', 'inactive', 'blocked'], { message: 'Choose a valid customer status.' }),
    notes: optionalTrimmedString(4000, 'Notes must be 4000 characters or less.'),
    portal_user_enabled: z.boolean().default(true),
    user_email: optionalTrimmedString(255, 'Email must be 255 characters or less.'),
    user_first_name: optionalTrimmedString(255, 'First name must be 255 characters or less.'),
    user_last_name: optionalTrimmedString(255, 'Last name must be 255 characters or less.'),
    user_phone: optionalTrimmedString(30, 'Phone must be 30 characters or less.'),
    user_active: z.boolean().default(true),
}).superRefine((value, context) => {
    const hasPortalValues = Boolean(value.user_email || value.user_first_name || value.user_last_name || value.user_phone);

    if (!value.portal_user_enabled && !hasPortalValues) {
        return;
    }

    if (!value.user_email) {
        context.addIssue({
            code: z.ZodIssueCode.custom,
            message: 'Portal email is required.',
            path: ['user_email'],
        });
    } else if (!z.string().email().safeParse(value.user_email).success) {
        context.addIssue({
            code: z.ZodIssueCode.custom,
            message: 'Enter a valid email address.',
            path: ['user_email'],
        });
    }

    if (!value.user_first_name) {
        context.addIssue({
            code: z.ZodIssueCode.custom,
            message: 'Portal first name is required.',
            path: ['user_first_name'],
        });
    }

    if (!value.user_last_name) {
        context.addIssue({
            code: z.ZodIssueCode.custom,
            message: 'Portal last name is required.',
            path: ['user_last_name'],
        });
    }
});

export type CustomerFormInput = z.input<typeof customerFormSchema>;
export type CustomerFormValues = z.output<typeof customerFormSchema>;
