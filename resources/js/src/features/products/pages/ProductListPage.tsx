import { useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { useToast } from '../../../app/providers/ToastProvider';
import { useTenant } from '../../auth/context/TenantContext';
import { useDeleteProduct, useProductBrands, useProductCategories, useProducts } from '../hooks';
import type { Product, ProductType } from '../types';
import { formatDate, parseBooleanSearchParam, parsePositiveInteger } from '../utils';

const productTypeOptions: ProductType[] = ['physical', 'service', 'digital', 'combo', 'variable'];

export function ProductListPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [deleteTarget, setDeleteTarget] = useState<Product | null>(null);

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const typeParam = searchParams.get('type');
    const type = productTypeOptions.includes(typeParam as ProductType) ? (typeParam as ProductType) : undefined;
    const brandId = parsePositiveInteger(searchParams.get('brand_id'), 0);
    const categoryId = parsePositiveInteger(searchParams.get('category_id'), 0);
    const isActive = parseBooleanSearchParam(searchParams.get('is_active'));

    const productQuery = useProducts({
        tenant_id: tenantId,
        page,
        per_page: 10,
        name: search || undefined,
        type,
        category_id: categoryId || undefined,
        brand_id: brandId || undefined,
        is_active: isActive,
        sort: '-updated_at',
    });
    const brandsQuery = useProductBrands({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const categoriesQuery = useProductCategories({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const deleteMutation = useDeleteProduct();

    const brandMap = useMemo(() => new Map(brandsQuery.data?.items.map((brand) => [brand.id, brand.name]) ?? []), [brandsQuery.data?.items]);
    const categoryMap = useMemo(() => new Map(categoriesQuery.data?.items.map((category) => [category.id, category.name]) ?? []), [categoriesQuery.data?.items]);

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            if ('search' in updates || 'type' in updates || 'brand_id' in updates || 'category_id' in updates || 'is_active' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        const target = deleteTarget;
        await deleteMutation.mutateAsync(target.id);
        setDeleteTarget(null);
        showToast({
            title: 'Product deleted',
            description: `${target.name} has been removed from the catalog list.`,
            tone: 'success',
        });
    }

    const columns: DataTableColumn<Product>[] = [
        {
            key: 'name',
            header: 'Product Name',
            render: (product) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/products/${product.id}`}>
                        {product.name}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{product.type}</p>
                </div>
            ),
        },
        {
            key: 'sku',
            header: 'SKU / Code',
            render: (product) => <span className="text-sm text-stone-700">{product.sku || product.slug || '-'}</span>,
        },
        {
            key: 'category',
            header: 'Category',
            render: (product) => <span className="text-sm text-stone-700">{product.category_id ? categoryMap.get(product.category_id) ?? `#${product.category_id}` : '-'}</span>,
        },
        {
            key: 'brand',
            header: 'Brand',
            render: (product) => <span className="text-sm text-stone-700">{product.brand_id ? brandMap.get(product.brand_id) ?? `#${product.brand_id}` : '-'}</span>,
        },
        {
            key: 'status',
            header: 'Status',
            render: (product) => <StatusBadge tone={product.is_active ? 'success' : 'default'}>{product.is_active ? 'Active' : 'Inactive'}</StatusBadge>,
        },
        {
            key: 'updated',
            header: 'Updated',
            render: (product) => formatDate(product.updated_at),
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[13rem]',
            render: (product) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/products/${product.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            View
                        </Button>
                    </Link>
                    <Link to={`/products/${product.id}/edit`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            Edit
                        </Button>
                    </Link>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(product)} type="button" variant="secondary">
                        Delete
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Link to="/products/new">
                            <Button>Add Product</Button>
                        </Link>
                    </div>
                }
                breadcrumbs={[{ label: 'Products' }]}
                description="The Product list now supports richer ERP filtering, clearer catalog columns, and direct routes into details and setup flows."
                title="Product List"
            />

            <ContentCard className="p-0">
                <TableToolbar
                    actions={<div className="hidden text-xs uppercase tracking-[0.16em] text-stone-400 lg:block">Tenant {tenantId}</div>}
                    description="Search catalog records, narrow the working set, and move into create, edit, or details flows."
                    title="Catalog products"
                >
                    <SearchFilterToolbar
                        filters={
                            <>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ type: event.target.value || undefined })} value={type ?? ''}>
                                    <option value="">All types</option>
                                    <option value="physical">Physical</option>
                                    <option value="service">Service</option>
                                    <option value="digital">Digital</option>
                                    <option value="combo">Combo</option>
                                    <option value="variable">Variable</option>
                                </Select>
                                <Select
                                    className="w-full md:max-w-[14rem]"
                                    onChange={(event) => updateParams({ brand_id: event.target.value || undefined })}
                                    value={brandId ? String(brandId) : ''}
                                >
                                    <option value="">All brands</option>
                                    {(brandsQuery.data?.items ?? []).map((brand) => (
                                        <option key={brand.id} value={brand.id}>
                                            {brand.name}
                                        </option>
                                    ))}
                                </Select>
                                <Select
                                    className="w-full md:max-w-[14rem]"
                                    onChange={(event) => updateParams({ category_id: event.target.value || undefined })}
                                    value={categoryId ? String(categoryId) : ''}
                                >
                                    <option value="">All categories</option>
                                    {(categoriesQuery.data?.items ?? []).map((category) => (
                                        <option key={category.id} value={category.id}>
                                            {category.name}
                                        </option>
                                    ))}
                                </Select>
                                <Select
                                    className="w-full md:max-w-[11rem]"
                                    onChange={(event) => updateParams({ is_active: event.target.value || undefined })}
                                    value={searchParams.get('is_active') ?? ''}
                                >
                                    <option value="">All statuses</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </Select>
                            </>
                        }
                        search={
                            <Input
                                className="w-full md:max-w-sm"
                                label={undefined}
                                onChange={(event) => updateParams({ search: event.target.value || undefined })}
                                placeholder="Search product name or SKU"
                                value={search}
                            />
                        }
                        trailing={<div className="text-sm text-stone-500">{productQuery.data?.meta?.total ?? 0} records</div>}
                    />
                </TableToolbar>

                {productQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : productQuery.isError ? (
                    <ErrorState
                        action={
                            <Button onClick={() => void productQuery.refetch()} variant="secondary">
                                Retry
                            </Button>
                        }
                        className="m-6"
                        description={productQuery.error.message}
                        title="Unable to load products"
                    />
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to="/products/new">
                                        <Button>Create your first product</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No products match the current filters yet. Create a product record or widen the filter set."
                                title="No products found"
                            />
                        }
                        footer={<TablePagination meta={productQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(product) => product.id}
                        rows={productQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete product"
                description={deleteTarget ? `Delete ${deleteTarget.name}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete product"
            />
        </div>
    );
}
