import type { UseFormReturn } from 'react-hook-form';
import { Button } from '../../../components/ui/Button';
import { ActionBar } from '../../../components/forms/ActionBar';
import { Checkbox } from '../../../components/forms/Checkbox';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { Textarea } from '../../../components/forms/Textarea';
import type { ProductVariantFormInput, ProductVariantFormValues } from '../schemas';

type ProductVariantFormProps = {
    form: UseFormReturn<ProductVariantFormInput, unknown, ProductVariantFormValues>;
    onSubmit: (values: ProductVariantFormValues) => void | Promise<void>;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onCancel?: () => void;
};

export function ProductVariantForm({ form, isSubmitting, mode, onCancel, onSubmit }: ProductVariantFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-5">
                <FormGrid>
                    <FormField error={errors.name?.message} label="Variant Name" required>
                        <Input placeholder="Large / Blue / Pack of 12" {...register('name')} error={errors.name?.message} />
                    </FormField>

                    <FormField error={errors.sku?.message} label="Variant SKU">
                        <Input placeholder="SKU-1001-BLUE" {...register('sku')} error={errors.sku?.message} />
                    </FormField>

                    <FormField error={errors.attribute_summary?.message} label="Attribute Summary">
                        <Input placeholder="Color: Blue | Size: Large" {...register('attribute_summary')} error={errors.attribute_summary?.message} />
                    </FormField>

                    <FormField className="xl:col-span-3" error={errors.notes?.message} label="Notes">
                        <Textarea placeholder="Internal variant notes, handling guidance, or merchandising context." {...register('notes')} error={errors.notes?.message} rows={4} />
                    </FormField>

                    <FormField error={errors.is_default?.message} label="Default Variant">
                        <Checkbox
                            className="border-stone-200/80 bg-stone-50/70"
                            description="Mark this variant as the default option for the product."
                            label="Default variant"
                            {...register('is_default')}
                        />
                    </FormField>

                    <FormField error={errors.is_active?.message} label="Status">
                        <Checkbox
                            className="border-stone-200/80 bg-stone-50/70"
                            description="Inactive variants stay visible historically but are removed from new selection flows."
                            label="Variant is active"
                            {...register('is_active')}
                        />
                    </FormField>
                </FormGrid>

                <ActionBar>
                    {onCancel ? (
                        <Button onClick={onCancel} type="button" variant="secondary">
                            Cancel
                        </Button>
                    ) : null}
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Add Variant' : 'Save Variant'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
