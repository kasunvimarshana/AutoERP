import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Button } from '../../../components/ui/Button';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { useToast } from '../../../app/providers/ToastProvider';
import { useTenant } from '../../auth/context/TenantContext';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { parsePositiveInteger } from '../../shared/utils';
import { useDeleteUser, useUsers } from '../hooks';
import type { UserRecord } from '../types';

export function UsersListPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [deleteTarget, setDeleteTarget] = useState<UserRecord | null>(null);

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const activeFilter = searchParams.get('active') ?? '';

    const usersQuery = useUsers({
        tenant_id: tenantId,
        page,
        per_page: 10,
        first_name: search || undefined,
        active: activeFilter === '' ? undefined : activeFilter === '1',
        sort: '-updated_at',
        include: 'permissions',
    });
    const deleteMutation = useDeleteUser();

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

            if ('search' in updates || 'active' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        const target = deleteTarget;
        await deleteMutation.mutateAsync(target.id);
        setDeleteTarget(null);
        showToast({
            title: 'User deleted',
            description: `${target.full_name ?? target.email ?? `User #${target.id}`} has been removed from the tenant directory.`,
            tone: 'success',
        });
    }

    const columns: DataTableColumn<UserRecord>[] = [
        {
            key: 'name',
            header: 'User',
            render: (user) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/users-access/users/${user.id}`}>
                        {(user.full_name ?? `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim()) || 'Unnamed user'}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{user.email ?? 'No email'}</p>
                </div>
            ),
        },
        {
            key: 'roles',
            header: 'Roles',
            render: (user) => (
                <div className="flex flex-wrap gap-1">
                    {user.roles.length > 0 ? user.roles.slice(0, 2).map((role) => <StatusBadge key={role.id}>{role.name}</StatusBadge>) : <span className="text-sm text-stone-500">No roles</span>}
                </div>
            ),
        },
        { key: 'permissions', header: 'Permission Summary', render: (user) => <span className="text-sm text-stone-700">{user.permissions?.length ?? 0} permissions</span> },
        { key: 'status', header: 'Status', render: (user) => <StatusBadge tone={user.active ? 'success' : 'default'}>{user.active ? 'Active' : 'Inactive'}</StatusBadge> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[13rem]',
            render: (user) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/users-access/users/${user.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            View
                        </Button>
                    </Link>
                    <Link to={`/users-access/users/${user.id}/edit`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            Edit
                        </Button>
                    </Link>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(user)} type="button" variant="secondary">
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
                    <Link to="/users-access/users/new">
                        <Button>Add User</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Users & Access' }, { label: 'Users' }]}
                description="User administration now follows the same CRUD shell pattern as Product, with role badges and permission summaries available directly in the list."
                title="Users"
            />

            <ContentCard className="p-0">
                <TableToolbar
                    actions={<div className="hidden text-xs uppercase tracking-[0.16em] text-stone-400 lg:block">Tenant {tenantId}</div>}
                    description="Search tenant users, review assigned roles, and move into profile or access maintenance flows."
                    title="User directory"
                >
                    <SearchFilterToolbar
                        filters={
                            <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ active: event.target.value || undefined })} value={activeFilter}>
                                <option value="">All statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </Select>
                        }
                        search={
                            <Input
                                className="w-full md:max-w-sm"
                                label={undefined}
                                onChange={(event) => updateParams({ search: event.target.value || undefined })}
                                placeholder="Search first name"
                                value={search}
                            />
                        }
                        trailing={<div className="text-sm text-stone-500">{usersQuery.data?.meta?.total ?? 0} records</div>}
                    />
                </TableToolbar>

                {usersQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : usersQuery.isError ? (
                    isForbiddenError(usersQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={usersQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={usersQuery.error.message} title="Unable to load users" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to="/users-access/users/new">
                                        <Button>Create your first user</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No users match the current filters yet. Add a user or widen the search criteria."
                                title="No users found"
                            />
                        }
                        footer={<TablePagination meta={usersQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(user) => user.id}
                        rows={usersQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete user"
                description={deleteTarget ? `Delete ${deleteTarget.full_name ?? deleteTarget.email ?? `user #${deleteTarget.id}`}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete user"
            />
        </div>
    );
}
