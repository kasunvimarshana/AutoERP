import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
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
import { humanize, readableRelation } from '@/shared/utils/object';
import { useAuth } from '@/modules/auth/AuthProvider';
import { listWarehouses, setWarehouseActive } from './warehouseApi';
import { hasWarehousePermission, warehousePermissions } from './warehousePermissions';
import { warehouseTypes, type WarehouseSummary } from './warehouseTypes';

export default function WarehouseListPage() {
    const auth = useAuth();
    const canCreate = hasWarehousePermission(auth.permissions, warehousePermissions.warehousesCreate);
    const canUpdate = hasWarehousePermission(auth.permissions, warehousePermissions.warehousesUpdate);
    const canActivate = hasWarehousePermission(auth.permissions, warehousePermissions.warehousesActivate);
    const canDeactivate = hasWarehousePermission(auth.permissions, warehousePermissions.warehousesDeactivate);
    const [search, setSearch] = useState('');
    const [type, setType] = useState('');
    const [active, setActive] = useState('');
    const [isDefault, setIsDefault] = useState('');
    const [organizationUnitId, setOrganizationUnitId] = useState('');
    const [page, setPage] = useState(1);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debouncedSearch = useDebounce(search);
    const result = useApi((signal) => listWarehouses({
        search: debouncedSearch || undefined,
        type: type || undefined,
        is_active: active === '' ? undefined : active === 'true',
        is_default: isDefault === '' ? undefined : isDefault === 'true',
        organization_unit_filter_id: organizationUnitId ? Number(organizationUnitId) : undefined,
        page,
        per_page: 25,
    }, signal), [active, debouncedSearch, isDefault, organizationUnitId, page, type]);

    const columns: DataColumn<WarehouseSummary>[] = [
        { key: 'code', header: 'Code', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/warehouses/${row.id}`}>{row.code || '-'}</Link> },
        { key: 'name', header: 'Name', render: (row) => row.name },
        { key: 'organization', header: 'Organization Unit', render: (row) => readableRelation(row.organization_unit) },
        { key: 'type', header: 'Type', render: (row) => row.type_label ?? humanize(row.type) },
        { key: 'default', header: 'Default', render: (row) => row.is_default ? <StatusBadge status="default" /> : '-' },
        { key: 'active', header: 'Active', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        { key: 'locations', header: 'Locations', render: (row) => row.locations_count ?? 0 },
        { key: 'actions', header: '', className: 'text-right', render: (row) => (
            <div className="flex justify-end gap-3">
                {canUpdate && <Link className="font-semibold text-slate-600 hover:text-sky-700" to={`/warehouses/${row.id}/edit`}>Edit</Link>}
                {row.is_active && canDeactivate && <button type="button" className="font-semibold text-amber-700" onClick={() => void toggle(row)}>Deactivate</button>}
                {!row.is_active && canActivate && <button type="button" className="font-semibold text-emerald-700" onClick={() => void toggle(row)}>Activate</button>}
            </div>
        ) },
    ];

    async function toggle(warehouse: WarehouseSummary) {
        setActionError(null);
        try {
            await setWarehouseActive(Number(warehouse.id), !warehouse.is_active);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    }

    return (
        <>
            <ContentHeader title="Warehouses" description="Warehouse scopes, defaults, and operational status." actions={canCreate ? <LinkButton to="/warehouses/create">Create Warehouse</LinkButton> : undefined} />
            <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <Input type="search" label="Search" placeholder="Code or name" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                {auth.organizationUnit && (
                    <Select
                        label="Organization Unit"
                        value={organizationUnitId}
                        placeholder="Visible scope"
                        options={[{ value: String(auth.organizationUnit.id), label: auth.organizationUnit.name ?? `Organization ${auth.organizationUnit.id}` }]}
                        onChange={(event) => { setOrganizationUnitId(event.target.value); setPage(1); }}
                    />
                )}
                <Select label="Type" value={type} onChange={(event) => { setType(event.target.value); setPage(1); }} options={warehouseTypes.map((entry) => ({ value: entry, label: humanize(entry) }))} placeholder="All types" />
                <Select label="Default" value={isDefault} onChange={(event) => { setIsDefault(event.target.value); setPage(1); }} options={[{ value: 'true', label: 'Default' }, { value: 'false', label: 'Not default' }]} placeholder="Any default" />
                <Select label="Status" value={active} onChange={(event) => { setActive(event.target.value); setPage(1); }} options={[{ value: 'true', label: 'Active' }, { value: 'false', label: 'Inactive' }]} placeholder="Any status" />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} rowHref={(row) => `/warehouses/${row.id}`} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
