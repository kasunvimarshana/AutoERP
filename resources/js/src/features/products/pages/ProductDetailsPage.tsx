import { useEffect, useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { DataTable, StatusBadge, type DataTableColumn } from '../../../components/tables';
import { SectionCard } from '../../../components/forms/SectionCard';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { ProductIdentifierForm } from '../components/ProductIdentifierForm';
import { ProductVariantForm } from '../components/ProductVariantForm';
import {
    useCreateProductIdentifier,
    useCreateProductVariant,
    useDeleteProductIdentifier,
    useDeleteProductVariant,
    useProduct,
    useProductBrands,
    useProductCategories,
    useProductIdentifiers,
    useProductVariants,
    useUnitsOfMeasure,
    useUpdateProductIdentifier,
    useUpdateProductVariant,
} from '../hooks';
import {
    productIdentifierFormSchema,
    productVariantFormSchema,
    type ProductIdentifierFormInput,
    type ProductIdentifierFormValues,
    type ProductVariantFormInput,
    type ProductVariantFormValues,
} from '../schemas';
import type { ProductIdentifier, ProductVariant, UnitOfMeasure } from '../types';
import { formatDate, parsePositiveInteger } from '../utils';

const detailTabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'variants', label: 'Variants' },
    { id: 'identifiers', label: 'Identifiers' },
    { id: 'stock', label: 'Stock' },
    { id: 'price-lists', label: 'Price Lists' },
    { id: 'supplier-products', label: 'Supplier Products' },
] as const;

type DetailTabId = (typeof detailTabs)[number]['id'];
type DeleteState =
    | { type: 'variant'; target: ProductVariant }
    | { type: 'identifier'; target: ProductIdentifier }
    | null;

function findUnitName(units: UnitOfMeasure[], unitId: number | null) {
    if (!unitId) {
        return '-';
    }

    const unit = units.find((item) => item.id === unitId);
    return unit ? `${unit.name} (${unit.symbol})` : `#${unitId}`;
}

export function ProductDetailsPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const { productId: productIdParam } = useParams();
    const productId = parsePositiveInteger(productIdParam ?? null, 0);
    const [searchParams, setSearchParams] = useSearchParams();
    const [editingVariant, setEditingVariant] = useState<ProductVariant | null>(null);
    const [editingIdentifier, setEditingIdentifier] = useState<ProductIdentifier | null>(null);
    const [deleteState, setDeleteState] = useState<DeleteState>(null);
    const [variantFormError, setVariantFormError] = useState<string | null>(null);
    const [identifierFormError, setIdentifierFormError] = useState<string | null>(null);
    const activeTab = (searchParams.get('tab') as DetailTabId | null) ?? 'overview';

    const productQuery = useProduct(productId, productId > 0);
    const brandsQuery = useProductBrands({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const categoriesQuery = useProductCategories({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const unitsQuery = useUnitsOfMeasure({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const variantsQuery = useProductVariants({ tenant_id: tenantId, product_id: productId, per_page: 50, sort: '-updated_at' }, productId > 0);
    const identifiersQuery = useProductIdentifiers({ tenant_id: tenantId, product_id: productId, per_page: 50, sort: '-updated_at' }, productId > 0);

    const createVariantMutation = useCreateProductVariant();
    const updateVariantMutation = useUpdateProductVariant(editingVariant?.id ?? 0);
    const deleteVariantMutation = useDeleteProductVariant();
    const createIdentifierMutation = useCreateProductIdentifier();
    const updateIdentifierMutation = useUpdateProductIdentifier(editingIdentifier?.id ?? 0);
    const deleteIdentifierMutation = useDeleteProductIdentifier();

    const variantForm = useForm<ProductVariantFormInput, unknown, ProductVariantFormValues>({
        resolver: zodResolver(productVariantFormSchema),
        defaultValues: {
            name: '',
            sku: '',
            attribute_summary: '',
            notes: '',
            is_default: false,
            is_active: true,
        },
    });

    const identifierForm = useForm<ProductIdentifierFormInput, unknown, ProductIdentifierFormValues>({
        resolver: zodResolver(productIdentifierFormSchema),
        defaultValues: {
            technology: 'barcode_1d',
            format: undefined,
            value: '',
            gs1_company_prefix: '',
            is_primary: false,
            is_active: true,
        },
    });

    useEffect(() => {
        if (!editingVariant) {
            variantForm.reset({
                name: '',
                sku: '',
                attribute_summary: '',
                notes: '',
                is_default: false,
                is_active: true,
            });
            return;
        }

        variantForm.reset({
            name: editingVariant.name,
            sku: editingVariant.sku ?? '',
            attribute_summary: typeof editingVariant.metadata?.attribute_summary === 'string' ? editingVariant.metadata.attribute_summary : '',
            notes: typeof editingVariant.metadata?.notes === 'string' ? editingVariant.metadata.notes : '',
            is_default: editingVariant.is_default,
            is_active: editingVariant.is_active,
        });
    }, [editingVariant, variantForm]);

    useEffect(() => {
        if (!editingIdentifier) {
            identifierForm.reset({
                technology: 'barcode_1d',
                format: undefined,
                value: '',
                gs1_company_prefix: '',
                is_primary: false,
                is_active: true,
            });
            return;
        }

        identifierForm.reset({
            technology: editingIdentifier.technology ?? 'barcode_1d',
                format: editingIdentifier.format ?? undefined,
            value: editingIdentifier.value ?? '',
            gs1_company_prefix: editingIdentifier.gs1_company_prefix ?? '',
            is_primary: editingIdentifier.is_primary,
            is_active: editingIdentifier.is_active,
        });
    }, [editingIdentifier, identifierForm]);

    const brandName = useMemo(() => {
        if (!productQuery.data?.brand_id) {
            return '-';
        }

        return brandsQuery.data?.items.find((brand) => brand.id === productQuery.data?.brand_id)?.name ?? `#${productQuery.data.brand_id}`;
    }, [brandsQuery.data?.items, productQuery.data?.brand_id]);

    const categoryName = useMemo(() => {
        if (!productQuery.data?.category_id) {
            return '-';
        }

        return (
            categoriesQuery.data?.items.find((category) => category.id === productQuery.data?.category_id)?.name ??
            `#${productQuery.data.category_id}`
        );
    }, [categoriesQuery.data?.items, productQuery.data?.category_id]);

    if (productId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The product route is missing a valid product ID." title="Invalid product route" />
            </div>
        );
    }

    if (productQuery.isPending || brandsQuery.isPending || categoriesQuery.isPending || unitsQuery.isPending) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <LoadingState lines={10} />
            </div>
        );
    }

    if (productQuery.isError || brandsQuery.isError || categoriesQuery.isError || unitsQuery.isError) {
        const error = productQuery.error ?? brandsQuery.error ?? categoriesQuery.error ?? unitsQuery.error;

        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState
                    action={
                        <Button
                            onClick={() => {
                                void productQuery.refetch();
                                void brandsQuery.refetch();
                                void categoriesQuery.refetch();
                                void unitsQuery.refetch();
                            }}
                            variant="secondary"
                        >
                            Retry
                        </Button>
                    }
                    description={error?.message ?? 'Unable to load product details.'}
                    title="Unable to load product details"
                />
            </div>
        );
    }

    const product = productQuery.data;
    const units = unitsQuery.data?.items ?? [];
    const baseUomName = findUnitName(units, product.base_uom_id);
    const purchaseUomName = findUnitName(units, product.purchase_uom_id);
    const salesUomName = findUnitName(units, product.sales_uom_id);
    const salesPrice = product.metadata?.sales_price ?? null;
    const purchasePrice = product.metadata?.purchase_price ?? null;
    const profitMargin = product.metadata?.profit_margin ?? null;

    async function handleVariantSubmit(values: ProductVariantFormValues) {
        setVariantFormError(null);

        try {
            const payload = {
                tenant_id: tenantId,
                product_id: product.id,
                name: values.name,
                sku: values.sku ?? null,
                is_default: values.is_default,
                is_active: values.is_active,
                metadata: {
                    attribute_summary: values.attribute_summary ?? null,
                    notes: values.notes ?? null,
                },
            };

            if (editingVariant) {
                await updateVariantMutation.mutateAsync(payload);
                showToast({ title: 'Variant updated', description: `${values.name} was updated successfully.`, tone: 'success' });
            } else {
                await createVariantMutation.mutateAsync(payload);
                showToast({ title: 'Variant added', description: `${values.name} is now part of this product.`, tone: 'success' });
            }

            setEditingVariant(null);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, variantForm.setError, {
                    onUnhandled: (message) => setVariantFormError(message),
                });
                return;
            }

            setVariantFormError(error instanceof Error ? error.message : 'Unable to save variant.');
        }
    }

    async function handleIdentifierSubmit(values: ProductIdentifierFormValues) {
        setIdentifierFormError(null);

        try {
            const payload = {
                tenant_id: tenantId,
                product_id: product.id,
                technology: values.technology,
                format: values.format ?? null,
                value: values.value,
                gs1_company_prefix: values.gs1_company_prefix ?? null,
                is_primary: values.is_primary,
                is_active: values.is_active,
            };

            if (editingIdentifier) {
                await updateIdentifierMutation.mutateAsync(payload);
                showToast({ title: 'Identifier updated', description: `${values.value} was updated successfully.`, tone: 'success' });
            } else {
                await createIdentifierMutation.mutateAsync(payload);
                showToast({ title: 'Identifier added', description: `${values.value} is now linked to this product.`, tone: 'success' });
            }

            setEditingIdentifier(null);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, identifierForm.setError, {
                    onUnhandled: (message) => setIdentifierFormError(message),
                });
                return;
            }

            setIdentifierFormError(error instanceof Error ? error.message : 'Unable to save identifier.');
        }
    }

    async function handleDeleteConfirm() {
        if (!deleteState) {
            return;
        }

        if (deleteState.type === 'variant') {
            await deleteVariantMutation.mutateAsync({
                productId: product.id,
                tenantId,
                variantId: deleteState.target.id,
            });
            showToast({
                title: 'Variant deleted',
                description: `${deleteState.target.name} was removed from this product.`,
                tone: 'success',
            });
        } else {
            await deleteIdentifierMutation.mutateAsync({
                productId: product.id,
                tenantId,
                identifierId: deleteState.target.id,
            });
            showToast({
                title: 'Identifier deleted',
                description: `${deleteState.target.value ?? 'Identifier'} was removed from this product.`,
                tone: 'success',
            });
        }

        setDeleteState(null);
    }

    const variantColumns: DataTableColumn<ProductVariant>[] = [
        {
            key: 'name',
            header: 'Variant',
            render: (variant) => (
                <div>
                    <p className="font-medium text-stone-950">{variant.name}</p>
                    <p className="mt-1 text-xs text-stone-500">{variant.sku || 'No SKU assigned'}</p>
                </div>
            ),
        },
        {
            key: 'attributes',
            header: 'Attributes',
            render: (variant) => (
                <span className="text-sm text-stone-600">
                    {typeof variant.metadata?.attribute_summary === 'string' ? variant.metadata.attribute_summary : 'No attribute summary'}
                </span>
            ),
        },
        {
            key: 'default',
            header: 'Default',
            render: (variant) => <StatusBadge tone={variant.is_default ? 'success' : 'default'}>{variant.is_default ? 'Default' : 'Optional'}</StatusBadge>,
        },
        {
            key: 'status',
            header: 'Status',
            render: (variant) => <StatusBadge tone={variant.is_active ? 'success' : 'default'}>{variant.is_active ? 'Active' : 'Inactive'}</StatusBadge>,
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[12rem]',
            render: (variant) => (
                <div className="flex flex-wrap gap-2">
                    <Button className="h-9 px-3 text-xs" onClick={() => setEditingVariant(variant)} type="button" variant="secondary">
                        Edit
                    </Button>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteState({ type: 'variant', target: variant })} type="button" variant="secondary">
                        Delete
                    </Button>
                </div>
            ),
        },
    ];

    const identifierColumns: DataTableColumn<ProductIdentifier>[] = [
        {
            key: 'value',
            header: 'Identifier',
            render: (identifier) => (
                <div>
                    <p className="font-medium text-stone-950">{identifier.value || 'No value'}</p>
                    <p className="mt-1 text-xs text-stone-500">{identifier.format || identifier.technology || 'Unspecified format'}</p>
                </div>
            ),
        },
        {
            key: 'gs1',
            header: 'GS1',
            render: (identifier) => <span className="text-sm text-stone-600">{identifier.gs1_company_prefix || '-'}</span>,
        },
        {
            key: 'primary',
            header: 'Primary',
            render: (identifier) => <StatusBadge tone={identifier.is_primary ? 'success' : 'default'}>{identifier.is_primary ? 'Primary' : 'Secondary'}</StatusBadge>,
        },
        {
            key: 'status',
            header: 'Status',
            render: (identifier) => <StatusBadge tone={identifier.is_active ? 'success' : 'default'}>{identifier.is_active ? 'Active' : 'Inactive'}</StatusBadge>,
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[12rem]',
            render: (identifier) => (
                <div className="flex flex-wrap gap-2">
                    <Button className="h-9 px-3 text-xs" onClick={() => setEditingIdentifier(identifier)} type="button" variant="secondary">
                        Edit
                    </Button>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteState({ type: 'identifier', target: identifier })} type="button" variant="secondary">
                        Delete
                    </Button>
                </div>
            ),
        },
    ];

    function renderOverviewTab() {
        return (
            <div className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <div className="space-y-4">
                    <SectionCard description="Core product identity, grouping, and operational state from the backend product resource." title="Basic information">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Product Type</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{product.type}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Status</dt>
                                <dd className="mt-1">
                                    <StatusBadge tone={product.is_active ? 'success' : 'default'}>{product.is_active ? 'Active' : 'Inactive'}</StatusBadge>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">SKU / Code</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{product.sku || product.slug || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Last Updated</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{formatDate(product.updated_at)}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Brand</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{brandName}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Category</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{categoryName}</dd>
                            </div>
                        </dl>
                        <div className="mt-5 rounded-2xl border border-stone-200/80 bg-white px-4 py-4 text-sm leading-6 text-stone-600">
                            {product.description || 'No description has been added to this product yet.'}
                        </div>
                    </SectionCard>

                    <SectionCard description="Inventory flags and valuation choices already aligned with the backend contract." title="Inventory controls">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Valuation</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{product.valuation_method || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Conversion Factor</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{product.uom_conversion_factor || '1'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Batch Tracking</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{product.is_batch_tracked ? 'Enabled' : 'Disabled'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Lot Tracking</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{product.is_lot_tracked ? 'Enabled' : 'Disabled'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Serial Tracking</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{product.is_serial_tracked ? 'Enabled' : 'Disabled'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Standard Cost</dt>
                                <dd className="mt-1 text-sm font-medium text-stone-900">{product.standard_cost || '-'}</dd>
                            </div>
                        </dl>
                    </SectionCard>
                </div>

                <div className="space-y-4">
                    <SectionCard description="Reference commercial values captured during product setup." title="Pricing summary">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="rounded-2xl border border-stone-200/80 bg-white px-4 py-4">
                                <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Purchase Price</p>
                                <p className="mt-2 text-2xl font-semibold text-stone-950">{purchasePrice || '-'}</p>
                            </div>
                            <div className="rounded-2xl border border-stone-200/80 bg-white px-4 py-4">
                                <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Sales Price</p>
                                <p className="mt-2 text-2xl font-semibold text-stone-950">{salesPrice || '-'}</p>
                            </div>
                            <div className="rounded-2xl border border-stone-200/80 bg-white px-4 py-4 sm:col-span-2">
                                <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Target Margin</p>
                                <p className="mt-2 text-2xl font-semibold text-stone-950">{profitMargin ? `${profitMargin}%` : '-'}</p>
                                <p className="mt-2 text-sm text-stone-600">{typeof product.metadata?.price_list_note === 'string' ? product.metadata.price_list_note : 'No price note added yet.'}</p>
                            </div>
                        </div>
                    </SectionCard>

                    <SectionCard description="Unit assignments used by product, purchase, and sales operations." title="Unit summary">
                        <dl className="space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <dt className="text-sm text-stone-500">Base UOM</dt>
                                <dd className="text-sm font-medium text-stone-900">{baseUomName}</dd>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <dt className="text-sm text-stone-500">Purchase UOM</dt>
                                <dd className="text-sm font-medium text-stone-900">{purchaseUomName}</dd>
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <dt className="text-sm text-stone-500">Sales UOM</dt>
                                <dd className="text-sm font-medium text-stone-900">{salesUomName}</dd>
                            </div>
                        </dl>
                    </SectionCard>
                </div>
            </div>
        );
    }

    function renderVariantsTab() {
        if (variantsQuery.isPending) {
            return <LoadingState lines={6} />;
        }

        if (variantsQuery.isError) {
            return <ErrorState description={variantsQuery.error.message} title="Unable to load product variants" />;
        }

        return (
            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <ContentCard className="p-0">
                    <DataTable
                        columns={variantColumns}
                        emptyState={<EmptyState className="m-6" description="No product variants are available for this product yet." title="No variants" />}
                        getRowKey={(variant) => variant.id}
                        rows={variantsQuery.data.items}
                    />
                </ContentCard>

                <SectionCard
                    description="Create or edit product variants using the actual backend variant contract."
                    title={editingVariant ? `Edit ${editingVariant.name}` : 'Add Variant'}
                >
                    {variantFormError ? <div className="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{variantFormError}</div> : null}
                    <ProductVariantForm
                        form={variantForm}
                        isSubmitting={createVariantMutation.isPending || updateVariantMutation.isPending}
                        mode={editingVariant ? 'edit' : 'create'}
                        onCancel={() => {
                            setEditingVariant(null);
                            setVariantFormError(null);
                        }}
                        onSubmit={handleVariantSubmit}
                    />
                </SectionCard>
            </div>
        );
    }

    function renderIdentifiersTab() {
        if (identifiersQuery.isPending) {
            return <LoadingState lines={6} />;
        }

        if (identifiersQuery.isError) {
            return <ErrorState description={identifiersQuery.error.message} title="Unable to load product identifiers" />;
        }

        return (
            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <ContentCard className="p-0">
                    <DataTable
                        columns={identifierColumns}
                        emptyState={<EmptyState className="m-6" description="No identifiers are currently linked to this product." title="No identifiers" />}
                        getRowKey={(identifier) => identifier.id}
                        rows={identifiersQuery.data.items}
                    />
                </ContentCard>

                <SectionCard
                    description="Manage barcodes and other identifiers with the backend-supported identifier contract."
                    title={editingIdentifier ? `Edit ${editingIdentifier.value}` : 'Add Identifier'}
                >
                    {identifierFormError ? <div className="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{identifierFormError}</div> : null}
                    <ProductIdentifierForm
                        form={identifierForm}
                        isSubmitting={createIdentifierMutation.isPending || updateIdentifierMutation.isPending}
                        mode={editingIdentifier ? 'edit' : 'create'}
                        onCancel={() => {
                            setEditingIdentifier(null);
                            setIdentifierFormError(null);
                        }}
                        onSubmit={handleIdentifierSubmit}
                    />
                </SectionCard>
            </div>
        );
    }

    function renderStockTab() {
        return (
            <SectionCard description="Inventory module integration is not wired yet, but this panel is arranged for stock, reserved, and available quantities." title="Stock summary">
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 px-4 py-8 text-center text-sm text-stone-500">On-hand quantity will appear here</div>
                    <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 px-4 py-8 text-center text-sm text-stone-500">Reserved quantity will appear here</div>
                    <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 px-4 py-8 text-center text-sm text-stone-500">Available-to-promise will appear here</div>
                </div>
            </SectionCard>
        );
    }

    function renderPriceListsTab() {
        return (
            <SectionCard description="Price list assignment endpoints are not part of this phase, so this tab shows the product’s current reference pricing context." title="Price list readiness">
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-2xl border border-stone-200/80 bg-white px-5 py-5">
                        <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Reference Sales Price</p>
                        <p className="mt-2 text-2xl font-semibold text-stone-950">{salesPrice || '-'}</p>
                        <p className="mt-2 text-sm text-stone-600">{typeof product.metadata?.price_list_note === 'string' ? product.metadata.price_list_note : 'No price list note captured yet.'}</p>
                    </div>
                    <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 px-5 py-5">
                        <p className="text-sm font-medium text-stone-900">Ready for pricing module integration</p>
                        <p className="mt-2 text-sm leading-6 text-stone-600">Customer price lists, price levels, and effective-dated rules can slot into this layout without structural redesign.</p>
                    </div>
                </div>
            </SectionCard>
        );
    }

    function renderSupplierProductsTab() {
        return (
            <SectionCard description="Supplier-product relationship endpoints are not wired yet, but product metadata already stores the preferred supplier reference." title="Supplier product readiness">
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-2xl border border-stone-200/80 bg-white px-5 py-5">
                        <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Supplier Reference</p>
                        <p className="mt-2 text-2xl font-semibold text-stone-950">
                            {typeof product.metadata?.supplier_reference === 'string' && product.metadata.supplier_reference
                                ? product.metadata.supplier_reference
                                : '-'}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 px-5 py-5">
                        <p className="text-sm font-medium text-stone-900">Ready for supplier item linkage</p>
                        <p className="mt-2 text-sm leading-6 text-stone-600">Preferred supplier, vendor item code, and lead-time details can layer into this panel during the Purchase module phase.</p>
                    </div>
                </div>
            </SectionCard>
        );
    }

    function renderTabContent() {
        if (activeTab === 'overview') {
            return renderOverviewTab();
        }

        if (activeTab === 'variants') {
            return renderVariantsTab();
        }

        if (activeTab === 'identifiers') {
            return renderIdentifiersTab();
        }

        if (activeTab === 'stock') {
            return renderStockTab();
        }

        if (activeTab === 'price-lists') {
            return renderPriceListsTab();
        }

        return renderSupplierProductsTab();
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Link to={`/products/${product.id}/edit`}>
                            <Button>Edit Product</Button>
                        </Link>
                    </div>
                }
                breadcrumbs={[{ label: 'Products', href: '/products' }, { label: product.name }]}
                description="The Product detail page now works like a real module workspace: overview first, then variants, identifiers, and future operational panels."
                title={product.name}
            />

            <ContentCard className="space-y-6">
                <div className="flex flex-wrap gap-2">
                    {detailTabs.map((tab) => (
                        <button
                            key={tab.id}
                            className={`rounded-2xl border px-4 py-2 text-sm font-medium transition ${
                                activeTab === tab.id
                                    ? 'border-stone-950 bg-stone-950 text-white'
                                    : 'border-stone-200 bg-white text-stone-600 hover:bg-stone-50 hover:text-stone-900'
                            }`}
                            onClick={() => setSearchParams({ tab: tab.id })}
                            type="button"
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {renderTabContent()}
            </ContentCard>

            <ConfirmModal
                confirmLabel={deleteState?.type === 'variant' ? 'Delete variant' : 'Delete identifier'}
                description={
                    deleteState?.type === 'variant'
                        ? `Delete ${deleteState.target.name}?`
                        : deleteState?.type === 'identifier'
                          ? `Delete ${deleteState.target.value ?? 'this identifier'}?`
                          : ''
                }
                isLoading={deleteVariantMutation.isPending || deleteIdentifierMutation.isPending}
                onCancel={() => setDeleteState(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteState)}
                title={deleteState?.type === 'variant' ? 'Delete variant' : 'Delete identifier'}
            />
        </div>
    );
}
