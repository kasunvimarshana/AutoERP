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
import { useTenantDomains, useTenants } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDateTime, parseBooleanSearchParam, parsePositiveInteger } from '../../shared/utils';
import type { TenantDomainRecord } from '../types';

export function TenantDomainsPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const selectedTenantId = Number(searchParams.get('tenant_id') ?? tenantId);
    const primary = searchParams.get('primary') ?? '';
    const verified = searchParams.get('verified') ?? '';

    const tenantsQuery = useTenants({ page: 1, per_page: 100, sort: 'name' });
    const activeTenant = tenantsQuery.data?.items.find((tenant) => tenant.id === selectedTenantId) ?? null;

    const domainsQuery = useTenantDomains(
        selectedTenantId,
        {
            page,
            per_page: 10,
            is_primary: parseBooleanSearchParam(primary),
            is_verified: parseBooleanSearchParam(verified),
        },
        selectedTenantId > 0,
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

            if ('tenant_id' in updates || 'primary' in updates || 'verified' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const columns: DataTableColumn<TenantDomainRecord>[] = useMemo(
        () => [
            {
                key: 'domain',
                header: 'Domain',
                render: (domain) => (
                    <div>
                        <p className="font-medium text-stone-950">{domain.domain}</p>
                        <p className="mt-1 text-xs text-stone-500">Domain #{domain.id}</p>
                    </div>
                ),
            },
            { key: 'primary', header: 'Primary', render: (domain) => <StatusBadge tone={domain.is_primary ? 'success' : 'default'}>{domain.is_primary ? 'Primary' : 'Secondary'}</StatusBadge> },
            { key: 'verified', header: 'Verified', render: (domain) => <StatusBadge tone={domain.is_verified ? 'success' : 'warning'}>{domain.is_verified ? 'Verified' : 'Pending'}</StatusBadge> },
            { key: 'verified_at', header: 'Verified At', render: (domain) => <span className="text-sm text-stone-700">{formatDateTime(domain.verified_at)}</span> },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Tenant Admin' }, { label: 'Domains' }]}
                description="Tenant domain administration now uses the real tenant-domain endpoint, scoped by the currently selected tenant."
                title="Domains"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Select a tenant and inspect its mapped domains, verification state, and primary-domain designation." title="Domain lookup">
                    {tenantsQuery.isPending ? (
                        <LoadingState lines={3} />
                    ) : tenantsQuery.isError ? (
                        isForbiddenError(tenantsQuery.error) ? (
                            <ProtectedErrorState description={tenantsQuery.error.message} />
                        ) : (
                            <ErrorState description={tenantsQuery.error.message} title="Unable to load tenants" />
                        )
                    ) : tenantsQuery.data.items.length === 0 ? (
                        <EmptyState description="Create a tenant first to inspect domain mappings." title="No tenants available" />
                    ) : (
                        <SearchFilterToolbar
                            filters={
                                <>
                                    <Select className="w-full md:max-w-[16rem]" onChange={(event) => updateParams({ tenant_id: event.target.value })} value={String(selectedTenantId)}>
                                        {tenantsQuery.data.items.map((tenant) => (
                                            <option key={tenant.id} value={tenant.id}>
                                                {tenant.name}
                                            </option>
                                        ))}
                                    </Select>
                                    <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ primary: event.target.value || undefined })} value={primary}>
                                        <option value="">All primary states</option>
                                        <option value="1">Primary</option>
                                        <option value="0">Secondary</option>
                                    </Select>
                                    <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ verified: event.target.value || undefined })} value={verified}>
                                        <option value="">All verification</option>
                                        <option value="1">Verified</option>
                                        <option value="0">Pending</option>
                                    </Select>
                                </>
                            }
                            trailing={<div className="text-sm text-stone-500">{activeTenant ? `${activeTenant.name} | ${activeTenant.domain ?? 'No primary domain'}` : 'No tenant selected'}</div>}
                        />
                    )}
                </TableToolbar>

                {!activeTenant ? (
                    <EmptyState className="m-6" description="Select a tenant to inspect domain mappings." title="No tenant selected" />
                ) : domainsQuery.isPending ? (
                    <LoadingState className="m-6" lines={7} />
                ) : domainsQuery.isError ? (
                    isForbiddenError(domainsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={domainsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={domainsQuery.error.message} title="Unable to load tenant domains" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No domains match the current filters for this tenant." title="No domains found" />}
                        footer={<TablePagination meta={domainsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(domain) => domain.id}
                        rows={domainsQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
