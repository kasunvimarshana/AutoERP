import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useAuth } from '@/modules/auth/AuthProvider';
import { accessApi, type AccessRole } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';

export default function RoleListPage() {
    const auth = useAuth();
    const { confirm, confirmDialog } = useConfirmDialog();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debouncedSearch = useDebounce(search);
    const canCreate = hasAccessPermission(auth, accessPermissions.rolesCreate);
    const canUpdate = hasAccessPermission(auth, accessPermissions.rolesUpdate);
    const canDelete = hasAccessPermission(auth, accessPermissions.rolesDelete);
    const roles = useApi((signal) => accessApi.listRoles({
        search: debouncedSearch || undefined,
        page,
        per_page: 25,
    }, signal), [debouncedSearch, page]);

    const deleteRole = async (role: AccessRole) => {
        if (!await confirm({ title: 'Delete role', message: `Delete the role “${role.name}”? This cannot be undone.`, confirmLabel: 'Delete role' })) return;
        setDeletingId(role.id);
        setActionError(null);
        try {
            await accessApi.deleteRole(role.id, role.row_version);
            roles.reload();
        } catch (caught) {
            setActionError(toApiError(caught));
        } finally {
            setDeletingId(null);
        }
    };

    const columns: DataColumn<AccessRole>[] = [
        { key: 'name', header: 'Name', render: (row) => <span className="font-semibold text-slate-900">{row.name}</span> },
        { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
        { key: 'users', header: 'Assigned Users', render: (row) => row.assigned_users_count ?? 0 },
        { key: 'permissions', header: 'Permissions', render: (row) => row.permissions_count ?? 0 },
        { key: 'type', header: 'Type', render: (row) => <StatusBadge status={row.is_system ? 'system' : 'custom'} /> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <LinkButton variant="secondary" className="min-h-8 px-3 py-1 text-xs" to={`/access/roles/${row.id}`}>View</LinkButton>
                    {canUpdate && !row.is_system && <LinkButton variant="secondary" className="min-h-8 px-3 py-1 text-xs" to={`/access/roles/${row.id}/edit`}>Edit</LinkButton>}
                    {canDelete && !row.is_system && (
                        <Button variant="danger" className="min-h-8 px-3 py-1 text-xs" loading={deletingId === row.id} onClick={() => void deleteRole(row)}>Delete</Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <ContentHeader
                title="Roles"
                description="Manage tenant roles and their permission assignments."
                actions={canCreate ? <LinkButton to="/access/roles/create">Add Role</LinkButton> : null}
            />
            <div className="mb-5 max-w-md">
                <Input label="Search" type="search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? roles.error} />
            {roles.loading ? <LoadingState label="Loading roles..." /> : (
                <DataTable
                    rows={roles.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    rowHref={(row) => `/access/roles/${row.id}`}
                    emptyMessage="No roles match the current search."
                />
            )}
            <Pagination meta={roles.data?.meta} onPageChange={setPage} />
            {confirmDialog}
        </>
    );
}
