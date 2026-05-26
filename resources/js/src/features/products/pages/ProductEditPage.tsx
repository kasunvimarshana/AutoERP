import { useEffect, useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate, useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { ProductForm } from '../components/ProductForm';
import {
    useCreateProductIdentifier,
    useProduct,
    useProductBrands,
    useProductCategories,
    useProductIdentifiers,
    useUnitsOfMeasure,
    useUpdateProduct,
    useUpdateProductIdentifier,
} from '../hooks';
import { productFormSchema, type ProductFormInput, type ProductFormValues } from '../schemas';
import { parsePositiveInteger, slugify } from '../utils';

export function ProductEditPage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const { productId: productIdParam } = useParams();
    const productId = parsePositiveInteger(productIdParam ?? null, 0);
    const [formError, setFormError] = useState<string | null>(null);

    const form = useForm<ProductFormInput, unknown, ProductFormValues>({
        resolver: zodResolver(productFormSchema),
        defaultValues: {
            type: 'physical',
            name: '',
            slug: '',
            sku: '',
            description: '',
            category_id: '',
            brand_id: '',
            base_uom_id: '',
            purchase_uom_id: '',
            sales_uom_id: '',
            uom_conversion_factor: '',
            valuation_method: 'fifo',
            standard_cost: '',
            purchase_price: '',
            sales_price: '',
            profit_margin: '',
            price_list_note: '',
            supplier_reference: '',
            identifier_technology: 'barcode_1d',
            identifier_format: undefined,
            identifier_value: '',
            identifier_gs1_company_prefix: '',
            identifier_is_primary: true,
            identifier_is_active: true,
            is_batch_tracked: false,
            is_lot_tracked: false,
            is_serial_tracked: false,
            is_active: true,
        },
    });

    const productQuery = useProduct(productId, productId > 0);
    const brandsQuery = useProductBrands({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const categoriesQuery = useProductCategories({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const unitsQuery = useUnitsOfMeasure({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const identifiersQuery = useProductIdentifiers({ tenant_id: tenantId, product_id: productId, per_page: 50, sort: '-updated_at' }, productId > 0);
    const updateMutation = useUpdateProduct(productId);
    const createIdentifierMutation = useCreateProductIdentifier();
    const primaryIdentifier = useMemo(
        () => identifiersQuery.data?.items.find((identifier) => identifier.is_primary) ?? identifiersQuery.data?.items[0] ?? null,
        [identifiersQuery.data?.items],
    );
    const updateIdentifierMutation = useUpdateProductIdentifier(primaryIdentifier?.id ?? 0);

    useEffect(() => {
        if (!productQuery.data) {
            return;
        }

        form.reset({
            type: productQuery.data.type,
            name: productQuery.data.name,
            slug: productQuery.data.slug ?? '',
            sku: productQuery.data.sku ?? '',
            description: productQuery.data.description ?? '',
            category_id: productQuery.data.category_id ?? '',
            brand_id: productQuery.data.brand_id ?? '',
            base_uom_id: productQuery.data.base_uom_id ?? '',
            purchase_uom_id: productQuery.data.purchase_uom_id ?? '',
            sales_uom_id: productQuery.data.sales_uom_id ?? '',
            uom_conversion_factor: productQuery.data.uom_conversion_factor ? String(productQuery.data.uom_conversion_factor) : '',
            valuation_method: productQuery.data.valuation_method ?? 'fifo',
            standard_cost: productQuery.data.standard_cost ? String(productQuery.data.standard_cost) : '',
            purchase_price: productQuery.data.metadata?.purchase_price ? String(productQuery.data.metadata.purchase_price) : '',
            sales_price: productQuery.data.metadata?.sales_price ? String(productQuery.data.metadata.sales_price) : '',
            profit_margin: productQuery.data.metadata?.profit_margin ? String(productQuery.data.metadata.profit_margin) : '',
            price_list_note: typeof productQuery.data.metadata?.price_list_note === 'string' ? productQuery.data.metadata.price_list_note : '',
            supplier_reference: typeof productQuery.data.metadata?.supplier_reference === 'string' ? productQuery.data.metadata.supplier_reference : '',
            identifier_technology: primaryIdentifier?.technology ?? 'barcode_1d',
            identifier_format: primaryIdentifier?.format ?? undefined,
            identifier_value: primaryIdentifier?.value ?? '',
            identifier_gs1_company_prefix: primaryIdentifier?.gs1_company_prefix ?? '',
            identifier_is_primary: primaryIdentifier?.is_primary ?? true,
            identifier_is_active: primaryIdentifier?.is_active ?? true,
            is_batch_tracked: productQuery.data.is_batch_tracked,
            is_lot_tracked: productQuery.data.is_lot_tracked,
            is_serial_tracked: productQuery.data.is_serial_tracked,
            is_active: productQuery.data.is_active,
        });
    }, [form, primaryIdentifier, productQuery.data]);

    async function onSubmit(values: ProductFormValues) {
        setFormError(null);

        try {
            const product = await updateMutation.mutateAsync({
                tenant_id: tenantId,
                type: values.type,
                name: values.name,
                slug: values.slug ?? slugify(values.name),
                sku: values.sku ?? null,
                description: values.description ?? null,
                category_id: values.category_id ?? null,
                brand_id: values.brand_id ?? null,
                base_uom_id: values.base_uom_id,
                purchase_uom_id: values.purchase_uom_id ?? null,
                sales_uom_id: values.sales_uom_id ?? null,
                uom_conversion_factor: values.uom_conversion_factor ?? null,
                valuation_method: values.valuation_method ?? null,
                standard_cost: values.standard_cost ?? null,
                is_batch_tracked: values.is_batch_tracked,
                is_lot_tracked: values.is_lot_tracked,
                is_serial_tracked: values.is_serial_tracked,
                is_active: values.is_active,
                metadata: {
                    purchase_price: values.purchase_price ?? null,
                    sales_price: values.sales_price ?? null,
                    profit_margin: values.profit_margin ?? null,
                    price_list_note: values.price_list_note ?? null,
                    supplier_reference: values.supplier_reference ?? null,
                },
            });

            if (values.identifier_value && values.identifier_technology) {
                const identifierPayload = {
                    tenant_id: tenantId,
                    product_id: product.id,
                    technology: values.identifier_technology,
                    format: values.identifier_format ?? null,
                    value: values.identifier_value,
                    gs1_company_prefix: values.identifier_gs1_company_prefix ?? null,
                    is_primary: values.identifier_is_primary,
                    is_active: values.identifier_is_active,
                };

                if (primaryIdentifier) {
                    await updateIdentifierMutation.mutateAsync(identifierPayload);
                } else {
                    await createIdentifierMutation.mutateAsync(identifierPayload);
                }
            }

            showToast({
                title: 'Product updated',
                description: `${product.name} now includes the latest catalog, pricing, and identifier setup.`,
                tone: 'success',
            });
            navigate(`/products/${product.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to update product.');
        }
    }

    if (productId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The product route is missing a valid product ID." title="Invalid product route" />
            </div>
        );
    }

    const lookupError = productQuery.error ?? brandsQuery.error ?? categoriesQuery.error ?? unitsQuery.error ?? identifiersQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[
                    { label: 'Products', href: '/products' },
                    { label: productQuery.data?.name ?? 'Product', href: productQuery.data ? `/products/${productQuery.data.id}` : undefined },
                    { label: 'Edit Product' },
                ]}
                description="Edit the full Product workspace fields, including metadata-backed pricing and the primary identifier."
                title={productQuery.data ? `Edit ${productQuery.data.name}` : 'Edit Product'}
            />

            <ContentCard>
                {productQuery.isPending || brandsQuery.isPending || categoriesQuery.isPending || unitsQuery.isPending || identifiersQuery.isPending ? (
                    <LoadingState lines={10} />
                ) : lookupError ? (
                    <ErrorState
                        action={
                            <Button
                                onClick={() => {
                                    void productQuery.refetch();
                                    void brandsQuery.refetch();
                                    void categoriesQuery.refetch();
                                    void unitsQuery.refetch();
                                    void identifiersQuery.refetch();
                                }}
                                variant="secondary"
                            >
                                Retry
                            </Button>
                        }
                        description={lookupError.message}
                        title="Unable to load the product editor"
                    />
                ) : (
                    <ProductForm
                        brands={brandsQuery.data?.items ?? []}
                        categories={categoriesQuery.data?.items ?? []}
                        form={form}
                        formError={formError}
                        isSubmitting={updateMutation.isPending || createIdentifierMutation.isPending || updateIdentifierMutation.isPending}
                        mode="edit"
                        onSubmit={onSubmit}
                        unitsOfMeasure={unitsQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
