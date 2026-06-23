import { useMemo, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { LinkButton, Button } from '@/shared/components/Button';
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
import { formatDate } from '@/shared/utils/formatDate';
import { useAuth } from '@/modules/auth/AuthProvider';
import { accessApi, type AccessUser } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';

export default function UserListPage() {
    const auth = useAuth();
    const tenantId = Number(auth.tenant?.id);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [roleId, setRoleId] = useState('');
    const [organizationUnitId, setOrganizationUnitId] = useState('');
    const [page, setPage] = useState(1);
    const [rowLoading, setRowLoading] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debouncedSearch = useDebounce(search);
    const canCreate = hasAccessPermission(auth, accessPermissions.usersCreate);
    const canUpdate = hasAccessPermission(auth, accessPermissions.usersUpdate);
    const canActivate = hasAccessPermission(auth, accessPermissions.usersActivate);
    const canDeactivate = hasAccessPermission(auth, accessPermissions.usersDeactivate);

    const users = useApi((signal) => accessApi.listUsers({
        search: debouncedSearch || undefined,
        status: status || undefined,
        role_id: roleId ? Number(roleId) : undefined,
        organization_unit_filter_id: organizationUnitId ? Number(organizationUnitId) : undefined,
        page,
        per_page: 25,
    }, signal), [debouncedSearch, status, roleId, organizationUnitId, page]);
    const roles = useApi((signal) => accessApi.listRoles({ per_page: 100 }, signal), []);
    const organizationUnits = useApi((signal) => accessApi.listOrganizationUnits({ tenant_id: tenantId }, signal), [tenantId], Number.isFinite(tenantId) && tenantId > 0);

    const changeStatus = async (user: AccessUser) => {
        setRowLoading(user.id);
        setActionError(null);
        try {
            if (user.status === 'active') await accessApi.deactivateUser(user.id);
            else await accessApi.activateUser(user.id);
            users.reload();
        } catch (caught) {
            setActionError(toApiError(caught));
        } finally {
            setRowLoading(null);
        }
    };

    const columns = useMemo<DataColumn<AccessUser>[]>(() => [
        { key: 'name', header: 'Name', render: (row) => <div><p className="font-semibold text-slate-900">{row.name ?? [row.first_name, row.last_name].filter(Boolean).join(' ')}</p><p className="text-xs text-slate-500">{row.email}</p></div> },
        { key: 'username', header: 'Username or Email', render: (row) => row.username || row.email },
        { key: 'roles', header: 'Roles', render: (row) => labelList(row.roles?.map((role) => role.name) ?? []) },
        { key: 'organization_units', header: 'Organization Unit(s)', render: (row) => labelList(row.organization_units?.map((unit) => unit.name) ?? []) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'last_login', header: 'Last Login', render: (row) => formatDate(row.last_login_at) },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <LinkButton variant="secondary" className="min-h-8 px-3 py-1 text-xs" to={`/access/users/${row.id}`}>View</LinkButton>
                    {canUpdate && <LinkButton variant="secondary" className="min-h-8 px-3 py-1 text-xs" to={`/access/users/${row.id}/edit`}>Edit</LinkButton>}
                    {((row.status === 'active' && canDeactivate) || (row.status !== 'active' && canActivate)) && (
                        <Button
                            variant={row.status === 'active' ? 'danger' : 'secondary'}
                            className="min-h-8 px-3 py-1 text-xs"
                            loading={rowLoading === row.id}
                            onClick={() => void changeStatus(row)}
                        >
                            {row.status === 'active' ? 'Deactivate' : 'Activate'}
                        </Button>
                    )}
                </div>
            ),
        },
    ], [canActivate, canDeactivate, canUpdate, rowLoading]);

    return (
        <>
            <ContentHeader
                title="User List"
                description="Search, filter, and manage tenant user accounts."
                actions={canCreate ? <LinkButton to="/access/users/create">Add User</LinkButton> : null}
            />
            <div className="mb-5 grid gap-4 lg:grid-cols-4">
                <Input label="Search" type="search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} placeholder="All statuses" options={statusOptions} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                <Select label="Role" value={roleId} placeholder="All roles" options={(roles.data?.data ?? []).map((role) => ({ value: String(role.id), label: role.name }))} onChange={(event) => { setRoleId(event.target.value); setPage(1); }} />
                <Select label="Organization Unit" value={organizationUnitId} placeholder="All organization units" options={(organizationUnits.data?.data ?? []).map((unit) => ({ value: String(unit.id), label: unit.name }))} onChange={(event) => { setOrganizationUnitId(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? users.error ?? roles.error ?? organizationUnits.error} />
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
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'suspended', label: 'Suspended' },
];
