import { useMemo } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
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
import { formatCurrency, formatDate, getStatusTone, parsePositiveInteger } from '../../shared/utils';
import { useVehicles } from '../../vehicles/hooks';
import { vehicleTitle } from '../../vehicles/utils';
import { useVehicleJobCards } from '../hooks';
import type { VehicleJobCardRecord } from '../types';

function humanize(value: string | null | undefined) {
    if (!value) {
        return '-';
    }

    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

type JobCardListPageProps = {
    scopedToVehicle?: boolean;
};

export function JobCardListPage({ scopedToVehicle = false }: JobCardListPageProps) {
    const { tenantId } = useTenant();
    const { vehicleId: vehicleIdParam } = useParams();
    const [searchParams, setSearchParams] = useSearchParams();
    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const selectedVehicleId = scopedToVehicle ? parsePositiveInteger(vehicleIdParam ?? null, 0) : parsePositiveInteger(searchParams.get('vehicle_id'), 0);
    const search = searchParams.get('search') ?? '';
    const vehiclesQuery = useVehicles({ tenant_id: tenantId, page: 1, per_page: 100, sort: '-created_at' });
    const jobCardsQuery = useVehicleJobCards(
        {
            tenant_id: tenantId,
            vehicle_id: selectedVehicleId,
            page,
            per_page: 10,
            sort: '-created_at',
        },
        selectedVehicleId > 0,
    );

    const selectedVehicle = vehiclesQuery.data?.items.find((vehicle) => vehicle.id === selectedVehicleId);
    const rows = (jobCardsQuery.data?.items ?? []).filter((jobCard) => {
        if (!search.trim()) {
            return true;
        }

        return jobCard.job_card_no.toLowerCase().includes(search.trim().toLowerCase());
    });

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

            if ('vehicle_id' in updates || 'search' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const columns = useMemo<DataTableColumn<VehicleJobCardRecord>[]>(
        () => [
            {
                key: 'job_card',
                header: 'Job Card',
                render: (jobCard) => (
                    <div>
                        <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/vehicles/${jobCard.vehicle_id}/job-cards/${jobCard.id}`}>
                            {jobCard.job_card_no}
                        </Link>
                        <p className="mt-1 text-xs text-stone-500">Vehicle #{jobCard.vehicle_id}</p>
                    </div>
                ),
            },
            { key: 'type', header: 'Job Type', render: (jobCard) => humanize(jobCard.service_type) },
            { key: 'scheduled', header: 'Next Service', render: (jobCard) => formatDate(jobCard.scheduled_at) },
            { key: 'total', header: 'Grand Total', render: (jobCard) => <span className="font-semibold text-stone-950">{formatCurrency(jobCard.grand_total)}</span> },
            {
                key: 'status',
                header: 'Job Status',
                render: (jobCard) => <StatusBadge tone={getStatusTone(jobCard.workflow_status)}>{humanize(jobCard.workflow_status)}</StatusBadge>,
            },
            {
                key: 'payment',
                header: 'Payment Status',
                render: (jobCard) => <StatusBadge tone={getStatusTone(jobCard.metadata?.payment_status)}>{humanize(jobCard.metadata?.payment_status)}</StatusBadge>,
            },
            {
                key: 'actions',
                header: 'Actions',
                render: (jobCard) => (
                    <Link to={`/vehicles/${jobCard.vehicle_id}/job-cards/${jobCard.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            View
                        </Button>
                    </Link>
                ),
            },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to={selectedVehicleId > 0 ? `/job-cards/create?vehicle_id=${selectedVehicleId}` : '/job-cards/create'}>
                        <Button>New Job Card</Button>
                    </Link>
                }
                breadcrumbs={scopedToVehicle ? [{ label: 'Vehicle', href: '/vehicles' }, { label: selectedVehicle ? vehicleTitle(selectedVehicle) : 'Vehicle Job Cards' }] : [{ label: 'Job Cards' }]}
                description={scopedToVehicle ? 'Vehicle-specific job card history using the supported vehicle job-card endpoint.' : 'Select a vehicle to review supported job card records.'}
                title={scopedToVehicle ? 'Vehicle Job Cards' : 'Job Card List'}
            />

            <ContentCard className="p-0">
                <TableToolbar
                    actions={<div className="hidden text-xs uppercase tracking-[0.16em] text-stone-400 lg:block">Tenant {tenantId}</div>}
                    description="Job cards are loaded through the real vehicle-scoped backend route."
                    title="Job Cards"
                >
                    <SearchFilterToolbar
                        filters={
                            scopedToVehicle ? null : (
                                <Select className="w-full md:max-w-xs" onChange={(event) => updateParams({ vehicle_id: event.target.value || undefined })} value={selectedVehicleId || ''}>
                                    <option value="">Select Vehicle</option>
                                    {(vehiclesQuery.data?.items ?? []).map((vehicle) => (
                                        <option key={vehicle.id} value={vehicle.id}>
                                            {vehicleTitle(vehicle)}
                                        </option>
                                    ))}
                                </Select>
                            )
                        }
                        search={<Input className="w-full md:max-w-sm" label={undefined} onChange={(event) => updateParams({ search: event.target.value || undefined })} placeholder="Search job card no" value={search} />}
                        trailing={<div className="text-sm text-stone-500">{jobCardsQuery.data?.meta?.total ?? 0} job cards</div>}
                    />
                </TableToolbar>

                {selectedVehicleId <= 0 ? (
                    <EmptyState
                        action={
                            <Link to="/job-cards/create">
                                <Button>Create Job Card</Button>
                            </Link>
                        }
                        className="m-6"
                        description="Choose a vehicle above to load its job cards."
                        title="Select a vehicle"
                    />
                ) : jobCardsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : jobCardsQuery.isError ? (
                    isForbiddenError(jobCardsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={jobCardsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={jobCardsQuery.error.message} title="Unable to load job cards" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to={`/job-cards/create?vehicle_id=${selectedVehicleId}`}>
                                        <Button>Create Job Card</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No job cards exist for this vehicle yet."
                                title="No job cards found"
                            />
                        }
                        footer={<TablePagination meta={jobCardsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(jobCard) => jobCard.id}
                        rows={rows}
                    />
                )}
            </ContentCard>
        </div>
    );
}
