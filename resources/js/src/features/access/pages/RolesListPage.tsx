import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Button } from '../../../components/ui/Button';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { DataTable, type DataTableColumn } from '../../../components/tables';
import { useToast } from '../../../app/providers/ToastProvider';
import { useTenant } from '../../auth/context/TenantContext';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { useDeleteRole, useRoles } from '../hooks';
import type { RoleRecord } from '../types';

export function RolesListPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [deleteTarget, setDeleteTarget] = useState<RoleRecord | null>(null);
    const rolesQuery = useRoles({ tenant_id: tenantId, per_page: 100, page: 1 });
    const deleteMutation = useDeleteRole();

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        const target = deleteTarget;
        await deleteMutation.mutateAsync(target.id);
        setDeleteTarget(null);
        showToast({
            title: 'Role deleted',
            description: `${target.name} has been removed from the role catalog.`,
            tone: 'success',
        });
    }

    const columns: DataTableColumn<RoleRecord>[] = [
        { key: 'name', header: 'Role', render: (role) => <span className="font-medium text-stone-950">{role.name}</span> },
        { key: 'permissions', header: 'Permission Count', render: (role) => <span className="text-sm text-stone-700">{role.permissions.length}</span> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[13rem]',
            render: (role) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/users-access/roles/${role.id}/edit`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            Edit
                        </Button>
                    </Link>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(role)} type="button" variant="secondary">
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
                    <Link to="/users-access/roles/new">
                        <Button>Add Role</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Users & Access' }, { label: 'Roles' }]}
                description="Roles expose permission bundle summaries and feed directly into the user-management screens in this phase."
                title="Roles"
            />

            <ContentCard className="p-0">
                {rolesQuery.isPending ? (
                    <LoadingState className="m-6" lines={6} />
                ) : rolesQuery.isError ? (
                    isForbiddenError(rolesQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={rolesQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={rolesQuery.error.message} title="Unable to load roles" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to="/users-access/roles/new">
                                        <Button>Create your first role</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No roles are available for this tenant yet."
                                title="No roles found"
                            />
                        }
                        getRowKey={(role) => role.id}
                        rows={rolesQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete role"
                description={deleteTarget ? `Delete ${deleteTarget.name}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete role"
            />
        </div>
    );
}
