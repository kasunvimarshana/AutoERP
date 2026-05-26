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
import type { RoleRecord } from '../types';
import type { UserFormInput, UserFormValues } from '../schemas';

type UserFormProps = {
    form: UseFormReturn<UserFormInput, unknown, UserFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onSubmit: (values: UserFormValues) => void | Promise<void>;
    organizationUnits: OrganizationUnitRecord[];
    roles: RoleRecord[];
};

export function UserForm({ form, formError = null, isSubmitting, mode, onSubmit, organizationUnits, roles }: UserFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
        watch,
        setValue,
    } = form;

    const selectedRoles = watch('roles') ?? [];

    function toggleRole(roleId: number, checked: boolean) {
        const nextRoles = checked ? Array.from(new Set([...selectedRoles, roleId])) : selectedRoles.filter((currentRoleId) => currentRoleId !== roleId);
        setValue('roles', nextRoles, { shouldValidate: true });
    }

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Maintain core user identity, routing ownership, and account status using the same large-card form pattern established across the ERP shell." title="User profile">
                    <FormGrid>
                        <FormField error={errors.first_name?.message} label="First Name" required>
                            <Input error={errors.first_name?.message} placeholder="Sanjaya" {...register('first_name')} />
                        </FormField>
                        <FormField error={errors.last_name?.message} label="Last Name" required>
                            <Input error={errors.last_name?.message} placeholder="Dias" {...register('last_name')} />
                        </FormField>
                        <FormField error={errors.email?.message} label="Email" required>
                            <Input error={errors.email?.message} placeholder="sanjaya@example.com" type="email" {...register('email')} />
                        </FormField>
                        <FormField error={errors.phone?.message} label="Phone">
                            <Input error={errors.phone?.message} placeholder="+94 77 987 6543" {...register('phone')} />
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
                        <FormField error={errors.active?.message} label="Status">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Inactive users stay on file but cannot access protected ERP routes."
                                label="User is active"
                                {...register('active')}
                            />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard description="Address data is stored as a structured payload on the user profile and is ready for reuse in future modules." title="Address">
                    <FormGrid>
                        <FormField error={errors.address_line1?.message} label="Address Line 1">
                            <Input error={errors.address_line1?.message} placeholder="No. 52, Flower Road" {...register('address_line1')} />
                        </FormField>
                        <FormField error={errors.address_line2?.message} label="Address Line 2">
                            <Input error={errors.address_line2?.message} placeholder="Apartment 7B" {...register('address_line2')} />
                        </FormField>
                        <FormField error={errors.city?.message} label="City">
                            <Input error={errors.city?.message} placeholder="Colombo" {...register('city')} />
                        </FormField>
                        <FormField error={errors.state?.message} label="State / Province">
                            <Input error={errors.state?.message} placeholder="Western" {...register('state')} />
                        </FormField>
                        <FormField error={errors.postal_code?.message} label="Postal Code">
                            <Input error={errors.postal_code?.message} placeholder="00700" {...register('postal_code')} />
                        </FormField>
                        <FormField error={errors.country?.message} label="Country">
                            <Input error={errors.country?.message} placeholder="Sri Lanka" {...register('country')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard description="Role assignments drive badges, permission summaries, and access checks throughout the shell." title="Roles">
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {roles.map((role) => {
                            const checked = selectedRoles.includes(role.id);

                            return (
                                <label key={role.id} className="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                    <div className="flex items-start gap-3">
                                        <input
                                            checked={checked}
                                            className="mt-1 h-4 w-4 rounded border-stone-300 text-stone-950 focus:ring-stone-300"
                                            onChange={(event) => toggleRole(role.id, event.target.checked)}
                                            type="checkbox"
                                        />
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium text-stone-950">{role.name}</p>
                                            <p className="mt-1 text-xs leading-5 text-stone-500">{role.permissions.length} permissions currently linked.</p>
                                        </div>
                                    </div>
                                </label>
                            );
                        })}
                    </div>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar leading={<p className="text-sm text-stone-500">User detail and permission summaries are available immediately after save.</p>}>
                    <Link to="/users-access/users">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create User' : 'Save Changes'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
