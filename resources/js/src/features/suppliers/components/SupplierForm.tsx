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
import type { SupplierFormInput, SupplierFormValues } from '../schemas';

type SupplierFormProps = {
    form: UseFormReturn<SupplierFormInput, unknown, SupplierFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onSubmit: (values: SupplierFormValues) => void | Promise<void>;
    organizationUnits: OrganizationUnitRecord[];
};

export function SupplierForm({ form, formError = null, isSubmitting, mode, onSubmit, organizationUnits }: SupplierFormProps) {
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
                    description="Capture supplier identity, procurement defaults, and ownership details without leaving the shared ERP master-data layout."
                    title="Supplier profile"
                >
                    <FormGrid>
                        <FormField error={errors.name?.message} label="Supplier Name" required>
                            <Input error={errors.name?.message} placeholder="Global Components Ltd" {...register('name')} />
                        </FormField>

                        <FormField error={errors.supplier_code?.message} label="Supplier Code">
                            <Input error={errors.supplier_code?.message} placeholder="SUP-2041" {...register('supplier_code')} />
                        </FormField>

                        <FormField error={errors.type?.message} label="Supplier Type" required>
                            <Select error={errors.type?.message} {...register('type')}>
                                <option value="company">Company</option>
                                <option value="individual">Individual</option>
                            </Select>
                        </FormField>

                        <FormField error={errors.status?.message} label="Status" required>
                            <Select error={errors.status?.message} {...register('status')}>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
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

                        <FormField error={errors.currency_id?.message} label="Currency ID">
                            <Input error={errors.currency_id?.message} placeholder="1" type="number" {...register('currency_id')} />
                        </FormField>

                        <FormField error={errors.ap_account_id?.message} label="AP Account ID">
                            <Input error={errors.ap_account_id?.message} placeholder="2100" type="number" {...register('ap_account_id')} />
                        </FormField>

                        <FormField error={errors.tax_number?.message} label="Tax Number">
                            <Input error={errors.tax_number?.message} placeholder="VAT-991117" {...register('tax_number')} />
                        </FormField>

                        <FormField error={errors.registration_number?.message} label="Registration Number">
                            <Input error={errors.registration_number?.message} placeholder="REG-22190" {...register('registration_number')} />
                        </FormField>

                        <FormField className="xl:col-span-3" error={errors.notes?.message} label="Notes">
                            <Textarea error={errors.notes?.message} placeholder="Procurement notes, relationship context, or onboarding guidance." {...register('notes')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard
                    description="Suppliers can also carry a linked portal-facing user account for collaboration and self-service workflows."
                    title="Portal user"
                >
                    <FormGrid>
                        <FormField error={errors.portal_user_enabled?.message} label="Portal Access">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Manage the linked supplier-side user profile directly from this master record."
                                label="Manage linked portal user"
                                {...register('portal_user_enabled')}
                            />
                        </FormField>

                        <FormField error={errors.user_active?.message} label="Portal Status">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Inactive linked users stay on file but cannot authenticate."
                                label="Portal user is active"
                                {...register('user_active')}
                            />
                        </FormField>

                        <div className="hidden xl:block" />

                        <FormField error={errors.user_first_name?.message} label="First Name" required={portalUserEnabled}>
                            <Input error={errors.user_first_name?.message} placeholder="Nimal" {...register('user_first_name')} />
                        </FormField>

                        <FormField error={errors.user_last_name?.message} label="Last Name" required={portalUserEnabled}>
                            <Input error={errors.user_last_name?.message} placeholder="Fernando" {...register('user_last_name')} />
                        </FormField>

                        <FormField error={errors.user_email?.message} label="Email" required={portalUserEnabled}>
                            <Input error={errors.user_email?.message} placeholder="supplier@example.com" type="email" {...register('user_email')} />
                        </FormField>

                        <FormField error={errors.user_phone?.message} label="Phone">
                            <Input error={errors.user_phone?.message} placeholder="+94 71 222 3344" {...register('user_phone')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar leading={<p className="text-sm text-stone-500">Supplier contacts, products, pricing, purchasing, and AP context become available from the supplier profile after save.</p>}>
                    <Link to="/suppliers">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create Supplier' : 'Save Changes'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
