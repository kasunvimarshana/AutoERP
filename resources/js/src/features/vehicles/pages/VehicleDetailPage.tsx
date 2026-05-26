import { Link, useParams, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { StatusBadge } from '../../../components/tables';
import { useTenant } from '../../auth/context/TenantContext';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { RelatedDocumentsTabs } from '../../shared/workflow';
import { formatDate, formatQuantity, getStatusTone, parsePositiveInteger } from '../../shared/utils';
import { useVehicle } from '../hooks';
import { humanizeVehicleStatus, readVehicleMetadata, vehicleTitle } from '../utils';

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'technical', label: 'Technical Details' },
    { id: 'owner', label: 'Owner Details' },
    { id: 'activity', label: 'Activity' },
] as const;

type VehicleTabId = (typeof tabs)[number]['id'];

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

export function VehicleDetailPage() {
    const { tenantId } = useTenant();
    const { vehicleId: vehicleIdParam } = useParams();
    const vehicleId = parsePositiveInteger(vehicleIdParam ?? null, 0);
    const [searchParams, setSearchParams] = useSearchParams();
    const tabParam = searchParams.get('tab') as VehicleTabId | null;
    const activeTab = tabs.some((tab) => tab.id === tabParam) ? tabParam ?? 'overview' : 'overview';

    const vehicleQuery = useVehicle(vehicleId, tenantId, vehicleId > 0);

    if (vehicleId <= 0) {
        return <ErrorState description="The vehicle route is missing a valid vehicle ID." title="Invalid vehicle route" />;
    }

    if (vehicleQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (vehicleQuery.isError) {
        return isForbiddenError(vehicleQuery.error) ? <ProtectedErrorState description={vehicleQuery.error.message} /> : <ErrorState description={vehicleQuery.error.message} title="Unable to load vehicle" />;
    }

    const vehicle = vehicleQuery.data;

    if (!vehicle) {
        return <ErrorState description="The vehicle could not be resolved from the current route." title="Vehicle not found" />;
    }

    const metadata = readVehicleMetadata(vehicle);

    function renderOverview() {
        return (
            <div className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <ContentCard>
                    <dl className="grid gap-4 sm:grid-cols-2">
                        <DetailItem label="Registration Number" value={vehicle.registration_number} />
                        <DetailItem label="Vehicle Number / Asset Code" value={vehicle.asset_code} />
                        <DetailItem label="Make" value={vehicle.make} />
                        <DetailItem label="Model" value={vehicle.model} />
                        <DetailItem label="Year" value={vehicle.year} />
                        <DetailItem label="Next Maintenance Due" value={formatDate(vehicle.next_maintenance_due_at)} />
                    </dl>
                </ContentCard>
                <ContentCard>
                    <div className="space-y-3">
                        <div className="rounded-2xl border border-stone-200/80 bg-stone-50/80 px-4 py-4">
                            <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Availability</p>
                            <div className="mt-2">
                                <StatusBadge tone={getStatusTone(vehicle.rental_status)}>{humanizeVehicleStatus(vehicle.rental_status)}</StatusBadge>
                            </div>
                        </div>
                        <div className="rounded-2xl border border-stone-200/80 bg-stone-50/80 px-4 py-4">
                            <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Service Status</p>
                            <div className="mt-2">
                                <StatusBadge tone={getStatusTone(vehicle.service_status)}>{humanizeVehicleStatus(vehicle.service_status)}</StatusBadge>
                            </div>
                        </div>
                    </div>
                </ContentCard>
            </div>
        );
    }

    function renderTechnical() {
        return (
            <ContentCard>
                <dl className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <DetailItem label="VIN" value={vehicle.vin} />
                    <DetailItem label="Chassis Number" value={vehicle.chassis_number} />
                    <DetailItem label="Engine Number" value={typeof metadata.engine_number === 'string' ? metadata.engine_number : null} />
                    <DetailItem label="Fuel Type" value={humanizeVehicleStatus(vehicle.fuel_type)} />
                    <DetailItem label="Transmission" value={humanizeVehicleStatus(vehicle.transmission)} />
                    <DetailItem label="Mileage / Odometer" value={formatQuantity(vehicle.odometer)} />
                    <DetailItem label="Color" value={typeof metadata.color === 'string' ? metadata.color : null} />
                    <DetailItem label="Image Path" value={vehicle.primary_image_path} />
                    <DetailItem label="Active" value={vehicle.is_active === undefined ? null : vehicle.is_active ? 'Yes' : 'No'} />
                </dl>
            </ContentCard>
        );
    }

    function renderOwner() {
        return (
            <ContentCard>
                <dl className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <DetailItem label="Ownership Type" value={humanizeVehicleStatus(vehicle.ownership_type)} />
                    <DetailItem label="Customer ID" value={vehicle.customer_id ? `#${vehicle.customer_id}` : null} />
                    <DetailItem label="Supplier ID" value={vehicle.supplier_id ? `#${vehicle.supplier_id}` : null} />
                    <DetailItem label="Organization Unit ID" value={vehicle.org_unit_id ? `#${vehicle.org_unit_id}` : null} />
                    <DetailItem label="Notes" value={typeof metadata.notes === 'string' ? metadata.notes : null} />
                </dl>
            </ContentCard>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to={`/vehicles/${vehicle.id}/edit`}>
                        <Button>Edit Vehicle</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Vehicle', href: '/vehicles' }, { label: vehicleTitle(vehicle) }]}
                description="Vehicle registry detail view with overview, technical, owner, and activity sections only."
                title={vehicleTitle(vehicle)}
            />

            <RelatedDocumentsTabs activeTab={activeTab} onChange={(tabId) => setSearchParams({ tab: tabId })} tabs={tabs.map((tab) => ({ id: tab.id, label: tab.label }))} />

            {activeTab === 'overview' ? renderOverview() : null}
            {activeTab === 'technical' ? renderTechnical() : null}
            {activeTab === 'owner' ? renderOwner() : null}
            {activeTab === 'activity' ? <EmptyState description="Activity will appear here when registry-specific audit events are exposed to the UI." title="No activity yet" /> : null}
        </div>
    );
}
