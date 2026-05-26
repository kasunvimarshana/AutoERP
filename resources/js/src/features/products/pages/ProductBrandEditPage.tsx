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
import { useProductBrand, useProductBrands, useUpdateProductBrand } from '../hooks';
import { taxonomyFormSchema, type TaxonomyFormInput, type TaxonomyFormValues } from '../schemas';
import { parsePositiveInteger, slugify } from '../utils';

export function ProductBrandEditPage() {
    const navigate = useNavigate();
    const { tenantId } = useTenant();
    const { brandId: brandIdParam } = useParams();
    const brandId = parsePositiveInteger(brandIdParam ?? null, 0);
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

    const brandQuery = useProductBrand(brandId, brandId > 0);
    const brandsQuery = useProductBrands({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const updateMutation = useUpdateProductBrand(brandId);

    useEffect(() => {
        if (!brandQuery.data) {
            return;
        }

        form.reset({
            name: brandQuery.data.name,
            slug: brandQuery.data.slug ?? '',
            code: brandQuery.data.code ?? '',
            parent_id: brandQuery.data.parent_id ?? '',
            website: brandQuery.data.website ?? '',
            description: brandQuery.data.description ?? '',
            is_active: brandQuery.data.is_active,
        });
    }, [brandQuery.data, form]);

    async function onSubmit(values: TaxonomyFormValues) {
        setFormError(null);

        try {
            await updateMutation.mutateAsync({
                tenant_id: tenantId,
                name: values.name,
                slug: values.slug ?? slugify(values.name),
                code: values.code ?? null,
                parent_id: values.parent_id ?? null,
                website: values.website ?? null,
                description: values.description ?? null,
                is_active: values.is_active,
            });

            navigate('/products/brands');
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to update brand.');
        }
    }

    if (brandId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The brand route is missing a valid brand ID." title="Invalid brand route" />
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[
                    { label: 'Products', href: '/products' },
                    { label: 'Brands', href: '/products/brands' },
                    { label: brandQuery.data?.name ?? 'Brand' },
                ]}
                description="Edit brand naming, hierarchy, and status using the same shared master-data editor."
                title={brandQuery.data ? `Edit ${brandQuery.data.name}` : 'Edit Brand'}
            />

            <ContentCard>
                {brandQuery.isPending || brandsQuery.isPending ? (
                    <LoadingState lines={7} />
                ) : brandQuery.isError || brandsQuery.isError ? (
                    <ErrorState
                        action={
                            <Button onClick={() => {
                                void brandQuery.refetch();
                                void brandsQuery.refetch();
                            }} variant="secondary">
                                Retry
                            </Button>
                        }
                        description={(brandQuery.error ?? brandsQuery.error)?.message ?? 'Unable to load the brand editor.'}
                        title="Unable to load brand editor"
                    />
                ) : (
                    <TaxonomyForm
                        currentId={brandId}
                        entityLabel="Brand"
                        entityListPath="/products/brands"
                        form={form}
                        formError={formError}
                        isSubmitting={updateMutation.isPending}
                        mode="edit"
                        onSubmit={onSubmit}
                        parentOptions={brandsQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
