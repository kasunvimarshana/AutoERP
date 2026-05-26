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
import type { UnitOfMeasureFormInput, UnitOfMeasureFormValues } from '../schemas';

type UnitOfMeasureFormProps = {
    form: UseFormReturn<UnitOfMeasureFormInput, unknown, UnitOfMeasureFormValues>;
    onSubmit: (values: UnitOfMeasureFormValues) => void | Promise<void>;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    formError?: string | null;
};

export function UnitOfMeasureForm({ form, formError = null, isSubmitting, mode, onSubmit }: UnitOfMeasureFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Keep units of measure consistent across product setup, inventory, purchase, and sales flows." title="Unit details">
                    <FormGrid>
                        <FormField error={errors.name?.message} label="Unit Name" required>
                            <Input placeholder="Each" {...register('name')} error={errors.name?.message} />
                        </FormField>

                        <FormField error={errors.symbol?.message} label="Symbol" required>
                            <Input placeholder="EA" {...register('symbol')} error={errors.symbol?.message} />
                        </FormField>

                        <FormField error={errors.type?.message} label="Type" required>
                            <Select {...register('type')} error={errors.type?.message}>
                                <option value="unit">Unit</option>
                                <option value="mass">Mass</option>
                                <option value="volume">Volume</option>
                                <option value="length">Length</option>
                                <option value="time">Time</option>
                                <option value="other">Other</option>
                            </Select>
                        </FormField>

                        <FormField className="xl:col-span-3" error={errors.is_base?.message} label="Base Unit">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Mark this as the base unit when other conversions point back to it."
                                label="This is a base unit"
                                {...register('is_base')}
                            />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar leading={<p className="text-sm text-stone-500">UOM conversion maintenance is already staged for the next phase through the backend endpoints.</p>}>
                    <Link to="/products/units">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create UOM' : 'Save Changes'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
