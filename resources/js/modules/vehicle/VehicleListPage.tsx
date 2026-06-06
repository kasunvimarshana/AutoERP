import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
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
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import type { VehicleCategory, VehicleMake, VehicleModel, VehicleType } from './vehicleTypes';

const statuses = ['', 'active', 'inactive', 'under_service', 'rented', 'reserved', 'sold', 'blocked', 'scrapped'];

export default function VehicleListPage() {
    const [rows, setRows] = useState<VehicleSummary[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [make, setMake] = useState<VehicleMake | null>(null);
    const [model, setModel] = useState<VehicleModel | null>(null);
    const [type, setType] = useState<VehicleType | null>(null);
    const [category, setCategory] = useState<VehicleCategory | null>(null);
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
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
            customer_id: customer?.id,
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
            .finally(() => setLoading(false));
        return () => controller.abort();
    }, [category, customer, debouncedSearch, make, model, page, status, type]);

    const refreshStatus = (vehicle: VehicleSummary, active: boolean) => {
        setVehicleActive(vehicle.id, active)
            .then((updated) => setRows((current) => current.map((row) => row.id === vehicle.id ? { ...row, status: updated.status } : row)))
            .catch((requestError) => setError(toApiError(requestError)));
    };

    return (
        <div>
            <ContentHeader title="Vehicles" description="Vehicle master data, ownership, documents, and attributes." actions={<Link to="/vehicles/create"><Button>Create Vehicle</Button></Link>} />
            <div className="mb-4 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Number, registration, chassis, engine, VIN" />
                <Select label="Status" value={status} options={statuses.filter(Boolean).map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                <VehicleMakeSelect value={make} onChange={(value) => { setMake(value); setModel(null); setPage(1); }} />
                <VehicleModelSelect makeId={make?.id} value={model} onChange={(value) => { setModel(value); setPage(1); }} />
                <VehicleTypeSelect value={type} onChange={(value) => { setType(value); setPage(1); }} />
                <VehicleCategorySelect value={category} onChange={(value) => { setCategory(value); setPage(1); }} />
                <CustomerLookupSelect value={customer} onChange={(value) => { setCustomer(value); setPage(1); }} />
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
                            { key: 'customer', header: 'Customer', render: (row) => row.customer?.name ?? '-' },
                            { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
                            { key: 'actions', header: '', render: (row) => <div className="flex justify-end gap-2"><Link to={`/vehicles/${row.id}`}><Button variant="ghost">View</Button></Link><Link to={`/vehicles/${row.id}/edit`}><Button variant="ghost">Edit</Button></Link><Button variant="secondary" onClick={() => refreshStatus(row, row.status !== 'active')}>{row.status === 'active' ? 'Deactivate' : 'Activate'}</Button></div> },
                        ]}
                    />
                    <Pagination meta={meta} onPageChange={setPage} />
                </>
            )}
        </div>
    );
}
