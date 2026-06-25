import { useCallback, useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { humanize, readableRelation } from '@/shared/utils/object';
import { useAuth } from '@/modules/auth/AuthProvider';
import { formatLocationLabel, formatWarehouseLabel } from './components/WarehouseLocationForm';
import { listWarehouseLocations, searchWarehouseLocationOptions, searchWarehouseOptions, setWarehouseLocationActive } from './warehouseApi';
import { hasWarehousePermission, warehousePermissions } from './warehousePermissions';
import { warehouseLocationTypes, type WarehouseLocationSummary, type WarehouseSummary } from './warehouseTypes';

export default function WarehouseLocationListPage() {
    const auth = useAuth();
    const canCreate = hasWarehousePermission(auth.permissions, warehousePermissions.locationsCreate);
    const canUpdate = hasWarehousePermission(auth.permissions, warehousePermissions.locationsUpdate);
    const canActivate = hasWarehousePermission(auth.permissions, warehousePermissions.locationsActivate);
    const canDeactivate = hasWarehousePermission(auth.permissions, warehousePermissions.locationsDeactivate);
    const [search, setSearch] = useState('');
    const [warehouse, setWarehouse] = useState<WarehouseSummary | null>(null);
    const [parent, setParent] = useState<WarehouseLocationSummary | null>(null);
    const [type, setType] = useState('');
    const [active, setActive] = useState('');
    const [isDefault, setIsDefault] = useState('');
    const [organizationUnitId, setOrganizationUnitId] = useState('');
    const [page, setPage] = useState(1);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debouncedSearch = useDebounce(search);
    const parentSearch = useCallback(
        (params: Parameters<typeof searchWarehouseLocationOptions>[0]) => searchWarehouseLocationOptions(params, warehouse?.id ?? null),
        [warehouse?.id],
    );
    const result = useApi((signal) => listWarehouseLocations({
        search: debouncedSearch || undefined,
        warehouse_id: warehouse?.id,
        parent_id: parent?.id,
        type: type || undefined,
        is_active: active === '' ? undefined : active === 'true',
        is_default: isDefault === '' ? undefined : isDefault === 'true',
        organization_unit_filter_id: organizationUnitId ? Number(organizationUnitId) : undefined,
        page,
        per_page: 25,
    }, signal), [active, debouncedSearch, isDefault, organizationUnitId, page, parent?.id, type, warehouse?.id]);

    const columns: DataColumn<WarehouseLocationSummary>[] = [
        { key: 'code', header: 'Code', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/warehouse-locations/${row.id}`}>{row.code || '-'}</Link> },
        { key: 'name', header: 'Name', render: (row) => row.name },
        { key: 'warehouse', header: 'Warehouse', render: (row) => readableRelation(row.warehouse) },
        { key: 'path', header: 'Parent / Path', render: (row) => row.parent ? `${readableRelation(row.parent)} / ${row.path ?? '-'}` : row.path ?? '-' },
        { key: 'type', header: 'Type', render: (row) => row.type_label ?? humanize(row.type) },
        { key: 'default', header: 'Default', render: (row) => row.is_default ? <StatusBadge status="default" /> : '-' },
        { key: 'pickable', header: 'Pickable', render: (row) => row.is_pickable ? 'Yes' : 'No' },
        { key: 'receivable', header: 'Receivable', render: (row) => row.is_receivable ? 'Yes' : 'No' },
        { key: 'active', header: 'Active', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => (
            <div className="flex justify-end gap-3">
                {canUpdate && <Link className="font-semibold text-slate-600 hover:text-sky-700" to={`/warehouse-locations/${row.id}/edit`}>Edit</Link>}
                {row.is_active && canDeactivate && <button type="button" className="font-semibold text-amber-700" onClick={() => void toggle(row)}>Deactivate</button>}
                {!row.is_active && canActivate && <button type="button" className="font-semibold text-emerald-700" onClick={() => void toggle(row)}>Activate</button>}
            </div>
        ) },
    ];

    async function toggle(location: WarehouseLocationSummary) {
        setActionError(null);
        try {
            await setWarehouseLocationActive(Number(location.id), !location.is_active);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    }

    return (
        <>
            <ContentHeader title="Warehouse Locations" description="Location hierarchy, capabilities, defaults, and status." actions={canCreate ? <LinkButton to="/warehouse-locations/create">Create Location</LinkButton> : undefined} />
            <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <Input type="search" label="Search" placeholder="Code, name, or path" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <GenericLookupSelect
                    label="Warehouse"
                    value={warehouse}
                    onChange={(next) => {
                        setWarehouse(next);
                        setParent(null);
                        setPage(1);
                    }}
                    search={searchWarehouseOptions}
                    formatLabel={formatWarehouseLabel}
                    loadOnOpen
                    minSearchLength={0}
                />
                <GenericLookupSelect
                    label="Parent Location"
                    value={parent}
                    onChange={(next) => {
                        setParent(next);
                        setPage(1);
                    }}
                    search={parentSearch}
                    formatLabel={formatLocationLabel}
                    disabled={!warehouse}
                    loadOnOpen
                    minSearchLength={0}
                />
                {auth.organizationUnit && (
                    <Select
                        label="Organization Unit"
                        value={organizationUnitId}
                        placeholder="Visible scope"
                        options={[{ value: String(auth.organizationUnit.id), label: auth.organizationUnit.name ?? `Organization ${auth.organizationUnit.id}` }]}
                        onChange={(event) => { setOrganizationUnitId(event.target.value); setPage(1); }}
                    />
                )}
                <Select label="Type" value={type} onChange={(event) => { setType(event.target.value); setPage(1); }} options={warehouseLocationTypes.map((entry) => ({ value: entry, label: humanize(entry) }))} placeholder="All types" />
                <Select label="Default" value={isDefault} onChange={(event) => { setIsDefault(event.target.value); setPage(1); }} options={[{ value: 'true', label: 'Default' }, { value: 'false', label: 'Not default' }]} placeholder="Any default" />
                <Select label="Status" value={active} onChange={(event) => { setActive(event.target.value); setPage(1); }} options={[{ value: 'true', label: 'Active' }, { value: 'false', label: 'Inactive' }]} placeholder="Any status" />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} rowHref={(row) => `/warehouse-locations/${row.id}`} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
