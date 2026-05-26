import type { UseFormReturn } from 'react-hook-form';
import { Link } from 'react-router-dom';
import { ActionBar } from '../../../components/forms/ActionBar';
import { Checkbox } from '../../../components/forms/Checkbox';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Textarea } from '../../../components/forms/Textarea';
import { Button } from '../../../components/ui/Button';
import type { ProductBrand, ProductCategory } from '../types';
import type { TaxonomyFormInput, TaxonomyFormValues } from '../schemas';

type TaxonomyEntity = ProductBrand | ProductCategory;

type TaxonomyFormProps = {
    form: UseFormReturn<TaxonomyFormInput, unknown, TaxonomyFormValues>;
    onSubmit: (values: TaxonomyFormValues) => void | Promise<void>;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    formError?: string | null;
    entityLabel: 'Brand' | 'Category';
    entityListPath: '/products/brands' | '/products/categories';
    parentOptions: TaxonomyEntity[];
    currentId?: number;
};

export function TaxonomyForm({
    currentId,
    entityLabel,
    entityListPath,
    form,
    formError = null,
    isSubmitting,
    mode,
    onSubmit,
    parentOptions,
}: TaxonomyFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    const availableParents = parentOptions.filter((option) => option.id !== currentId);

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard
                    description={`Use the shared catalog master form pattern so ${entityLabel.toLowerCase()} records feel identical across setup screens.`}
                    title={`${entityLabel} details`}
                >
                    <FormGrid>
                        <FormField error={errors.name?.message} label={`${entityLabel} Name`} required>
                            <Input placeholder={`Enter ${entityLabel.toLowerCase()} name`} {...register('name')} error={errors.name?.message} />
                        </FormField>

                        <FormField error={errors.code?.message} label="Code">
                            <Input placeholder={`${entityLabel.toUpperCase().slice(0, 3)}-001`} {...register('code')} error={errors.code?.message} />
                        </FormField>

                        <FormField error={errors.slug?.message} hint="If left blank, the slug is generated from the name." label="Slug">
                            <Input placeholder={`auto-generated-${entityLabel.toLowerCase()}-slug`} {...register('slug')} error={errors.slug?.message} />
                        </FormField>

                        <FormField error={errors.parent_id?.message} label="Parent">
                            <Select {...register('parent_id')} error={errors.parent_id?.message}>
                                <option value="">No parent</option>
                                {availableParents.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        {entityLabel === 'Brand' ? (
                            <FormField error={errors.website?.message} label="Website">
                                <Input placeholder="https://example.com" {...register('website')} error={errors.website?.message} />
                            </FormField>
                        ) : (
                            <div className="hidden xl:block" />
                        )}

                        <FormField error={errors.is_active?.message} label="Status">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description={`Keep this ${entityLabel.toLowerCase()} visible for new product assignments.`}
                                label={`${entityLabel} is active`}
                                {...register('is_active')}
                            />
                        </FormField>

                        <FormField className="xl:col-span-3" error={errors.description?.message} label="Description">
                            <Textarea
                                placeholder={`Add internal context for this ${entityLabel.toLowerCase()} record.`}
                                {...register('description')}
                                error={errors.description?.message}
                                rows={5}
                            />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar leading={<p className="text-sm text-stone-500">Hierarchy, activity status, and code assignment align with the backend validation rules.</p>}>
                    <Link to={entityListPath}>
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? `Create ${entityLabel}` : 'Save Changes'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
