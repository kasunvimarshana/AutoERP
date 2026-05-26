import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { useTenant } from '../../auth/context/TenantContext';
import { useProductCategories } from '../../products/hooks';
import { useTaxGroups, useTaxRules } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDate, parsePositiveInteger } from '../../shared/utils';
import type { TaxRuleRecord } from '../types';

export function TaxRulesPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const selectedGroupId = Number(searchParams.get('group_id') ?? 0);
    const partyType = searchParams.get('party_type') ?? '';
    const region = searchParams.get('region') ?? '';

    const taxGroupsQuery = useTaxGroups({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const productCategoriesQuery = useProductCategories({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });

    const fallbackGroupId = taxGroupsQuery.data?.items[0]?.id ?? 0;
    const activeGroupId = selectedGroupId || fallbackGroupId;
    const activeGroup = taxGroupsQuery.data?.items.find((taxGroup) => taxGroup.id === activeGroupId) ?? null;

    const taxRulesQuery = useTaxRules(
        activeGroupId,
        {
            tenant_id: tenantId,
            page,
            per_page: 10,
            party_type: partyType ? (partyType as 'customer' | 'supplier') : undefined,
            region: region || undefined,
            sort: '-priority',
        },
        activeGroupId > 0,
    );

    const categoryNames = useMemo(
        () => new Map((productCategoriesQuery.data?.items ?? []).map((category) => [category.id, category.name])),
        [productCategoriesQuery.data?.items],
    );

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

            if ('group_id' in updates || 'party_type' in updates || 'region' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const columns: DataTableColumn<TaxRuleRecord>[] = useMemo(
        () => [
            {
                key: 'product_category_id',
                header: 'Product Category',
                render: (taxRule) => <span className="text-sm text-stone-700">{taxRule.product_category_id ? categoryNames.get(taxRule.product_category_id) ?? `Category #${taxRule.product_category_id}` : 'All categories'}</span>,
            },
            { key: 'party_type', header: 'Party Type', render: (taxRule) => <StatusBadge>{taxRule.party_type ?? 'Any'}</StatusBadge> },
            { key: 'region', header: 'Region', render: (taxRule) => <span className="text-sm text-stone-700">{taxRule.region || 'Any region'}</span> },
            { key: 'priority', header: 'Priority', render: (taxRule) => <StatusBadge tone="warning">{String(taxRule.priority)}</StatusBadge> },
            { key: 'updated_at', header: 'Updated', render: (taxRule) => <span className="text-sm text-stone-700">{formatDate(taxRule.updated_at)}</span> },
        ],
        [categoryNames],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Tax' }, { label: 'Rules' }]}
                description="Tax rule lookup now uses the nested group rule endpoint and existing product category data, making the Phase 5 route operational."
                title="Tax Rules"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Choose a tax group, then refine the list by party type or regional targeting to inspect effective rule ordering." title="Rule lookup">
                    {taxGroupsQuery.isPending ? (
                        <LoadingState lines={3} />
                    ) : taxGroupsQuery.isError ? (
                        isForbiddenError(taxGroupsQuery.error) ? (
                            <ProtectedErrorState description={taxGroupsQuery.error.message} />
                        ) : (
                            <ErrorState description={taxGroupsQuery.error.message} title="Unable to load tax groups" />
                        )
                    ) : taxGroupsQuery.data.items.length === 0 ? (
                        <EmptyState description="Create a tax group first to unlock rule management." title="No tax groups available" />
                    ) : (
                        <SearchFilterToolbar
                            filters={
                                <>
                                    <Select className="w-full md:max-w-[16rem]" onChange={(event) => updateParams({ group_id: event.target.value })} value={activeGroupId ? String(activeGroupId) : ''}>
                                        {taxGroupsQuery.data.items.map((taxGroup) => (
                                            <option key={taxGroup.id} value={taxGroup.id}>
                                                {taxGroup.name}
                                            </option>
                                        ))}
                                    </Select>
                                    <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ party_type: event.target.value || undefined })} value={partyType}>
                                        <option value="">All party types</option>
                                        <option value="customer">Customer</option>
                                        <option value="supplier">Supplier</option>
                                    </Select>
                                </>
                            }
                            search={<Input className="w-full md:max-w-sm" label={undefined} onChange={(event) => updateParams({ region: event.target.value || undefined })} placeholder="Filter by region" value={region} />}
                            trailing={<div className="text-sm text-stone-500">{activeGroup ? activeGroup.description || activeGroup.name : 'No group selected'}</div>}
                        />
                    )}
                </TableToolbar>

                {!activeGroup ? (
                    <EmptyState className="m-6" description="Select a tax group to inspect its rules." title="No group selected" />
                ) : taxRulesQuery.isPending || productCategoriesQuery.isPending ? (
                    <LoadingState className="m-6" lines={7} />
                ) : taxRulesQuery.isError ? (
                    isForbiddenError(taxRulesQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={taxRulesQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={taxRulesQuery.error.message} title="Unable to load tax rules" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No tax rules match the current filters for this group." title="No tax rules found" />}
                        footer={<TablePagination meta={taxRulesQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(taxRule) => taxRule.id}
                        rows={taxRulesQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
