import { useState } from 'react';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useAuth } from '@/modules/auth/AuthProvider';
import { accessApi, type AccessUser } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';

export default function UserListPage() {
    const auth = useAuth();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [roleId, setRoleId] = useState('');
    const [organizationUnitId, setOrganizationUnitId] = useState('');
    const [page, setPage] = useState(1);
    const debouncedSearch = useDebounce(search);
    const canCreate = hasAccessPermission(auth, accessPermissions.usersCreate);
    const canManage = [
        accessPermissions.usersUpdate,
        accessPermissions.usersAssignRoles,
        accessPermissions.usersAssignPermissions,
        accessPermissions.usersManageOrganizationAccess,
    ].some((permission) => hasAccessPermission(auth, permission));

    const users = useApi((signal) => accessApi.listUsers({
        search: debouncedSearch || undefined,
        status: status || undefined,
        role_id: roleId ? Number(roleId) : undefined,
        organization_unit_filter_id: organizationUnitId ? Number(organizationUnitId) : undefined,
        page,
        per_page: 25,
    }, signal), [debouncedSearch, status, roleId, organizationUnitId, page]);
    const roles = useApi((signal) => accessApi.listAllRoles(signal), []);
    const organizationUnits = useApi(
        (signal) => accessApi.listAllOrganizationUnits(signal),
        [],
    );

    const columns: DataColumn<AccessUser>[] = [
        {
            key: 'name',
            header: 'User',
            render: (row) => (
                <div>
                    <p className="font-semibold text-slate-900">{row.name ?? [row.first_name, row.last_name].filter(Boolean).join(' ')}</p>
                    <p className="text-xs text-slate-500">{row.email}</p>
                </div>
            ),
        },
        { key: 'username', header: 'Username', render: (row) => row.username || '-' },
        { key: 'roles', header: 'Roles', render: (row) => labelList(row.roles?.map((role) => role.name) ?? []) },
        {
            key: 'organization_units',
            header: 'Organization access',
            render: (row) => labelList(row.organization_units?.map((unit) => unit.name) ?? []),
        },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        {
            key: 'readiness',
            header: 'Credential setup',
            render: (row) => row.credentials_ready ? 'Completed' : 'Pending',
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <LinkButton variant="secondary" className="min-h-8 px-3 py-1 text-xs" to={`/access/users/${row.id}`}>View</LinkButton>
                    {canManage && <LinkButton variant="secondary" className="min-h-8 px-3 py-1 text-xs" to={`/access/users/${row.id}/edit`}>Manage</LinkButton>}
                </div>
            ),
        },
    ];

    return (
        <>
            <ContentHeader
                title="Users"
                description="Search tenant users and open a guided account-management workflow."
                actions={canCreate ? <LinkButton to="/access/users/create">Invite User</LinkButton> : null}
            />
            <div className="mb-5 grid gap-4 lg:grid-cols-4">
                <Input label="Search" type="search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} placeholder="All statuses" options={statusOptions} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                <Select label="Role" value={roleId} placeholder="All roles" options={(roles.data ?? []).map((role) => ({ value: String(role.id), label: role.name }))} onChange={(event) => { setRoleId(event.target.value); setPage(1); }} />
                <Select label="Organization unit" value={organizationUnitId} placeholder="All organization units" options={(organizationUnits.data ?? []).map((unit) => ({ value: String(unit.id), label: unit.code ? `${unit.name} (${unit.code})` : unit.name }))} onChange={(event) => { setOrganizationUnitId(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={users.error ?? roles.error ?? organizationUnits.error} />
            {users.loading ? <LoadingState label="Loading users..." /> : (
                <DataTable
                    rows={users.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    rowHref={(row) => `/access/users/${row.id}`}
                    emptyMessage="No users match the current filters."
                />
            )}
            <Pagination meta={users.data?.meta} onPageChange={setPage} />
        </>
    );
}

function labelList(values: string[]): string {
    return values.length > 0 ? values.join(', ') : '-';
}

const statusOptions = [
    { value: 'invited', label: 'Invited' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'suspended', label: 'Suspended' },
];
