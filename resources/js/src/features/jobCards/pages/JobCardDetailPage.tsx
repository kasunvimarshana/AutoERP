import { Link, useParams } from 'react-router-dom';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { DataTable, StatusBadge, type DataTableColumn } from '../../../components/tables';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { useTenant } from '../../auth/context/TenantContext';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatCurrency, formatDate, formatQuantity, getStatusTone, parsePositiveInteger } from '../../shared/utils';
import { useVehicle } from '../../vehicles/hooks';
import { vehicleTitle } from '../../vehicles/utils';
import { useVehicleJobCards } from '../hooks';
import type { JobCardAssignedSubItem, JobCardOrderLine, VehicleJobCardRecord } from '../types';

function humanize(value: string | null | undefined) {
    if (!value) {
        return '-';
    }

    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

type DetailItemProps = {
    label: string;
    value: string | number | null | undefined;
};

function DetailItem({ label, value }: DetailItemProps) {
    return (
        <div>
            <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">{label}</dt>
            <dd className="mt-1 text-sm font-medium text-stone-950">{value === null || value === undefined || value === '' ? '-' : value}</dd>
        </div>
    );
}

export function JobCardDetailPage() {
    const { tenantId } = useTenant();
    const { vehicleId: vehicleIdParam, jobCardId: jobCardIdParam } = useParams();
    const vehicleId = parsePositiveInteger(vehicleIdParam, 0);
    const jobCardId = parsePositiveInteger(jobCardIdParam, 0);
    const vehicleQuery = useVehicle(vehicleId, tenantId, vehicleId > 0);
    const jobCardsQuery = useVehicleJobCards({ tenant_id: tenantId, vehicle_id: vehicleId, page: 1, per_page: 100 }, vehicleId > 0);

    if (vehicleId <= 0 || jobCardId <= 0) {
        return <ErrorState description="The job card route is missing a valid vehicle or job card ID." title="Invalid job card route" />;
    }

    if (vehicleQuery.isPending || jobCardsQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (vehicleQuery.isError) {
        return isForbiddenError(vehicleQuery.error) ? <ProtectedErrorState description={vehicleQuery.error.message} /> : <ErrorState description={vehicleQuery.error.message} title="Unable to load vehicle" />;
    }

    if (jobCardsQuery.isError) {
        return isForbiddenError(jobCardsQuery.error) ? <ProtectedErrorState description={jobCardsQuery.error.message} /> : <ErrorState description={jobCardsQuery.error.message} title="Unable to load job card" />;
    }

    const vehicle = vehicleQuery.data;
    const jobCard = jobCardsQuery.data.items.find((item) => item.id === jobCardId);

    if (!jobCard) {
        return <ErrorState description="This backend exposes vehicle job-card lists, but the requested job card was not present in the current vehicle list." title="Job card not found" />;
    }

    const orderLines = jobCard.metadata?.order_lines ?? [];
    const assignedSubItems = jobCard.metadata?.assigned_sub_items ?? [];
    const orderColumns: DataTableColumn<JobCardOrderLine>[] = [
        { key: 'product', header: 'Product', render: (line) => line.product_name },
        { key: 'quantity', header: 'Quantity', render: (line) => formatQuantity(line.quantity) },
        { key: 'price', header: 'Net Unit Price', render: (line) => formatCurrency(line.net_unit_price) },
        { key: 'discount', header: 'Discount', render: (line) => `${formatQuantity(line.discount)}%` },
        { key: 'subtotal', header: 'Sub Total', render: (line) => <span className="font-semibold text-stone-950">{formatCurrency(line.sub_total)}</span> },
    ];
    const assignedColumns: DataTableColumn<JobCardAssignedSubItem>[] = [
        { key: 'employee', header: 'Employee Name', render: (item) => item.employee_name },
        { key: 'service', header: 'Service Item', render: (item) => item.service_item },
        { key: 'sub_item', header: 'Sub Item', render: (item) => item.sub_item },
        { key: 'sub_item_id', header: 'Sub Item ID', render: (item) => item.sub_item_id },
        { key: 'incentive', header: 'Incentive Amount', render: (item) => <span className="font-semibold text-stone-950">{formatCurrency(item.incentive_amount)}</span> },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to={`/job-cards/create?vehicle_id=${vehicleId}`}>
                        <Button>New Job Card</Button>
                    </Link>
                }
                breadcrumbs={[
                    { label: 'Job Cards', href: '/job-cards' },
                    { label: vehicle ? vehicleTitle(vehicle) : `Vehicle #${vehicleId}`, href: `/vehicles/${vehicleId}/job-cards` },
                    { label: jobCard.job_card_no },
                ]}
                description="Job card detail assembled from the supported vehicle job-card list route."
                title={jobCard.job_card_no}
            />

            <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <ContentCard>
                    <dl className="grid gap-4 sm:grid-cols-2">
                        <DetailItem label="Vehicle" value={vehicle ? vehicleTitle(vehicle) : `#${jobCard.vehicle_id}`} />
                        <DetailItem label="Customer ID" value={jobCard.customer_id ? `#${jobCard.customer_id}` : null} />
                        <DetailItem label="Job Type" value={humanize(jobCard.service_type)} />
                        <DetailItem label="Next Service" value={formatDate(jobCard.scheduled_at ?? jobCard.metadata?.next_service_date)} />
                        <DetailItem label="Mileage" value={formatQuantity(jobCard.metadata?.mileage as number | string | null | undefined)} />
                        <DetailItem label="Monthly Mileage" value={formatQuantity(jobCard.metadata?.monthly_mileage as number | string | null | undefined)} />
                        <DetailItem label="Supervisor" value={jobCard.metadata?.supervisor_name as string | null | undefined} />
                    </dl>
                </ContentCard>

                <ContentCard>
                    <div className="space-y-4">
                        <div className="flex items-center justify-between rounded-xl border border-stone-200 bg-stone-50 px-4 py-3">
                            <span className="text-sm text-stone-600">Job Status</span>
                            <StatusBadge tone={getStatusTone(jobCard.workflow_status)}>{humanize(jobCard.workflow_status)}</StatusBadge>
                        </div>
                        <div className="flex items-center justify-between rounded-xl border border-stone-200 bg-stone-50 px-4 py-3">
                            <span className="text-sm text-stone-600">Payment Status</span>
                            <StatusBadge tone={getStatusTone(jobCard.metadata?.payment_status)}>{humanize(jobCard.metadata?.payment_status)}</StatusBadge>
                        </div>
                        <div className="rounded-xl border border-stone-200 px-4 py-4">
                            <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Grand Total</p>
                            <p className="mt-2 text-2xl font-semibold text-stone-950">{formatCurrency(jobCard.grand_total)}</p>
                        </div>
                    </div>
                </ContentCard>
            </div>

            <ContentCard className="p-0">
                <div className="border-b border-stone-200 px-6 py-5">
                    <h3 className="text-lg font-semibold text-stone-950">Order Table</h3>
                </div>
                <DataTable
                    columns={orderColumns}
                    emptyState={<EmptyState className="m-6" description="No product lines were stored for this job card." title="No order lines" />}
                    getRowKey={(line) => line.id}
                    rows={orderLines}
                />
            </ContentCard>

            <ContentCard className="p-0">
                <div className="border-b border-stone-200 px-6 py-5">
                    <h3 className="text-lg font-semibold text-stone-950">Assigned Sub items</h3>
                </div>
                <DataTable
                    columns={assignedColumns}
                    emptyState={<EmptyState className="m-6" description="No crew sub items were assigned for this job card." title="No assigned sub items" />}
                    getRowKey={(item) => item.id}
                    rows={assignedSubItems}
                />
            </ContentCard>
        </div>
    );
}
