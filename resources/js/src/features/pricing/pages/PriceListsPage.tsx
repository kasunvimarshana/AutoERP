import { useMemo } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { Button } from '../../../components/ui/Button';
import { useTenant } from '../../auth/context/TenantContext';
import { usePriceListItems, usePriceLists } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatCurrency, formatDate, parseBooleanSearchParam, parsePositiveInteger } from '../../shared/utils';
import type { PriceListItemRecord, PriceListRecord } from '../types';

export function PriceListsPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const type = searchParams.get('type') ?? '';
    const active = searchParams.get('active') ?? '';

    const priceListsQuery = usePriceLists({
        tenant_id: tenantId,
        page,
        per_page: 10,
        name: search || undefined,
        type: type ? (type as PriceListRecord['type']) : undefined,
        is_active: parseBooleanSearchParam(active),
        sort: 'name:asc',
    });

    const selectedPriceListId = parsePositiveInteger(searchParams.get('price_list_id'), priceListsQuery.data?.items[0]?.id ?? 0);
    const selectedPriceList = priceListsQuery.data?.items.find((priceList) => priceList.id === selectedPriceListId) ?? null;
    const priceListItemsQuery = usePriceListItems(selectedPriceListId, 1, 25, selectedPriceListId > 0);

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

            if ('search' in updates || 'type' in updates || 'active' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const priceListColumns: DataTableColumn<PriceListRecord>[] = useMemo(
        () => [
            {
                key: 'name',
                header: 'Price List',
                render: (priceList) => (
                    <div>
                        <p className="font-medium text-stone-950">{priceList.name}</p>
                        <p className="mt-1 text-xs text-stone-500">Currency #{priceList.currency_id ?? '-'}</p>
                    </div>
                ),
            },
            { key: 'type', header: 'Type', render: (priceList) => <StatusBadge>{priceList.type}</StatusBadge> },
            { key: 'default', header: 'Default', render: (priceList) => <StatusBadge tone={priceList.is_default ? 'success' : 'default'}>{priceList.is_default ? 'Default' : 'Optional'}</StatusBadge> },
            { key: 'active', header: 'Status', render: (priceList) => <StatusBadge tone={priceList.is_active ? 'success' : 'default'}>{priceList.is_active ? 'Active' : 'Inactive'}</StatusBadge> },
            {
                key: 'validity',
                header: 'Validity',
                render: (priceList) => (
                    <div className="text-sm text-stone-700">
                        <p>{formatDate(priceList.valid_from)}</p>
                        <p className="mt-1 text-xs text-stone-500">to {formatDate(priceList.valid_to)}</p>
                    </div>
                ),
            },
            {
                key: 'actions',
                header: 'Items',
                className: 'w-[10rem]',
                render: (priceList) => (
                    <Button className="h-9 px-3 text-xs" onClick={() => updateParams({ price_list_id: priceList.id })} type="button" variant="secondary">
                        Inspect items
                    </Button>
                ),
            },
        ],
        [setSearchParams],
    );

    const itemColumns: DataTableColumn<PriceListItemRecord>[] = useMemo(
        () => [
            {
                key: 'product',
                header: 'Product',
                render: (item) => (
                    <div>
                        <p className="font-medium text-stone-950">Product #{item.product_id}</p>
                        <p className="mt-1 text-xs text-stone-500">Variant {item.variant_id ?? '-'} | UOM {item.uom_id ?? '-'}</p>
                    </div>
                ),
            },
            { key: 'min_quantity', header: 'Min Qty', render: (item) => <span className="text-sm text-stone-700">{item.min_quantity}</span> },
            { key: 'price', header: 'Price', render: (item) => <span className="text-sm text-stone-700">{formatCurrency(item.price)}</span> },
            { key: 'discount_pct', header: 'Discount', render: (item) => <span className="text-sm text-stone-700">{item.discount_pct ?? 0}%</span> },
            {
                key: 'validity',
                header: 'Validity',
                render: (item) => (
                    <div className="text-sm text-stone-700">
                        <p>{formatDate(item.valid_from)}</p>
                        <p className="mt-1 text-xs text-stone-500">to {formatDate(item.valid_to)}</p>
                    </div>
                ),
            },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Pricing' }, { label: 'Price Lists' }]}
                description="Phase 5 pricing now reads from the existing price-list APIs instead of routing into placeholders, including item-level visibility for the currently selected list."
                title="Price Lists"
            />

            <ContentCard className="p-0">
                <TableToolbar
                    actions={<div className="hidden text-xs uppercase tracking-[0.16em] text-stone-400 lg:block">Tenant {tenantId}</div>}
                    description="Review list pricing, sales versus purchase contexts, and active date ranges before customer or supplier assignment flows."
                    title="Pricing catalog"
                >
                    <SearchFilterToolbar
                        filters={
                            <>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ type: event.target.value || undefined })} value={type}>
                                    <option value="">All types</option>
                                    <option value="sales">Sales</option>
                                    <option value="purchase">Purchase</option>
                                </Select>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ active: event.target.value || undefined })} value={active}>
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
                                placeholder="Search price list name"
                                value={search}
                            />
                        }
                        trailing={<div className="text-sm text-stone-500">{priceListsQuery.data?.meta?.total ?? 0} price lists</div>}
                    />
                </TableToolbar>

                {priceListsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : priceListsQuery.isError ? (
                    isForbiddenError(priceListsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={priceListsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={priceListsQuery.error.message} title="Unable to load price lists" />
                    )
                ) : (
                    <DataTable
                        columns={priceListColumns}
                        emptyState={<EmptyState className="m-6" description="No price lists match the current filters." title="No price lists found" />}
                        footer={<TablePagination meta={priceListsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(priceList) => priceList.id}
                        rows={priceListsQuery.data.items}
                    />
                )}
            </ContentCard>

            <ContentCard className="p-0">
                <TableToolbar
                    description={
                        selectedPriceList
                            ? `${selectedPriceList.name} is currently selected. The item list below uses the real nested price-list endpoint already exposed by the backend.`
                            : 'Choose a price list from the table above to inspect the assigned pricing items.'
                    }
                    title="Price list items"
                />

                {!selectedPriceList ? (
                    <EmptyState
                        className="m-6"
                        description="Select a price list from the table above to inspect item pricing, minimum quantities, and validity windows."
                        title="No price list selected"
                    />
                ) : priceListItemsQuery.isPending ? (
                    <LoadingState className="m-6" lines={6} />
                ) : priceListItemsQuery.isError ? (
                    isForbiddenError(priceListItemsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={priceListItemsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={priceListItemsQuery.error.message} title="Unable to load price list items" />
                    )
                ) : (
                    <DataTable
                        columns={itemColumns}
                        emptyState={<EmptyState className="m-6" description="This price list does not have item rows yet." title="No items found" />}
                        getRowKey={(item) => item.id}
                        rows={priceListItemsQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
