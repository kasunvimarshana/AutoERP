import type { UseFormReturn } from 'react-hook-form';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Textarea } from '../../../components/forms/Textarea';
import type { UserRecord } from '../../access/types';
import type { OrganizationUnitRecord, OrganizationUnitTypeRecord } from '../types';
import type { OrganizationUnitFormInput, OrganizationUnitFormValues } from '../schemas';

type OrganizationUnitFormProps = {
    form: UseFormReturn<OrganizationUnitFormInput, unknown, OrganizationUnitFormValues>;
    managerUsers: UserRecord[];
    organizationUnits: OrganizationUnitRecord[];
    organizationUnitTypes: OrganizationUnitTypeRecord[];
};

export function OrganizationUnitForm({ form, managerUsers, organizationUnits, organizationUnitTypes }: OrganizationUnitFormProps) {
    const {
        formState: { errors },
        register,
    } = form;

    return (
        <SectionCard description="Organization units support hierarchy, manager ownership, and descriptive metadata, so the editor keeps those controls together in one consistent panel." title="Organization unit editor">
            <FormGrid>
                <FormField error={errors.name?.message} label="Unit Name" required>
                    <Input error={errors.name?.message} placeholder="Western Region" {...register('name')} />
                </FormField>
                <FormField error={errors.code?.message} label="Code">
                    <Input error={errors.code?.message} placeholder="WEST-REG" {...register('code')} />
                </FormField>
                <FormField error={errors.type_id?.message} label="Unit Type">
                    <Select error={errors.type_id?.message} {...register('type_id')}>
                        <option value="">Select type</option>
                        {organizationUnitTypes.map((organizationUnitType) => (
                            <option key={organizationUnitType.id} value={organizationUnitType.id}>
                                {organizationUnitType.name}
                            </option>
                        ))}
                    </Select>
                </FormField>
                <FormField error={errors.parent_id?.message} label="Parent Unit">
                    <Select error={errors.parent_id?.message} {...register('parent_id')}>
                        <option value="">No parent</option>
                        {organizationUnits.map((organizationUnit) => (
                            <option key={organizationUnit.id} value={organizationUnit.id}>
                                {organizationUnit.name}
                            </option>
                        ))}
                    </Select>
                </FormField>
                <FormField error={errors.manager_user_id?.message} label="Manager User">
                    <Select error={errors.manager_user_id?.message} {...register('manager_user_id')}>
                        <option value="">Select manager</option>
                        {managerUsers.map((user) => (
                            <option key={user.id} value={user.id}>
                                {user.full_name ?? user.email ?? `User #${user.id}`}
                            </option>
                        ))}
                    </Select>
                </FormField>
                <FormField error={errors.is_active?.message} label="Status">
                    <Select error={errors.is_active?.message} {...register('is_active')}>
                        <option value="true">Active</option>
                        <option value="false">Inactive</option>
                    </Select>
                </FormField>
                <FormField className="xl:col-span-3" error={errors.description?.message} label="Description">
                    <Textarea error={errors.description?.message} placeholder="Operational purpose, coverage, or ownership notes." {...register('description')} />
                </FormField>
            </FormGrid>
        </SectionCard>
    );
}
