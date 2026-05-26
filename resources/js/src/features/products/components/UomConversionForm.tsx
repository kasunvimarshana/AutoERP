import type { UseFormReturn } from 'react-hook-form';
import { Button } from '../../../components/ui/Button';
import { ActionBar } from '../../../components/forms/ActionBar';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import type { UnitOfMeasure } from '../types';
import type { UomConversionFormInput, UomConversionFormValues } from '../schemas';

type UomConversionFormProps = {
    form: UseFormReturn<UomConversionFormInput, unknown, UomConversionFormValues>;
    units: UnitOfMeasure[];
    onSubmit: (values: UomConversionFormValues) => void | Promise<void>;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onCancel?: () => void;
};

export function UomConversionForm({ form, isSubmitting, mode, onCancel, onSubmit, units }: UomConversionFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-5">
                <FormGrid>
                    <FormField error={errors.from_uom_id?.message} label="From UOM" required>
                        <Select {...register('from_uom_id')} error={errors.from_uom_id?.message}>
                            <option value="">Select unit</option>
                            {units.map((unit) => (
                                <option key={unit.id} value={unit.id}>
                                    {unit.name} ({unit.symbol})
                                </option>
                            ))}
                        </Select>
                    </FormField>

                    <FormField error={errors.to_uom_id?.message} label="To UOM" required>
                        <Select {...register('to_uom_id')} error={errors.to_uom_id?.message}>
                            <option value="">Select unit</option>
                            {units.map((unit) => (
                                <option key={unit.id} value={unit.id}>
                                    {unit.name} ({unit.symbol})
                                </option>
                            ))}
                        </Select>
                    </FormField>

                    <FormField error={errors.factor?.message} hint="Example: 1 box = 12 each, so the factor is 12." label="Conversion Factor" required>
                        <Input placeholder="12" step="0.0001" type="number" {...register('factor')} error={errors.factor?.message} />
                    </FormField>
                </FormGrid>

                <ActionBar>
                    {onCancel ? (
                        <Button onClick={onCancel} type="button" variant="secondary">
                            Cancel
                        </Button>
                    ) : null}
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Add Conversion' : 'Save Conversion'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
