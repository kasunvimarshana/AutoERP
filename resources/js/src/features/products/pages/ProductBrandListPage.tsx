import { useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { useTenant } from '../../auth/context/TenantContext';
import { useDeleteProductBrand, useProductBrands } from '../hooks';
import type { ProductBrand } from '../types';
import { formatDate, parseBooleanSearchParam, parsePositiveInteger } from '../utils';

export function ProductBrandListPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [deleteTarget, setDeleteTarget] = useState<ProductBrand | null>(null);

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const isActive = parseBooleanSearchParam(searchParams.get('is_active'));
    const brandsQuery = useProductBrands({
        tenant_id: tenantId,
        page,
        per_page: 10,
        name: search || undefined,
        is_active: isActive,
        sort: 'name',
    });
    const deleteMutation = useDeleteProductBrand();

    const parentMap = useMemo(() => new Map(brandsQuery.data?.items.map((brand) => [brand.id, brand.name]) ?? []), [brandsQuery.data?.items]);

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

            if ('search' in updates || 'is_active' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        await deleteMutation.mutateAsync(deleteTarget.id);
        setDeleteTarget(null);
    }

    const columns: DataTableColumn<ProductBrand>[] = [
        {
            key: 'name',
            header: 'Brand',
            render: (brand) => (
                <div>
                    <p className="font-medium text-stone-950">{brand.name}</p>
                    <p className="mt-1 text-xs text-stone-500">{brand.code || brand.slug || 'No code assigned'}</p>
                </div>
            ),
        },
        {
            key: 'parent',
            header: 'Parent',
            render: (brand) => <span>{brand.parent_id ? parentMap.get(brand.parent_id) ?? `#${brand.parent_id}` : '-'}</span>,
        },
        {
            key: 'website',
            header: 'Website',
            render: (brand) => <span className="text-sm text-stone-600">{brand.website || '-'}</span>,
        },
        {
            key: 'status',
            header: 'Status',
            render: (brand) => <StatusBadge tone={brand.is_active ? 'success' : 'default'}>{brand.is_active ? 'Active' : 'Inactive'}</StatusBadge>,
        },
        {
            key: 'updated',
            header: 'Updated',
            render: (brand) => formatDate(brand.updated_at),
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[11rem]',
            render: (brand) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/products/brands/${brand.id}/edit`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            Edit
                        </Button>
                    </Link>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(brand)} type="button" variant="secondary">
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
                    <Link to="/products/brands/new">
                        <Button>Add Brand</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Products', href: '/products' }, { label: 'Brands' }]}
                description="Brand master data now follows the same shared table, filter, and editor structure as the main product module."
                title="Brand List"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Maintain brand naming, hierarchy, and activity state for product assignment." title="Product brands">
                    <SearchFilterToolbar
                        filters={
                            <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ is_active: event.target.value || undefined })} value={searchParams.get('is_active') ?? ''}>
                                <option value="">All statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </Select>
                        }
                        search={
                            <Input
                                className="w-full md:max-w-sm"
                                label={undefined}
                                onChange={(event) => updateParams({ search: event.target.value || undefined })}
                                placeholder="Search brand name"
                                value={search}
                            />
                        }
                    />
                </TableToolbar>

                {brandsQuery.isPending ? (
                    <LoadingState className="m-6" lines={7} />
                ) : brandsQuery.isError ? (
                    <ErrorState
                        action={
                            <Button onClick={() => void brandsQuery.refetch()} variant="secondary">
                                Retry
                            </Button>
                        }
                        className="m-6"
                        description={brandsQuery.error.message}
                        title="Unable to load product brands"
                    />
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to="/products/brands/new">
                                        <Button>Create brand</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No brands match the current filters yet."
                                title="No brands found"
                            />
                        }
                        footer={<TablePagination meta={brandsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(brand) => brand.id}
                        rows={brandsQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete brand"
                description={deleteTarget ? `Delete ${deleteTarget.name}?` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete brand"
            />
        </div>
    );
}
