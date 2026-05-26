import type { UseFormReturn } from 'react-hook-form';
import { Link } from 'react-router-dom';
import { ActionBar } from '../../../components/forms/ActionBar';
import { Checkbox } from '../../../components/forms/Checkbox';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Button } from '../../../components/ui/Button';
import type { OrganizationUnitRecord } from '../../organization/types';
import type { EmployeeFormInput, EmployeeFormValues } from '../schemas';

type EmployeeFormProps = {
    form: UseFormReturn<EmployeeFormInput, unknown, EmployeeFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onSubmit: (values: EmployeeFormValues) => void | Promise<void>;
    organizationUnits: OrganizationUnitRecord[];
};

export function EmployeeForm({ form, formError = null, isSubmitting, mode, onSubmit, organizationUnits }: EmployeeFormProps) {
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
                <SectionCard description="Employee records sit on top of linked user profiles and add workforce-specific attributes like codes, job titles, and employment dates." title="Employee profile">
                    <FormGrid>
                        <FormField error={errors.employee_code?.message} label="Employee Code">
                            <Input error={errors.employee_code?.message} placeholder="EMP-0087" {...register('employee_code')} />
                        </FormField>
                        <FormField error={errors.job_title?.message} label="Job Title">
                            <Input error={errors.job_title?.message} placeholder="Senior Operations Executive" {...register('job_title')} />
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
                        <FormField error={errors.hire_date?.message} label="Hire Date">
                            <Input error={errors.hire_date?.message} type="date" {...register('hire_date')} />
                        </FormField>
                        <FormField error={errors.termination_date?.message} label="Termination Date">
                            <Input error={errors.termination_date?.message} type="date" {...register('termination_date')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard description="Employees are linked to user accounts for identity, authentication, and downstream access-control workflows." title="Linked user">
                    <FormGrid>
                        <FormField error={errors.portal_user_enabled?.message} label="Linked User">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Manage the linked user profile directly from the employee record."
                                label="Manage linked user"
                                {...register('portal_user_enabled')}
                            />
                        </FormField>
                        <FormField error={errors.user_active?.message} label="User Status">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Inactive linked users remain on file but cannot access protected routes."
                                label="Linked user is active"
                                {...register('user_active')}
                            />
                        </FormField>
                        <div className="hidden xl:block" />
                        <FormField error={errors.user_first_name?.message} label="First Name" required={portalUserEnabled}>
                            <Input error={errors.user_first_name?.message} placeholder="Kasun" {...register('user_first_name')} />
                        </FormField>
                        <FormField error={errors.user_last_name?.message} label="Last Name" required={portalUserEnabled}>
                            <Input error={errors.user_last_name?.message} placeholder="Silva" {...register('user_last_name')} />
                        </FormField>
                        <FormField error={errors.user_email?.message} label="Email" required={portalUserEnabled}>
                            <Input error={errors.user_email?.message} placeholder="employee@example.com" type="email" {...register('user_email')} />
                        </FormField>
                        <FormField error={errors.user_phone?.message} label="Phone">
                            <Input error={errors.user_phone?.message} placeholder="+94 76 111 2233" {...register('user_phone')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar leading={<p className="text-sm text-stone-500">User linkage and employment dates stay visible together so employee records are ready for later HR and access workflows.</p>}>
                    <Link to="/employees">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create Employee' : 'Save Changes'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
