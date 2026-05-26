import type { UseFormReturn } from 'react-hook-form';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import type { OrganizationUnitTypeFormInput, OrganizationUnitTypeFormValues } from '../schemas';

type OrganizationUnitTypeFormProps = {
    form: UseFormReturn<OrganizationUnitTypeFormInput, unknown, OrganizationUnitTypeFormValues>;
};

export function OrganizationUnitTypeForm({ form }: OrganizationUnitTypeFormProps) {
    const {
        formState: { errors },
        register,
    } = form;

    return (
        <SectionCard description="Organization unit types control taxonomy and depth progression for the hierarchy. The editor mirrors the same shared admin form language used elsewhere in Phase 3." title="Unit type editor">
            <FormGrid>
                <FormField error={errors.name?.message} label="Type Name" required>
                    <Input error={errors.name?.message} placeholder="Region" {...register('name')} />
                </FormField>
                <FormField error={errors.level?.message} label="Level" required>
                    <Input error={errors.level?.message} min={0} placeholder="1" type="number" {...register('level')} />
                </FormField>
                <FormField error={errors.is_active?.message} label="Status">
                    <Select error={errors.is_active?.message} {...register('is_active')}>
                        <option value="true">Active</option>
                        <option value="false">Inactive</option>
                    </Select>
                </FormField>
            </FormGrid>
        </SectionCard>
    );
}
