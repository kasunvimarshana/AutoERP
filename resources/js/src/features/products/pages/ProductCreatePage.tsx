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
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { ProductForm } from '../components/ProductForm';
import { useCreateProduct, useCreateProductIdentifier, useProductBrands, useProductCategories, useUnitsOfMeasure } from '../hooks';
import { productFormSchema, type ProductFormInput, type ProductFormValues } from '../schemas';
import { slugify } from '../utils';

export function ProductCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
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
            uom_conversion_factor: '1',
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

    const brandsQuery = useProductBrands({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const categoriesQuery = useProductCategories({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const unitsQuery = useUnitsOfMeasure({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const createMutation = useCreateProduct();
    const createIdentifierMutation = useCreateProductIdentifier();

    async function onSubmit(values: ProductFormValues) {
        setFormError(null);

        try {
            const product = await createMutation.mutateAsync({
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
                await createIdentifierMutation.mutateAsync({
                    tenant_id: tenantId,
                    product_id: product.id,
                    technology: values.identifier_technology,
                    format: values.identifier_format ?? null,
                    value: values.identifier_value,
                    gs1_company_prefix: values.identifier_gs1_company_prefix ?? null,
                    is_primary: values.identifier_is_primary,
                    is_active: values.identifier_is_active,
                });
            }

            showToast({
                title: 'Product created',
                description: `${product.name} is ready for variants, identifiers, and stock setup.`,
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

            setFormError(error instanceof Error ? error.message : 'Unable to create product.');
        }
    }

    const lookupError = brandsQuery.error ?? categoriesQuery.error ?? unitsQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Products', href: '/products' }, { label: 'Add Product' }]}
                description="The complete product form now includes commercial metadata, primary identifier setup, and inventory-ready tracking controls."
                title="Add Product"
            />

            <ContentCard>
                {brandsQuery.isPending || categoriesQuery.isPending || unitsQuery.isPending ? (
                    <LoadingState lines={10} />
                ) : lookupError ? (
                    <ErrorState
                        action={
                            <Button
                                onClick={() => {
                                    void brandsQuery.refetch();
                                    void categoriesQuery.refetch();
                                    void unitsQuery.refetch();
                                }}
                                variant="secondary"
                            >
                                Retry lookups
                            </Button>
                        }
                        description={lookupError.message}
                        title="Unable to load product setup lookups"
                    />
                ) : (
                    <ProductForm
                        brands={brandsQuery.data?.items ?? []}
                        categories={categoriesQuery.data?.items ?? []}
                        form={form}
                        formError={formError}
                        isSubmitting={createMutation.isPending || createIdentifierMutation.isPending}
                        mode="create"
                        onSubmit={onSubmit}
                        unitsOfMeasure={unitsQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
