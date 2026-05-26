import type { UseFormReturn } from 'react-hook-form';
import { ActionBar } from '../../../components/forms/ActionBar';
import { Checkbox } from '../../../components/forms/Checkbox';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Button } from '../../../components/ui/Button';
import type { WarehouseLocationFormInput, WarehouseLocationFormValues } from '../schemas';
import type { WarehouseLocationRecord } from '../types';

type WarehouseLocationFormProps = {
    form: UseFormReturn<WarehouseLocationFormInput, unknown, WarehouseLocationFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    locations: WarehouseLocationRecord[];
    mode: 'create' | 'edit';
    onCancel: () => void;
    onSubmit: (values: WarehouseLocationFormValues) => void | Promise<void>;
};

export function WarehouseLocationForm({ form, formError = null, isSubmitting, locations, mode, onCancel, onSubmit }: WarehouseLocationFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Maintain warehouse hierarchy with the same editor pattern already used for organization units." title={mode === 'create' ? 'Add Location' : 'Edit Location'}>
                    <FormGrid>
                        <FormField error={errors.name?.message} label="Location Name" required>
                            <Input error={errors.name?.message} placeholder="Bin A-01-02" {...register('name')} />
                        </FormField>
                        <FormField error={errors.code?.message} label="Location Code">
                            <Input error={errors.code?.message} placeholder="A0102" {...register('code')} />
                        </FormField>
                        <FormField error={errors.type?.message} label="Location Type" required>
                            <Select error={errors.type?.message} {...register('type')}>
                                <option value="zone">Zone</option>
                                <option value="aisle">Aisle</option>
                                <option value="rack">Rack</option>
                                <option value="shelf">Shelf</option>
                                <option value="bin">Bin</option>
                                <option value="staging">Staging</option>
                                <option value="dispatch">Dispatch</option>
                            </Select>
                        </FormField>
                        <FormField error={errors.parent_id?.message} label="Parent Location">
                            <Select error={errors.parent_id?.message} {...register('parent_id')}>
                                <option value="">No parent</option>
                                {locations.map((location) => (
                                    <option key={location.id} value={location.id}>
                                        {location.path ?? location.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.capacity?.message} label="Capacity">
                            <Input error={errors.capacity?.message} placeholder="250" {...register('capacity')} />
                        </FormField>
                        <div />
                        <FormField error={errors.is_active?.message} label="Status">
                            <Checkbox className="border-stone-200/80 bg-stone-50/70" description="Inactive locations stay visible for traceability but should not receive new transactions." label="Location is active" {...register('is_active')} />
                        </FormField>
                        <FormField error={errors.is_pickable?.message} label="Picking">
                            <Checkbox className="border-stone-200/80 bg-stone-50/70" description="Allow picking from this location during outbound workflows." label="Pickable location" {...register('is_pickable')} />
                        </FormField>
                        <FormField error={errors.is_receivable?.message} label="Receiving">
                            <Checkbox className="border-stone-200/80 bg-stone-50/70" description="Allow receipts into this location during inbound workflows." label="Receivable location" {...register('is_receivable')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar>
                    <Button onClick={onCancel} type="button" variant="secondary">
                        Cancel
                    </Button>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create Location' : 'Save Location'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
