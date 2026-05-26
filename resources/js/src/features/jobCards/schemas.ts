import { z } from 'zod';

const numericString = z
    .union([z.string(), z.number()])
    .transform((value) => (typeof value === 'number' ? value : Number(value)))
    .pipe(z.number().finite().min(0));

export const jobCardStepOneSchema = z.object({
    vehicle_id: z.coerce.number().int().positive('Vehicle is required.'),
    customer_id: z.coerce.number().int().positive('Customer is required.'),
    job_card_no: z.string().trim().min(1, 'Manual job card number is required.').max(120, 'Job card number must be 120 characters or fewer.'),
    service_type: z.enum(['maintenance', 'repair', 'inspection', 'accident', 'other']),
    mileage: numericString,
    monthly_mileage: numericString,
    scheduled_at: z.string().trim().min(1, 'Next service date is required.'),
    order_discount_type: z.enum(['none', 'percentage', 'fixed']),
    order_discount_value: numericString,
    workflow_status: z.enum(['draft', 'scheduled', 'in_progress', 'awaiting_parts', 'quality_check', 'completed', 'cancelled']),
    payment_status: z.enum(['unpaid', 'partial_paid', 'paid']),
});

export const jobCardFormSchema = jobCardStepOneSchema.extend({
    service_item: z.string().trim().min(1, 'Service item is required.'),
    supervisor_user_id: z.coerce.number().int().positive('Supervisor is required to submit this job card.'),
});

export type JobCardStepOneValues = z.infer<typeof jobCardStepOneSchema>;
export type JobCardFormValues = z.infer<typeof jobCardFormSchema>;
