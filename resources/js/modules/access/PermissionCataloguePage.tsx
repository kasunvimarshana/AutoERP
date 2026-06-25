import { useMemo, useState } from 'react';
import { ApiError } from '@/shared/api/apiError';
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
import { accessApi, type AccessPermission } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';

export default function PermissionCataloguePage() {
    const auth = useAuth();
    const [search, setSearch] = useState('');
    const [module, setModule] = useState('');
    const [page, setPage] = useState(1);
    const debouncedSearch = useDebounce(search);
    const canView = hasAccessPermission(auth, accessPermissions.permissionsView);
    const permissions = useApi((signal) => accessApi.listPermissions({
        search: debouncedSearch || undefined,
        module: module || undefined,
        page,
        per_page: 25,
    }, signal), [debouncedSearch, module, page], canView);
    const moduleLookup = useApi((signal) => accessApi.listPermissions({ per_page: 100 }, signal), [], canView);

    const moduleOptions = useMemo(() => {
        const modules = new Set<string>();
        (moduleLookup.data?.data ?? []).forEach((permission) => {
            if (permission.module) modules.add(permission.module);
        });
        return [...modules].sort((left, right) => left.localeCompare(right)).map((value) => ({
            value,
            label: value,
        }));
    }, [moduleLookup.data]);

    const columns = useMemo<DataColumn<AccessPermission>[]>(() => [
        { key: 'module', header: 'Module', render: (row) => row.module ?? '-' },
        { key: 'resource', header: 'Resource', render: (row) => row.resource ?? '-' },
        { key: 'action', header: 'Action', render: (row) => row.action ?? '-' },
        { key: 'code', header: 'Permission code', render: (row) => <span className="font-mono text-xs text-slate-600">{row.name}</span> },
        { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status ?? 'system_defined'} /> },
    ], []);

    const permissionError = canView ? permissions.error ?? moduleLookup.error : new ApiError('You do not have permission to view the permission catalogue.', 403);

    return (
        <>
            <ContentHeader
                title="Permissions"
                description="Read-only catalogue of system-defined authorization permissions."
            />
            <div className="mb-5 grid gap-4 md:grid-cols-[minmax(0,1fr)_18rem]">
                <Input label="Search" type="search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Module" value={module} placeholder="All modules" options={moduleOptions} onChange={(event) => { setModule(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={permissionError} />
            {canView && (permissions.loading ? <LoadingState label="Loading permissions..." /> : (
                <DataTable
                    rows={permissions.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    emptyMessage="No permissions match the current filters."
                />
            ))}
            {canView && <Pagination meta={permissions.data?.meta} onPageChange={setPage} />}
        </>
    );
}
