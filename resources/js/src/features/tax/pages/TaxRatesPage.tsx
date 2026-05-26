import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { useTenant } from '../../auth/context/TenantContext';
import { useTaxGroups, useTaxRates } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatCurrency, formatDate, parseBooleanSearchParam, parsePositiveInteger } from '../../shared/utils';
import type { TaxRateRecord } from '../types';

export function TaxRatesPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const selectedGroupId = Number(searchParams.get('group_id') ?? 0);
    const type = searchParams.get('type') ?? '';
    const active = searchParams.get('active') ?? '';
    const compound = searchParams.get('compound') ?? '';

    const taxGroupsQuery = useTaxGroups({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const fallbackGroupId = taxGroupsQuery.data?.items[0]?.id ?? 0;
    const activeGroupId = selectedGroupId || fallbackGroupId;
    const activeGroup = taxGroupsQuery.data?.items.find((taxGroup) => taxGroup.id === activeGroupId) ?? null;

    const taxRatesQuery = useTaxRates(
        activeGroupId,
        {
            tenant_id: tenantId,
            page,
            per_page: 10,
            type: type ? (type as TaxRateRecord['type']) : undefined,
            is_active: parseBooleanSearchParam(active),
            is_compound: parseBooleanSearchParam(compound),
            sort: '-updated_at',
        },
        activeGroupId > 0,
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

            if ('group_id' in updates || 'type' in updates || 'active' in updates || 'compound' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const columns: DataTableColumn<TaxRateRecord>[] = useMemo(
        () => [
            {
                key: 'name',
                header: 'Rate',
                render: (taxRate) => (
                    <div>
                        <p className="font-medium text-stone-950">{taxRate.name}</p>
                        <p className="mt-1 text-xs text-stone-500">Account #{taxRate.account_id ?? '-'}</p>
                    </div>
                ),
            },
            { key: 'type', header: 'Type', render: (taxRate) => <StatusBadge>{taxRate.type}</StatusBadge> },
            { key: 'rate', header: 'Value', render: (taxRate) => <span className="text-sm text-stone-700">{taxRate.type === 'fixed' ? formatCurrency(taxRate.rate) : `${taxRate.rate}%`}</span> },
            { key: 'compound', header: 'Compound', render: (taxRate) => <StatusBadge tone={taxRate.is_compound ? 'warning' : 'default'}>{taxRate.is_compound ? 'Compound' : 'Standard'}</StatusBadge> },
            { key: 'active', header: 'Status', render: (taxRate) => <StatusBadge tone={taxRate.is_active ? 'success' : 'default'}>{taxRate.is_active ? 'Active' : 'Inactive'}</StatusBadge> },
            { key: 'validity', header: 'Valid From', render: (taxRate) => <span className="text-sm text-stone-700">{formatDate(taxRate.valid_from)}</span> },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Tax' }, { label: 'Rates' }]}
                description="Tax rate maintenance is now split by group using the nested tax APIs that already exist in the backend."
                title="Tax Rates"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Select a tax group, then narrow the result set by rate type, activation state, or compound behavior." title="Rate lookup">
                    {taxGroupsQuery.isPending ? (
                        <LoadingState lines={3} />
                    ) : taxGroupsQuery.isError ? (
                        isForbiddenError(taxGroupsQuery.error) ? (
                            <ProtectedErrorState description={taxGroupsQuery.error.message} />
                        ) : (
                            <ErrorState description={taxGroupsQuery.error.message} title="Unable to load tax groups" />
                        )
                    ) : taxGroupsQuery.data.items.length === 0 ? (
                        <EmptyState description="Create a tax group first to unlock rate management." title="No tax groups available" />
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
                                    <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ type: event.target.value || undefined })} value={type}>
                                        <option value="">All types</option>
                                        <option value="percentage">Percentage</option>
                                        <option value="fixed">Fixed</option>
                                    </Select>
                                    <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ active: event.target.value || undefined })} value={active}>
                                        <option value="">All statuses</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </Select>
                                    <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ compound: event.target.value || undefined })} value={compound}>
                                        <option value="">All compounds</option>
                                        <option value="1">Compound</option>
                                        <option value="0">Standard</option>
                                    </Select>
                                </>
                            }
                            trailing={<div className="text-sm text-stone-500">{activeGroup ? activeGroup.description || activeGroup.name : 'No group selected'}</div>}
                        />
                    )}
                </TableToolbar>

                {!activeGroup ? (
                    <EmptyState className="m-6" description="Select a tax group to inspect its rates." title="No group selected" />
                ) : taxRatesQuery.isPending ? (
                    <LoadingState className="m-6" lines={7} />
                ) : taxRatesQuery.isError ? (
                    isForbiddenError(taxRatesQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={taxRatesQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={taxRatesQuery.error.message} title="Unable to load tax rates" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No tax rates match the current filters for this group." title="No tax rates found" />}
                        footer={<TablePagination meta={taxRatesQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(taxRate) => taxRate.id}
                        rows={taxRatesQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
