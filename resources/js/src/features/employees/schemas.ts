import { z } from 'zod';
import { optionalTrimmedString, optionalInteger } from '../shared/form-utils';

export const employeeFormSchema = z
    .object({
        employee_code: optionalTrimmedString(255, 'Employee code must be 255 characters or less.'),
        org_unit_id: optionalInteger('Choose a valid organization unit.'),
        job_title: optionalTrimmedString(255, 'Job title must be 255 characters or less.'),
        hire_date: z.string().trim().optional(),
        termination_date: z.string().trim().optional(),
        portal_user_enabled: z.boolean().default(true),
        user_email: optionalTrimmedString(255, 'Email must be 255 characters or less.'),
        user_first_name: optionalTrimmedString(255, 'First name must be 255 characters or less.'),
        user_last_name: optionalTrimmedString(255, 'Last name must be 255 characters or less.'),
        user_phone: optionalTrimmedString(30, 'Phone must be 30 characters or less.'),
        user_active: z.boolean().default(true),
    })
    .superRefine((value, context) => {
        const hasPortalValues = Boolean(value.user_email || value.user_first_name || value.user_last_name || value.user_phone);

        if (value.portal_user_enabled || hasPortalValues) {
            if (!value.user_email) {
                context.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: 'Employee email is required.',
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
                    message: 'First name is required.',
                    path: ['user_first_name'],
                });
            }

            if (!value.user_last_name) {
                context.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: 'Last name is required.',
                    path: ['user_last_name'],
                });
            }
        }

        if (value.hire_date && value.termination_date && new Date(value.termination_date) < new Date(value.hire_date)) {
            context.addIssue({
                code: z.ZodIssueCode.custom,
                message: 'Termination date cannot be before hire date.',
                path: ['termination_date'],
            });
        }
    });

export type EmployeeFormInput = z.input<typeof employeeFormSchema>;
export type EmployeeFormValues = z.output<typeof employeeFormSchema>;
