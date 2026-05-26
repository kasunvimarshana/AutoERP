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
import { useTenants } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDateTime, parseBooleanSearchParam, parsePositiveInteger } from '../../shared/utils';
import type { TenantRecord } from '../types';

export function TenantsPage() {
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const status = searchParams.get('status') ?? '';
    const active = searchParams.get('active') ?? '';

    const tenantsQuery = useTenants({
        page,
        per_page: 10,
        name: search || undefined,
        status: status || undefined,
        active: parseBooleanSearchParam(active),
        sort: 'name',
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

            if ('search' in updates || 'status' in updates || 'active' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const columns: DataTableColumn<TenantRecord>[] = useMemo(
        () => [
            {
                key: 'name',
                header: 'Tenant',
                render: (tenant) => (
                    <div>
                        <p className="font-medium text-stone-950">{tenant.name}</p>
                        <p className="mt-1 text-xs text-stone-500">
                            {tenant.slug} {tenant.domain ? `| ${tenant.domain}` : ''}
                        </p>
                    </div>
                ),
            },
            { key: 'status', header: 'Status', render: (tenant) => <StatusBadge tone={tenant.active ? 'success' : 'danger'}>{tenant.status}</StatusBadge> },
            { key: 'active', header: 'Active', render: (tenant) => <StatusBadge tone={tenant.active ? 'success' : 'default'}>{tenant.active ? 'Active' : 'Inactive'}</StatusBadge> },
            { key: 'tenant_plan_id', header: 'Plan', render: (tenant) => <span className="text-sm text-stone-700">{tenant.tenant_plan_id ?? '-'}</span> },
            { key: 'trial_ends_at', header: 'Trial Ends', render: (tenant) => <span className="text-sm text-stone-700">{formatDateTime(tenant.trial_ends_at)}</span> },
            { key: 'updated_at', header: 'Updated', render: (tenant) => <span className="text-sm text-stone-700">{formatDateTime(tenant.updated_at)}</span> },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Tenant Admin' }, { label: 'Tenants' }]}
                description="Tenant administration now exposes the real tenant registry so the sidebar route is fully wired instead of using the generic placeholder shell."
                title="Tenants"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Review tenant lifecycle status, primary domains, and plan references from the existing tenant management APIs." title="Tenant registry">
                    <SearchFilterToolbar
                        filters={
                            <>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}>
                                    <option value="">All statuses</option>
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="cancelled">Cancelled</option>
                                </Select>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ active: event.target.value || undefined })} value={active}>
                                    <option value="">All activity</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </Select>
                            </>
                        }
                        search={<Input className="w-full md:max-w-sm" label={undefined} onChange={(event) => updateParams({ search: event.target.value || undefined })} placeholder="Search tenant name" value={search} />}
                        trailing={<div className="text-sm text-stone-500">{tenantsQuery.data?.meta?.total ?? 0} tenants</div>}
                    />
                </TableToolbar>

                {tenantsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : tenantsQuery.isError ? (
                    isForbiddenError(tenantsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={tenantsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={tenantsQuery.error.message} title="Unable to load tenants" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No tenants match the current filters." title="No tenants found" />}
                        footer={<TablePagination meta={tenantsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(tenant) => tenant.id}
                        rows={tenantsQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
