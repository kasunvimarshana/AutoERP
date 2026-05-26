import { useTenant } from '../../auth/context/TenantContext';
import { usePermissions } from '../hooks';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { DataTable, type DataTableColumn } from '../../../components/tables';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import type { PermissionRecord } from '../types';

export function PermissionsPage() {
    const { tenantId } = useTenant();
    const permissionsQuery = usePermissions({ tenant_id: tenantId, per_page: 100, page: 1 });

    const columns: DataTableColumn<PermissionRecord>[] = [
        { key: 'id', header: 'ID', render: (permission) => <span className="text-sm text-stone-700">#{permission.id}</span> },
        { key: 'name', header: 'Permission', render: (permission) => <span className="font-medium text-stone-950">{permission.name}</span> },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Users & Access' }, { label: 'Permissions' }]}
                description="Permission definitions are exposed in a read-focused screen so access reviews can happen without drifting into unsupported maintenance flows."
                title="Permissions"
            />

            <ContentCard className="p-0">
                {permissionsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : permissionsQuery.isError ? (
                    isForbiddenError(permissionsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={permissionsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={permissionsQuery.error.message} title="Unable to load permissions" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No permissions are available for this tenant." title="No permissions found" />}
                        getRowKey={(permission) => permission.id}
                        rows={permissionsQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
