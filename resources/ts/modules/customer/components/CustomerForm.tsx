import { FormEvent, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { customerApi } from '../services/customerApi';
import type { Customer } from '../types/customer.types';

function FieldLabel({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

export function CustomerForm({ customer, mode }: { customer?: Customer; mode: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const input = {
            code: String(formData.get('customer_code') ?? ''),
            contactPerson: String(formData.get('contact_person') ?? ''),
            email: String(formData.get('email') ?? ''),
            industry: String(formData.get('industry') ?? ''),
            name: String(formData.get('customer_name') ?? ''),
            notes: String(formData.get('notes') ?? ''),
            phone: String(formData.get('phone') ?? ''),
            taxNumber: String(formData.get('tax_number') ?? ''),
        };

        try {
            const response =
                mode === 'edit' && customer
                    ? await customerApi.updateCustomer(customer.id, input)
                    : await customerApi.createCustomer(input);

            navigate(`/customers/${response.data.id}`);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save customer.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Customer records can be saved without creating a user account. User access is handled separately after the customer exists." title="Profile">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <FieldLabel>Customer code</FieldLabel>
                            <Input defaultValue={customer?.code} name="customer_code" placeholder="CUS-0001" />
                            <FieldError message={errors.customer_code?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Customer name</FieldLabel>
                            <Input defaultValue={customer?.name} name="customer_name" placeholder="Legal or trading name" />
                            <FieldError message={errors.customer_name?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Industry</FieldLabel>
                            <Select
                                defaultValue={customer?.industry}
                                name="industry"
                                options={[
                                    { label: 'Fleet operations', value: 'Fleet operations' },
                                    { label: 'Vehicle rental', value: 'Vehicle rental' },
                                    { label: 'Workshop', value: 'Workshop' },
                                    { label: 'Trading', value: 'Trading' },
                                ]}
                                placeholder="Select industry"
                            />
                            <FieldError message={errors.customer_type?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Primary contact</FieldLabel>
                            <Input defaultValue={customer?.contactPerson} name="contact_person" placeholder="Contact person" />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Phone</FieldLabel>
                            <Input defaultValue={customer?.phone} name="phone" placeholder="+94..." />
                            <FieldError message={errors.phone?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Email</FieldLabel>
                            <Input defaultValue={customer?.email} name="email" placeholder="billing@example.com" type="email" />
                            <FieldError message={errors.email?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Tax registration number</FieldLabel>
                            <Input defaultValue={customer?.taxNumber} name="tax_number" placeholder="Optional" />
                            <FieldError message={errors.tax_number?.[0]} />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <FieldLabel>Notes</FieldLabel>
                            <Textarea defaultValue={customer?.notes} name="notes" placeholder="Operational notes, billing preferences, or service instructions." />
                            <FieldError message={errors.notes?.[0]} />
                        </div>
                    </div>
                </FormSection>

                <FormSection description="Credit limits, tax behavior, finance defaults, and optional user access are backend-validated after save." title="Backend-owned setup">
                    <div className="grid gap-4 md:grid-cols-2">
                        {['Credit Profile', 'Tax Profile', 'Finance Defaults', 'Optional User Access'].map((label) => (
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4" key={label}>
                                <p className="text-sm font-semibold text-slate-900">{label}</p>
                                <p className="mt-1 text-sm text-slate-500">Managed in its own detail tab after customer save.</p>
                            </div>
                        ))}
                    </div>
                </FormSection>

                <div className="flex justify-end gap-3">
                    <Link to="/customers">
                        <Button type="button" variant="secondary">Cancel</Button>
                    </Link>
                    <Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Customer' : 'Create Customer'}</Button>
                </div>
            </div>

            <PreviewPanel
                rows={[
                    { label: 'Tenant validation', value: 'Backend-owned' },
                    { label: 'Credit exposure', value: 'Readonly backend preview later' },
                    { label: 'User account', value: 'Optional, never automatic' },
                ]}
                title="Customer validation preview"
            />
        </form>
    );
}
