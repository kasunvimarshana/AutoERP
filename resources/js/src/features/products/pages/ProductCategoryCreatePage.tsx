import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ValidationError } from '../../../api/client';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { TaxonomyForm } from '../components/TaxonomyForm';
import { useCreateProductCategory, useProductCategories } from '../hooks';
import { taxonomyFormSchema, type TaxonomyFormInput, type TaxonomyFormValues } from '../schemas';
import { slugify } from '../utils';

export function ProductCategoryCreatePage() {
    const navigate = useNavigate();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<TaxonomyFormInput, unknown, TaxonomyFormValues>({
        resolver: zodResolver(taxonomyFormSchema),
        defaultValues: {
            name: '',
            slug: '',
            code: '',
            parent_id: '',
            website: '',
            description: '',
            is_active: true,
        },
    });
    const categoriesQuery = useProductCategories({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const createMutation = useCreateProductCategory();

    async function onSubmit(values: TaxonomyFormValues) {
        setFormError(null);

        try {
            await createMutation.mutateAsync({
                tenant_id: tenantId,
                name: values.name,
                slug: values.slug ?? slugify(values.name),
                code: values.code ?? null,
                parent_id: values.parent_id ?? null,
                description: values.description ?? null,
                is_active: values.is_active,
            });

            navigate('/products/categories');
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create category.');
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[
                    { label: 'Products', href: '/products' },
                    { label: 'Categories', href: '/products/categories' },
                    { label: 'Add Category' },
                ]}
                description="Add a category using the same shared master-data layout and backend-confirmed fields."
                title="Add Category"
            />

            <ContentCard>
                {categoriesQuery.isPending ? (
                    <LoadingState lines={7} />
                ) : categoriesQuery.isError ? (
                    <ErrorState
                        action={
                            <Button onClick={() => void categoriesQuery.refetch()} variant="secondary">
                                Retry
                            </Button>
                        }
                        description={categoriesQuery.error.message}
                        title="Unable to load category hierarchy"
                    />
                ) : (
                    <TaxonomyForm
                        entityLabel="Category"
                        entityListPath="/products/categories"
                        form={form}
                        formError={formError}
                        isSubmitting={createMutation.isPending}
                        mode="create"
                        onSubmit={onSubmit}
                        parentOptions={categoriesQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
