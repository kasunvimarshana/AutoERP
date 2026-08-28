import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ActionMenu } from '@/shared/components/ActionMenu';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { PaginationMeta } from '@/shared/types/pagination';
import { listVehicles, setVehicleActive } from './vehicleApi';
import type { VehicleSummary } from './vehicleTypes';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { lookupApi } from '@/shared/api/lookupApi';
import type { NamedResource } from '@/shared/types/common';
import { notifySuccess } from '@/shared/notifications/appToast';
import { hasVehiclePermission, vehiclePermissions } from './vehiclePermissions';

const currentOwnerName = (row: VehicleSummary, ownerType = 'customer') =>
    (ownerType === 'customer' ? row.current_customer?.name : row.current_supplier?.name) ?? '-';

export default function VehicleListPage() {
    const auth = useAuth();
    const canCreate = hasVehiclePermission(auth, vehiclePermissions.create);
    const canUpdate = hasVehiclePermission(auth, vehiclePermissions.update);
    const canChangeStatus = hasVehiclePermission(auth, vehiclePermissions.changeStatus);
    const [searchParams] = useSearchParams();
    const ownership = searchParams.get('ownership');
    const title = ownership === 'supplier' ? 'Supplier Vehicles' : ownership === 'customer' ? 'Customer Vehicles' : 'Vehicles';
    const description = ownership === 'supplier'
        ? 'Vehicles supplied, leased, rented, or owned by external owners and suppliers.'
        : ownership === 'customer'
            ? 'Customer-owned vehicles available to service and related workflows.'
            : 'Vehicle master data, ownership, documents, and attributes.';
    const [rows, setRows] = useState<VehicleSummary[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [search, setSearch] = useState('');
    const [customer, setCustomer] = useState<NamedResource | null>(null);
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const debouncedSearch = useDebounce(search);
    const detailQuery = ownership ? `?ownership=${ownership}` : '';
    const hasFilters = Boolean(search || customer);

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) setLoading(true);
        });
        listVehicles({
            search: debouncedSearch,
            customer_id: customer?.id,
            ownership_scope: ownership || undefined,
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
    }, [customer, debouncedSearch, ownership, page]);

    const refreshStatus = (vehicle: VehicleSummary, active: boolean) => {
        if (!canChangeStatus) return;
        setVehicleActive(vehicle.id, active)
            .then((updated) => {
                setRows((current) => updateVehicleRows(current, updated));
                notifySuccess(updated.status === 'active' ? 'Vehicle activated successfully.' : 'Vehicle deactivated successfully.');
            })
            .catch((requestError) => setError(toApiError(requestError)));
    };

    return (
        <div>
            <ContentHeader title={title} description={description} actions={canCreate ? <LinkButton to="/vehicles/create">New vehicle</LinkButton> : undefined} />
            <div className="mb-4 grid max-w-4xl gap-3 md:grid-cols-2">
                <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Number, registration, chassis, engine, VIN" />
                <LookupSelect label="Customer" value={customer} onChange={(value) => { setCustomer(value); setPage(1); }} search={lookupApi.customers} />
            </div>
            {hasFilters && (
                <div className="mb-4 flex flex-wrap items-center gap-2 text-sm text-slate-600">
                    <span>Filters applied</span>
                    <Button variant="ghost" className="min-h-9 px-3 py-1.5" onClick={() => {
                        setSearch('');
                        setCustomer(null);
                        setPage(1);
                    }}>Clear filters</Button>
                </div>
            )}
            <ErrorAlert error={error} />
            {loading ? <LoadingState label="Loading vehicles..." /> : (
                <>
                    <DataTable
                        rows={rows}
                        rowKey={(row) => row.id}
                        rowHref={(row) => `/vehicles/${row.id}${detailQuery}`}
                        columns={[
                            { key: 'number', header: 'Vehicle', render: (row) => <div><p className="font-medium text-slate-900">{row.vehicle_number}</p><p className="text-xs text-slate-500">{row.registration_number ?? row.code ?? '-'}</p></div> },
                            { key: 'make', header: 'Make / Model', render: (row) => `${row.make?.name ?? '-'} / ${row.model?.name ?? '-'}` },
                            { key: 'type', header: 'Type', render: (row) => row.type?.name ?? '-' },
                            { key: 'customer', header: 'Current Customer', render: (row) => currentOwnerName(row) },
                            { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
                            ...((canUpdate || canChangeStatus) ? [{ key: 'actions', header: '', render: (row: VehicleSummary) => <div className="flex justify-end gap-2">
                                {canUpdate && <LinkButton to={`/vehicles/${row.id}/edit${detailQuery}`} variant="secondary">Edit</LinkButton>}
                                {canChangeStatus && <ActionMenu><Button className="w-full justify-start" variant="ghost" onClick={() => refreshStatus(row, row.status !== 'active')}>{row.status === 'active' ? 'Deactivate' : 'Activate'}</Button></ActionMenu>}
                            </div> }] : []),
                        ]}
                    />
                    <Pagination meta={meta} onPageChange={setPage} />
                </>
            )}
        </div>
    );
}

function updateVehicleRows(rows: VehicleSummary[], updated: VehicleSummary) {
    const currentIndex = rows.findIndex((row) => row.id === updated.id);

    if (currentIndex === -1) return rows;

    return rows.map((row) => row.id === updated.id ? updated : row);
}
