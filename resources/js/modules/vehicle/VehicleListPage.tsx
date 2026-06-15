import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { PaginationMeta } from '@/shared/types/pagination';
import { listVehicles, setVehicleActive } from './vehicleApi';
import type { VehicleSummary } from './vehicleTypes';
import { VehicleCategorySelect } from './components/VehicleCategorySelect';
import { VehicleMakeSelect } from './components/VehicleMakeSelect';
import { VehicleModelSelect } from './components/VehicleModelSelect';
import { VehicleTypeSelect } from './components/VehicleTypeSelect';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { lookupApi } from '@/shared/api/lookupApi';
import type { NamedResource } from '@/shared/types/common';
import type { VehicleCategory, VehicleMake, VehicleModel, VehicleOwnerType, VehicleScope, VehicleType } from './vehicleTypes';
import { Tabs } from '@/shared/components/Tabs';
import { searchSuppliers } from '@/modules/supplier/supplierApi';

const statuses = ['', 'active', 'inactive', 'under_service', 'rented', 'reserved', 'sold', 'blocked', 'scrapped'];
const scopes = [
    { id: 'all' as VehicleScope, label: 'All Vehicles' },
    { id: 'fleet' as VehicleScope, label: 'Fleet Vehicles' },
    { id: 'customer' as VehicleScope, label: 'Customer Vehicles' },
    { id: 'supplier_owner' as VehicleScope, label: 'Supplier / Owner Vehicles' },
];
const ownerTypes: Array<{ value: VehicleOwnerType | ''; label: string }> = [
    { value: '', label: 'All owners' },
    { value: 'company', label: 'Company' },
    { value: 'customer', label: 'Customer' },
    { value: 'supplier', label: 'Supplier' },
    { value: 'third_party', label: 'Third party' },
];

export default function VehicleListPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const requestedScope = searchParams.get('scope');
    const scope = scopes.some((item) => item.id === requestedScope) ? requestedScope as VehicleScope : 'all';
    const contextSearch = searchParams.toString() || `scope=${scope}`;
    const [rows, setRows] = useState<VehicleSummary[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [make, setMake] = useState<VehicleMake | null>(null);
    const [model, setModel] = useState<VehicleModel | null>(null);
    const [type, setType] = useState<VehicleType | null>(null);
    const [category, setCategory] = useState<VehicleCategory | null>(null);
    const [ownerType, setOwnerType] = useState<VehicleOwnerType | ''>('');
    const [owner, setOwner] = useState<NamedResource | null>(null);
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const debouncedSearch = useDebounce(search);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        listVehicles({
            search: debouncedSearch,
            status: status || undefined,
            vehicle_make_id: make?.id,
            vehicle_model_id: model?.id,
            vehicle_type_id: type?.id,
            vehicle_category_id: category?.id,
            scope,
            owner_type: ownerType || undefined,
            owner_id: owner?.id,
            page,
            per_page: 25,
        }, controller.signal)
            .then((response) => {
                setRows(response.data);
                setMeta(response.meta);
                setError(null);
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [category, debouncedSearch, make, model, owner, ownerType, page, scope, status, type]);

    useEffect(() => {
        setOwnerType(scope === 'customer' ? 'customer' : '');
        setOwner(null);
        setPage(1);
    }, [scope]);

    const refreshStatus = (vehicle: VehicleSummary, active: boolean) => {
        setVehicleActive(vehicle.id, active)
            .then((updated) => setRows((current) => current.map((row) => row.id === vehicle.id ? { ...row, status: updated.status } : row)))
            .catch((requestError) => setError(toApiError(requestError)));
    };

    return (
        <div>
            <ContentHeader
                title={scopes.find((item) => item.id === scope)?.label ?? 'Vehicles'}
                description="One vehicle master with effective ownership history and server-side scope filters."
                actions={<Link to={`/vehicles/create?${contextSearch}`}><Button>Create Vehicle</Button></Link>}
            />
            <div className="mb-5 overflow-x-auto">
                <Tabs<VehicleScope>
                    tabs={scopes}
                    active={scope}
                    onChange={(next) => setSearchParams({ scope: next })}
                />
            </div>
            <div className="mb-4 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Number, registration, chassis, engine, VIN" />
                <Select label="Status" value={status} options={statuses.filter(Boolean).map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                <VehicleMakeSelect value={make} onChange={(value) => { setMake(value); setModel(null); setPage(1); }} />
                <VehicleModelSelect makeId={make?.id} value={model} onChange={(value) => { setModel(value); setPage(1); }} />
                <VehicleTypeSelect value={type} onChange={(value) => { setType(value); setPage(1); }} />
                <VehicleCategorySelect value={category} onChange={(value) => { setCategory(value); setPage(1); }} />
                <Select
                    label="Owner Type"
                    value={ownerType}
                    options={ownerTypes}
                    onChange={(event) => {
                        setOwnerType(event.target.value as VehicleOwnerType | '');
                        setOwner(null);
                        setPage(1);
                    }}
                />
                {ownerType !== '' && ownerType !== 'company' && (
                    <LookupSelect
                        label={ownerType === 'customer' ? 'Customer' : 'Supplier / Owner'}
                        value={owner}
                        onChange={(value) => { setOwner(value); setPage(1); }}
                        search={ownerType === 'customer' ? lookupApi.customers : searchSuppliers}
                    />
                )}
            </div>
            <ErrorAlert error={error} />
            {loading ? <LoadingState label="Loading vehicles..." /> : (
                <>
                    <DataTable
                        rows={rows}
                        rowKey={(row) => row.id}
                        columns={[
                            { key: 'number', header: 'Vehicle', render: (row) => <div><p className="font-medium text-slate-900">{row.vehicle_number}</p><p className="text-xs text-slate-500">{row.registration_number ?? row.code ?? '-'}</p></div> },
                            { key: 'make', header: 'Make / Model', render: (row) => `${row.make?.name ?? '-'} / ${row.model?.name ?? '-'}` },
                            { key: 'type', header: 'Type', render: (row) => row.type?.name ?? '-' },
                            { key: 'owner', header: 'Owner', render: (row) => row.current_ownership?.owner?.name ?? '-' },
                            { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
                            { key: 'actions', header: '', render: (row) => <div className="flex justify-end gap-2"><Link to={`/vehicles/${row.id}?${contextSearch}`}><Button variant="ghost">View</Button></Link><Link to={`/vehicles/${row.id}/edit?${contextSearch}`}><Button variant="ghost">Edit</Button></Link><Button variant="secondary" onClick={() => refreshStatus(row, row.status !== 'active')}>{row.status === 'active' ? 'Deactivate' : 'Activate'}</Button></div> },
                        ]}
                        emptyMessage={`No ${scopes.find((item) => item.id === scope)?.label.toLowerCase() ?? 'vehicles'} found.`}
                    />
                    <Pagination meta={meta} onPageChange={setPage} />
                </>
            )}
        </div>
    );
}
