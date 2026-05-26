import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { JsonPreview } from '../../../components/ui/JsonPreview';
import { useTenant } from '../../auth/context/TenantContext';
import { useTenantSettings, useTenants } from '../../tenant-admin/hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDateTime, parseBooleanSearchParam } from '../../shared/utils';
import type { TenantSettingRecord } from '../../tenant-admin/types';

export function PreferencesSettingsPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const selectedTenantId = Number(searchParams.get('tenant_id') ?? tenantId);
    const visibility = searchParams.get('visibility') ?? '';

    const tenantsQuery = useTenants({ page: 1, per_page: 100, sort: 'name' });
    const activeTenant = tenantsQuery.data?.items.find((tenant) => tenant.id === selectedTenantId) ?? null;
    const settingsQuery = useTenantSettings(
        selectedTenantId,
        { group: 'preferences', per_page: 100, is_public: parseBooleanSearchParam(visibility) },
        selectedTenantId > 0,
    );

    const columns: DataTableColumn<TenantSettingRecord>[] = [
        {
            key: 'key',
            header: 'Preference',
            render: (setting) => (
                <div>
                    <p className="font-medium text-stone-950">{setting.key}</p>
                    <p className="mt-1 text-xs text-stone-500">{setting.group || 'Ungrouped'}</p>
                </div>
            ),
        },
        { key: 'is_public', header: 'Visibility', render: (setting) => <StatusBadge tone={setting.is_public ? 'success' : 'default'}>{setting.is_public ? 'Public' : 'Private'}</StatusBadge> },
        { key: 'updated_at', header: 'Updated', render: (setting) => <span className="text-sm text-stone-700">{formatDateTime(setting.updated_at)}</span> },
        { key: 'value', header: 'Value', render: (setting) => <div className="max-w-md"><JsonPreview className="max-h-40" value={setting.value} /></div> },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Settings' }, { label: 'Preferences' }]}
                description="Preference settings now use tenant-scoped setting records so the route reflects saved workspace configuration instead of placeholder copy."
                title="Preferences"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Inspect tenant preference defaults and visibility settings already stored in the backend settings registry." title="Preference setting registry">
                    {tenantsQuery.isPending ? (
                        <LoadingState lines={3} />
                    ) : tenantsQuery.isError ? (
                        isForbiddenError(tenantsQuery.error) ? (
                            <ProtectedErrorState description={tenantsQuery.error.message} />
                        ) : (
                            <ErrorState description={tenantsQuery.error.message} title="Unable to load tenants" />
                        )
                    ) : tenantsQuery.data.items.length === 0 ? (
                        <EmptyState description="A tenant is required before preference settings can be displayed." title="No tenants available" />
                    ) : (
                        <SearchFilterToolbar
                            filters={
                                <>
                                    <Select className="w-full md:max-w-[16rem]" onChange={(event) => setSearchParams({ tenant_id: event.target.value, visibility })} value={String(selectedTenantId)}>
                                        {tenantsQuery.data.items.map((tenant) => (
                                            <option key={tenant.id} value={tenant.id}>
                                                {tenant.name}
                                            </option>
                                        ))}
                                    </Select>
                                    <Select className="w-full md:max-w-[12rem]" onChange={(event) => setSearchParams({ tenant_id: String(selectedTenantId), visibility: event.target.value })} value={visibility}>
                                        <option value="">All visibility</option>
                                        <option value="1">Public</option>
                                        <option value="0">Private</option>
                                    </Select>
                                </>
                            }
                            trailing={<div className="text-sm text-stone-500">{activeTenant ? `${activeTenant.name} | ${settingsQuery.data?.items.length ?? 0} settings` : 'No tenant selected'}</div>}
                        />
                    )}
                </TableToolbar>

                {!activeTenant ? (
                    <EmptyState className="m-6" description="Select a tenant to inspect preference settings." title="No tenant selected" />
                ) : settingsQuery.isPending ? (
                    <LoadingState className="m-6" lines={6} />
                ) : settingsQuery.isError ? (
                    isForbiddenError(settingsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={settingsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={settingsQuery.error.message} title="Unable to load preference settings" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No preference settings match the current filters for this tenant." title="No preference settings found" />}
                        getRowKey={(setting) => setting.id}
                        rows={settingsQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
