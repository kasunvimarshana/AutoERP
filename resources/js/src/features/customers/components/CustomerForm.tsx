import type { UseFormReturn } from 'react-hook-form';
import { Link } from 'react-router-dom';
import { ActionBar } from '../../../components/forms/ActionBar';
import { Checkbox } from '../../../components/forms/Checkbox';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Textarea } from '../../../components/forms/Textarea';
import { Button } from '../../../components/ui/Button';
import type { OrganizationUnitRecord } from '../../organization/types';
import type { CustomerFormInput, CustomerFormValues } from '../schemas';

type CustomerFormProps = {
    form: UseFormReturn<CustomerFormInput, unknown, CustomerFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onSubmit: (values: CustomerFormValues) => void | Promise<void>;
    organizationUnits: OrganizationUnitRecord[];
};

export function CustomerForm({ form, formError = null, isSubmitting, mode, onSubmit, organizationUnits }: CustomerFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
        watch,
    } = form;

    const portalUserEnabled = watch('portal_user_enabled');

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard
                    description="Capture the core customer identity, commercial standing, and ownership details in the same shared master-data form structure used across the Product module."
                    title="Customer profile"
                >
                    <FormGrid>
                        <FormField error={errors.name?.message} label="Customer Name" required>
                            <Input error={errors.name?.message} placeholder="Apex Retail Group" {...register('name')} />
                        </FormField>

                        <FormField error={errors.customer_code?.message} label="Customer Code">
                            <Input error={errors.customer_code?.message} placeholder="CUST-1001" {...register('customer_code')} />
                        </FormField>

                        <FormField error={errors.type?.message} label="Customer Type" required>
                            <Select error={errors.type?.message} {...register('type')}>
                                <option value="company">Company</option>
                                <option value="individual">Individual</option>
                            </Select>
                        </FormField>

                        <FormField error={errors.status?.message} label="Status" required>
                            <Select error={errors.status?.message} {...register('status')}>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="blocked">Blocked</option>
                            </Select>
                        </FormField>

                        <FormField error={errors.org_unit_id?.message} label="Organization Unit">
                            <Select error={errors.org_unit_id?.message} {...register('org_unit_id')}>
                                <option value="">Select unit</option>
                                {organizationUnits.map((organizationUnit) => (
                                    <option key={organizationUnit.id} value={organizationUnit.id}>
                                        {organizationUnit.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField error={errors.payment_terms_days?.message} label="Payment Terms Days">
                            <Input error={errors.payment_terms_days?.message} placeholder="30" type="number" {...register('payment_terms_days')} />
                        </FormField>

                        <FormField error={errors.credit_limit?.message} label="Credit Limit">
                            <Input error={errors.credit_limit?.message} placeholder="10000" step="0.01" type="number" {...register('credit_limit')} />
                        </FormField>

                        <FormField error={errors.currency_id?.message} label="Currency ID">
                            <Input error={errors.currency_id?.message} placeholder="1" type="number" {...register('currency_id')} />
                        </FormField>

                        <FormField error={errors.ar_account_id?.message} label="AR Account ID">
                            <Input error={errors.ar_account_id?.message} placeholder="1100" type="number" {...register('ar_account_id')} />
                        </FormField>

                        <FormField error={errors.tax_number?.message} label="Tax Number">
                            <Input error={errors.tax_number?.message} placeholder="TAX-REG-7781" {...register('tax_number')} />
                        </FormField>

                        <FormField error={errors.registration_number?.message} label="Registration Number">
                            <Input error={errors.registration_number?.message} placeholder="REG-44771" {...register('registration_number')} />
                        </FormField>

                        <FormField className="xl:col-span-3" error={errors.notes?.message} label="Notes">
                            <Textarea error={errors.notes?.message} placeholder="Internal notes, collection guidance, or account context." {...register('notes')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard
                    description="The customer contract allows an associated portal user record. This block stays available on both create and edit flows so commercial and self-service data can stay aligned."
                    title="Portal user"
                >
                    <FormGrid>
                        <FormField error={errors.portal_user_enabled?.message} label="Portal Access">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Keep a linked user profile for login, account ownership, and future self-service experiences."
                                label="Manage linked portal user"
                                {...register('portal_user_enabled')}
                            />
                        </FormField>

                        <FormField error={errors.user_active?.message} label="Portal Status">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Inactive linked users stay on record but cannot authenticate."
                                label="Portal user is active"
                                {...register('user_active')}
                            />
                        </FormField>

                        <div className="hidden xl:block" />

                        <FormField error={errors.user_first_name?.message} label="First Name" required={portalUserEnabled}>
                            <Input error={errors.user_first_name?.message} placeholder="Anika" {...register('user_first_name')} />
                        </FormField>

                        <FormField error={errors.user_last_name?.message} label="Last Name" required={portalUserEnabled}>
                            <Input error={errors.user_last_name?.message} placeholder="Perera" {...register('user_last_name')} />
                        </FormField>

                        <FormField error={errors.user_email?.message} label="Email" required={portalUserEnabled}>
                            <Input error={errors.user_email?.message} placeholder="customer@example.com" type="email" {...register('user_email')} />
                        </FormField>

                        <FormField error={errors.user_phone?.message} label="Phone">
                            <Input error={errors.user_phone?.message} placeholder="+94 77 123 4567" {...register('user_phone')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar
                    leading={<p className="text-sm text-stone-500">Addresses, contacts, customer pricing, sales, and AR context become available from the profile page after this record is saved.</p>}
                >
                    <Link to="/customers">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create Customer' : 'Save Changes'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
