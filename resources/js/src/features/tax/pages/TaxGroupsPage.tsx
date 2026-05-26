import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { DataTable, SearchFilterToolbar, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { useTenant } from '../../auth/context/TenantContext';
import { useTaxGroups } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDate, parsePositiveInteger } from '../../shared/utils';
import type { TaxGroupRecord } from '../types';

export function TaxGroupsPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';

    const taxGroupsQuery = useTaxGroups({
        tenant_id: tenantId,
        page,
        per_page: 10,
        name: search || undefined,
        sort: 'name:asc',
    });

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

            if ('search' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const columns: DataTableColumn<TaxGroupRecord>[] = useMemo(
        () => [
            {
                key: 'name',
                header: 'Tax Group',
                render: (taxGroup) => (
                    <div>
                        <p className="font-medium text-stone-950">{taxGroup.name}</p>
                        <p className="mt-1 text-xs text-stone-500">Group #{taxGroup.id}</p>
                    </div>
                ),
            },
            { key: 'description', header: 'Description', render: (taxGroup) => <span className="text-sm text-stone-700">{taxGroup.description || '-'}</span> },
            { key: 'updated_at', header: 'Updated', render: (taxGroup) => <span className="text-sm text-stone-700">{formatDate(taxGroup.updated_at)}</span> },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Tax' }, { label: 'Groups' }]}
                description="Tax group administration is now connected to the existing tax module so the Phase 5 routes show real jurisdiction groupings instead of placeholders."
                title="Tax Groups"
            />

            <ContentCard className="p-0">
                <TableToolbar
                    actions={<div className="hidden text-xs uppercase tracking-[0.16em] text-stone-400 lg:block">Tenant {tenantId}</div>}
                    description="Review tax group definitions before drilling into group-specific rates and applicability rules."
                    title="Tax group catalog"
                >
                    <SearchFilterToolbar
                        search={
                            <Input
                                className="w-full md:max-w-sm"
                                label={undefined}
                                onChange={(event) => updateParams({ search: event.target.value || undefined })}
                                placeholder="Search tax group name"
                                value={search}
                            />
                        }
                        trailing={<div className="text-sm text-stone-500">{taxGroupsQuery.data?.meta?.total ?? 0} groups</div>}
                    />
                </TableToolbar>

                {taxGroupsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : taxGroupsQuery.isError ? (
                    isForbiddenError(taxGroupsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={taxGroupsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={taxGroupsQuery.error.message} title="Unable to load tax groups" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No tax groups match the current filters." title="No tax groups found" />}
                        footer={<TablePagination meta={taxGroupsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(taxGroup) => taxGroup.id}
                        rows={taxGroupsQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
