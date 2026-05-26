import type { UseFormReturn } from 'react-hook-form';
import { Link } from 'react-router-dom';
import { ActionBar } from '../../../components/forms/ActionBar';
import { FormField } from '../../../components/forms/FormField';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Button } from '../../../components/ui/Button';
import type { PermissionRecord } from '../types';
import type { RoleFormInput, RoleFormValues } from '../schemas';

type RoleFormProps = {
    allowNameEdit: boolean;
    form: UseFormReturn<RoleFormInput, unknown, RoleFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onSubmit: (values: RoleFormValues) => void | Promise<void>;
    permissions: PermissionRecord[];
};

export function RoleForm({ allowNameEdit, form, formError = null, isSubmitting, mode, onSubmit, permissions }: RoleFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
        setValue,
        watch,
    } = form;

    const selectedPermissionIds = watch('permission_ids') ?? [];

    function togglePermission(permissionId: number, checked: boolean) {
        const nextPermissionIds = checked
            ? Array.from(new Set([...selectedPermissionIds, permissionId]))
            : selectedPermissionIds.filter((currentPermissionId) => currentPermissionId !== permissionId);

        setValue('permission_ids', nextPermissionIds, { shouldValidate: true });
    }

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Role records define reusable access bundles. The current backend contract supports role creation plus permission sync for maintenance." title="Role setup">
                    <FormField error={errors.name?.message} hint={!allowNameEdit ? 'Role renaming is not exposed by the current backend contract, so editing focuses on permission maintenance.' : undefined} label="Role Name" required>
                        <Input error={errors.name?.message} placeholder="Inventory Manager" readOnly={!allowNameEdit} {...register('name')} />
                    </FormField>
                </SectionCard>

                <SectionCard description="Permission summaries stay visible here so access updates can be managed without leaving the role editor." title="Permission assignment">
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {permissions.map((permission) => {
                            const checked = selectedPermissionIds.includes(permission.id);

                            return (
                                <label key={permission.id} className="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                    <div className="flex items-start gap-3">
                                        <input
                                            checked={checked}
                                            className="mt-1 h-4 w-4 rounded border-stone-300 text-stone-950 focus:ring-stone-300"
                                            onChange={(event) => togglePermission(permission.id, event.target.checked)}
                                            type="checkbox"
                                        />
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium text-stone-950">{permission.name}</p>
                                        </div>
                                    </div>
                                </label>
                            );
                        })}
                    </div>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar leading={<p className="text-sm text-stone-500">Role changes feed directly into user badges and permission summaries across Users & Access screens.</p>}>
                    <Link to="/users-access/roles">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create Role' : 'Save Role Permissions'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
