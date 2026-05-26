import { useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { PageHeader } from '../../../components/layout/PageHeader';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { useTenant } from '../../auth/context/TenantContext';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDate, getStatusTone, parseBooleanSearchParam, parsePositiveInteger } from '../../shared/utils';
import { useDeleteVehicle, useUpdateVehicleStatus, useVehicles } from '../hooks';
import type { VehicleRecord, VehicleRentalStatus, VehicleServiceStatus } from '../types';
import { humanizeVehicleStatus, vehicleTitle } from '../utils';

const rentalStatuses: VehicleRentalStatus[] = ['available', 'reserved', 'rented', 'blocked'];
const serviceStatuses: VehicleServiceStatus[] = ['none', 'in_maintenance', 'under_repair', 'awaiting_parts', 'quality_check', 'ready_for_pickup', 'returned_to_fleet'];

export function VehicleListPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [deleteTarget, setDeleteTarget] = useState<VehicleRecord | null>(null);
    const [statusTarget, setStatusTarget] = useState<VehicleRecord | null>(null);
    const [statusRental, setStatusRental] = useState<VehicleRentalStatus>('available');
    const [statusService, setStatusService] = useState<VehicleServiceStatus>('none');
    const [statusDate, setStatusDate] = useState('');
    const [statusError, setStatusError] = useState<string | null>(null);

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const model = searchParams.get('model') ?? '';
    const ownershipType = searchParams.get('ownership_type') ?? '';
    const rentalStatus = searchParams.get('rental_status') as VehicleRentalStatus | null;
    const serviceStatus = searchParams.get('service_status') as VehicleServiceStatus | null;
    const active = searchParams.get('active') ?? '';

    const vehiclesQuery = useVehicles({
        tenant_id: tenantId,
        page,
        per_page: 10,
        make: search || undefined,
        model: model || undefined,
        ownership_type: ownershipType ? (ownershipType as VehicleRecord['ownership_type']) : undefined,
        rental_status: rentalStatus && rentalStatuses.includes(rentalStatus) ? rentalStatus : undefined,
        service_status: serviceStatus && serviceStatuses.includes(serviceStatus) ? serviceStatus : undefined,
        is_active: parseBooleanSearchParam(active),
        sort: '-created_at',
    });
    const deleteMutation = useDeleteVehicle(tenantId);
    const statusMutation = useUpdateVehicleStatus(statusTarget?.id ?? 0);

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

            if ('search' in updates || 'model' in updates || 'ownership_type' in updates || 'rental_status' in updates || 'service_status' in updates || 'active' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    function openStatusModal(vehicle: VehicleRecord) {
        setStatusTarget(vehicle);
        setStatusRental(vehicle.rental_status);
        setStatusService(vehicle.service_status);
        setStatusDate(vehicle.next_maintenance_due_at?.slice(0, 10) ?? '');
        setStatusError(null);
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        const target = deleteTarget;

        try {
            await deleteMutation.mutateAsync(target.id);
            setDeleteTarget(null);
            showToast({
                title: 'Vehicle deleted',
                description: `${vehicleTitle(target)} has been removed from the registry.`,
                tone: 'success',
            });
        } catch (error) {
            showToast({
                title: 'Unable to delete vehicle',
                description: error instanceof Error ? error.message : 'The vehicle could not be deleted.',
                tone: 'error',
            });
        }
    }

    async function handleStatusConfirm() {
        if (!statusTarget) {
            return;
        }

        setStatusError(null);

        try {
            await statusMutation.mutateAsync({
                tenant_id: tenantId,
                rental_status: statusRental,
                service_status: statusService,
                next_maintenance_due_at: statusDate || null,
            });
            showToast({
                title: 'Vehicle status updated',
                description: `${vehicleTitle(statusTarget)} status was updated successfully.`,
                tone: 'success',
            });
            setStatusTarget(null);
        } catch (error) {
            if (error instanceof ValidationError) {
                setStatusError(Object.values(error.errors).flat()[0] ?? error.message);
                return;
            }

            setStatusError(error instanceof Error ? error.message : 'Unable to update vehicle status.');
        }
    }

    const columns = useMemo<DataTableColumn<VehicleRecord>[]>(
        () => [
            {
                key: 'vehicle',
                header: 'Vehicle',
                render: (vehicle) => (
                    <div>
                        <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/vehicles/${vehicle.id}`}>
                            {vehicleTitle(vehicle)}
                        </Link>
                        <p className="mt-1 text-xs text-stone-500">{vehicle.registration_number ?? vehicle.vin ?? 'No registration assigned'}</p>
                    </div>
                ),
            },
            { key: 'make', header: 'Make', render: (vehicle) => <span className="text-sm text-stone-700">{vehicle.make}</span> },
            { key: 'model', header: 'Model', render: (vehicle) => <span className="text-sm text-stone-700">{vehicle.model}</span> },
            { key: 'ownership', header: 'Ownership', render: (vehicle) => <StatusBadge>{humanizeVehicleStatus(vehicle.ownership_type)}</StatusBadge> },
            {
                key: 'availability',
                header: 'Availability',
                render: (vehicle) => <StatusBadge tone={getStatusTone(vehicle.rental_status)}>{humanizeVehicleStatus(vehicle.rental_status)}</StatusBadge>,
            },
            {
                key: 'service',
                header: 'Service',
                render: (vehicle) => <StatusBadge tone={getStatusTone(vehicle.service_status)}>{humanizeVehicleStatus(vehicle.service_status)}</StatusBadge>,
            },
            { key: 'maintenance', header: 'Maintenance Due', render: (vehicle) => <span className="text-sm text-stone-700">{formatDate(vehicle.next_maintenance_due_at)}</span> },
            {
                key: 'actions',
                header: 'Actions',
                className: 'w-[18rem]',
                render: (vehicle) => (
                    <div className="flex flex-wrap gap-2">
                        <Link to={`/vehicles/${vehicle.id}`}>
                            <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                                View
                            </Button>
                        </Link>
                        <Link to={`/vehicles/${vehicle.id}/edit`}>
                            <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                                Edit
                            </Button>
                        </Link>
                        <Button className="h-9 px-3 text-xs" onClick={() => openStatusModal(vehicle)} type="button" variant="secondary">
                            Change Status
                        </Button>
                        <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(vehicle)} type="button" variant="secondary">
                            Delete
                        </Button>
                    </div>
                ),
            },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to="/vehicles/create">
                        <Button>Add Vehicle</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Vehicle' }, { label: 'Vehicle List' }]}
                description="Search and maintain the vehicle registry without adding rental, job card, or service screens."
                title="Vehicle List"
            />

            <ContentCard className="p-0">
                <TableToolbar
                    actions={<div className="hidden text-xs uppercase tracking-[0.16em] text-stone-400 lg:block">Tenant {tenantId}</div>}
                    description="Filter registry records by make, model, ownership, availability, service status, and active state."
                    title="Vehicles"
                >
                    <SearchFilterToolbar
                        filters={
                            <div className="flex flex-col gap-3 md:flex-row">
                                <Input className="w-full md:max-w-[12rem]" label={undefined} onChange={(event) => updateParams({ model: event.target.value || undefined })} placeholder="Model" value={model} />
                                <Select className="w-full md:max-w-[13rem]" onChange={(event) => updateParams({ ownership_type: event.target.value || undefined })} value={ownershipType}>
                                    <option value="">All ownership</option>
                                    <option value="company_owned">Company Owned</option>
                                    <option value="third_party_owned">Third Party Owned</option>
                                    <option value="customer_owned">Customer Owned</option>
                                    <option value="leased">Leased</option>
                                </Select>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ rental_status: event.target.value || undefined })} value={rentalStatus ?? ''}>
                                    <option value="">All availability</option>
                                    <option value="available">Available</option>
                                    <option value="reserved">Reserved</option>
                                    <option value="rented">In Use</option>
                                    <option value="blocked">Blocked</option>
                                </Select>
                                <Select className="w-full md:max-w-[13rem]" onChange={(event) => updateParams({ service_status: event.target.value || undefined })} value={serviceStatus ?? ''}>
                                    <option value="">All service states</option>
                                    <option value="none">None</option>
                                    <option value="in_maintenance">In Maintenance</option>
                                    <option value="under_repair">Under Repair</option>
                                    <option value="awaiting_parts">Awaiting Parts</option>
                                    <option value="quality_check">Quality Check</option>
                                    <option value="ready_for_pickup">Ready For Pickup</option>
                                    <option value="returned_to_fleet">Returned To Fleet</option>
                                </Select>
                                <Select className="w-full md:max-w-[11rem]" onChange={(event) => updateParams({ active: event.target.value || undefined })} value={active}>
                                    <option value="">All statuses</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </Select>
                            </div>
                        }
                        search={<Input className="w-full md:max-w-sm" label={undefined} onChange={(event) => updateParams({ search: event.target.value || undefined })} placeholder="Search make" value={search} />}
                        trailing={<div className="text-sm text-stone-500">{vehiclesQuery.data?.meta?.total ?? 0} vehicles</div>}
                    />
                </TableToolbar>

                {vehiclesQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : vehiclesQuery.isError ? (
                    isForbiddenError(vehiclesQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={vehiclesQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={vehiclesQuery.error.message} title="Unable to load vehicles" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to="/vehicles/create">
                                        <Button>Create your first vehicle</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No vehicles match the current filters yet. Create a vehicle record or widen the filters."
                                title="No vehicles found"
                            />
                        }
                        footer={<TablePagination meta={vehiclesQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(vehicle) => vehicle.id}
                        rows={vehiclesQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete vehicle"
                description={deleteTarget ? `Delete ${vehicleTitle(deleteTarget)}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete vehicle"
            />

            <ConfirmModal
                confirmLabel="Update status"
                description={statusTarget ? `Update status for ${vehicleTitle(statusTarget)}.` : ''}
                isLoading={statusMutation.isPending}
                onCancel={() => setStatusTarget(null)}
                onConfirm={() => void handleStatusConfirm()}
                open={Boolean(statusTarget)}
                title="Change vehicle status"
            >
                <div className="space-y-3">
                    <Select onChange={(event) => setStatusRental(event.target.value as VehicleRentalStatus)} value={statusRental}>
                        <option value="available">Available</option>
                        <option value="reserved">Reserved</option>
                        <option value="rented">In Use</option>
                        <option value="blocked">Blocked</option>
                    </Select>
                    <Select onChange={(event) => setStatusService(event.target.value as VehicleServiceStatus)} value={statusService}>
                        <option value="none">None</option>
                        <option value="in_maintenance">In Maintenance</option>
                        <option value="under_repair">Under Repair</option>
                        <option value="awaiting_parts">Awaiting Parts</option>
                        <option value="quality_check">Quality Check</option>
                        <option value="ready_for_pickup">Ready For Pickup</option>
                        <option value="returned_to_fleet">Returned To Fleet</option>
                    </Select>
                    <Input label={undefined} onChange={(event) => setStatusDate(event.target.value)} type="date" value={statusDate} />
                    {statusError ? <p className="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{statusError}</p> : null}
                </div>
            </ConfirmModal>
        </div>
    );
}
