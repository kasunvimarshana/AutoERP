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
import type { WarehouseFormInput, WarehouseFormValues } from '../schemas';

type WarehouseFormProps = {
    form: UseFormReturn<WarehouseFormInput, unknown, WarehouseFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onSubmit: (values: WarehouseFormValues) => void | Promise<void>;
    organizationUnits: OrganizationUnitRecord[];
};

export function WarehouseForm({ form, formError = null, isSubmitting, mode, onSubmit, organizationUnits }: WarehouseFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Warehouses follow the same large-card master-data layout as Product and the Phase 3 administration modules." title="Warehouse setup">
                    <FormGrid>
                        <FormField error={errors.name?.message} label="Warehouse Name" required>
                            <Input error={errors.name?.message} placeholder="Central Distribution Center" {...register('name')} />
                        </FormField>
                        <FormField error={errors.code?.message} label="Warehouse Code">
                            <Input error={errors.code?.message} placeholder="WH-CEN" {...register('code')} />
                        </FormField>
                        <FormField error={errors.type?.message} label="Warehouse Type" required>
                            <Select error={errors.type?.message} {...register('type')}>
                                <option value="standard">Standard</option>
                                <option value="virtual">Virtual</option>
                                <option value="transit">Transit</option>
                                <option value="quarantine">Quarantine</option>
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
                        <FormField error={errors.address_id?.message} label="Address ID">
                            <Input error={errors.address_id?.message} placeholder="102" {...register('address_id')} />
                        </FormField>
                        <FormField error={errors.image_path?.message} label="Image Path">
                            <Input error={errors.image_path?.message} placeholder="/images/warehouses/central.png" {...register('image_path')} />
                        </FormField>
                        <FormField error={errors.is_active?.message} label="Status">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Inactive warehouses stay visible for reporting but should not be used for operational transactions."
                                label="Warehouse is active"
                                {...register('is_active')}
                            />
                        </FormField>
                        <FormField error={errors.is_default?.message} label="Default">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Mark this warehouse as the default operational warehouse for quick workflow selection."
                                label="Default warehouse"
                                {...register('is_default')}
                            />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar leading={<p className="text-sm text-stone-500">Warehouse detail screens expose locations, stock levels, and stock movements immediately after save.</p>}>
                    <Link to="/warehouses">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create Warehouse' : 'Save Changes'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
