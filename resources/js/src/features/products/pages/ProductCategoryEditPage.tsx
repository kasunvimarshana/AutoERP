import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate, useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ValidationError } from '../../../api/client';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { TaxonomyForm } from '../components/TaxonomyForm';
import { useProductCategories, useProductCategory, useUpdateProductCategory } from '../hooks';
import { taxonomyFormSchema, type TaxonomyFormInput, type TaxonomyFormValues } from '../schemas';
import { parsePositiveInteger, slugify } from '../utils';

export function ProductCategoryEditPage() {
    const navigate = useNavigate();
    const { tenantId } = useTenant();
    const { categoryId: categoryIdParam } = useParams();
    const categoryId = parsePositiveInteger(categoryIdParam ?? null, 0);
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

    const categoryQuery = useProductCategory(categoryId, categoryId > 0);
    const categoriesQuery = useProductCategories({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const updateMutation = useUpdateProductCategory(categoryId);

    useEffect(() => {
        if (!categoryQuery.data) {
            return;
        }

        form.reset({
            name: categoryQuery.data.name,
            slug: categoryQuery.data.slug ?? '',
            code: categoryQuery.data.code ?? '',
            parent_id: categoryQuery.data.parent_id ?? '',
            website: '',
            description: categoryQuery.data.description ?? '',
            is_active: categoryQuery.data.is_active,
        });
    }, [categoryQuery.data, form]);

    async function onSubmit(values: TaxonomyFormValues) {
        setFormError(null);

        try {
            await updateMutation.mutateAsync({
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

            setFormError(error instanceof Error ? error.message : 'Unable to update category.');
        }
    }

    if (categoryId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The category route is missing a valid category ID." title="Invalid category route" />
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[
                    { label: 'Products', href: '/products' },
                    { label: 'Categories', href: '/products/categories' },
                    { label: categoryQuery.data?.name ?? 'Category' },
                ]}
                description="Edit category hierarchy, naming, and activity using the same shared editor pattern."
                title={categoryQuery.data ? `Edit ${categoryQuery.data.name}` : 'Edit Category'}
            />

            <ContentCard>
                {categoryQuery.isPending || categoriesQuery.isPending ? (
                    <LoadingState lines={7} />
                ) : categoryQuery.isError || categoriesQuery.isError ? (
                    <ErrorState
                        action={
                            <Button onClick={() => {
                                void categoryQuery.refetch();
                                void categoriesQuery.refetch();
                            }} variant="secondary">
                                Retry
                            </Button>
                        }
                        description={(categoryQuery.error ?? categoriesQuery.error)?.message ?? 'Unable to load the category editor.'}
                        title="Unable to load category editor"
                    />
                ) : (
                    <TaxonomyForm
                        currentId={categoryId}
                        entityLabel="Category"
                        entityListPath="/products/categories"
                        form={form}
                        formError={formError}
                        isSubmitting={updateMutation.isPending}
                        mode="edit"
                        onSubmit={onSubmit}
                        parentOptions={categoriesQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
