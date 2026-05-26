import { Link } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { StatusBadge } from '../../../components/tables';
import { useTenant } from '../../auth/context/TenantContext';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDate } from '../../shared/utils';
import { useVehicleDashboard } from '../hooks';
import { humanizeVehicleStatus } from '../utils';

const dashboardCards = [
    { key: 'all', label: 'Total Vehicles' },
    { key: 'rental_available', label: 'Available' },
    { key: 'rented', label: 'In Use' },
    { key: 'in_service', label: 'In Service' },
    { key: 'awaiting_parts', label: 'Awaiting Parts' },
    { key: 'quality_control', label: 'Quality Control' },
    { key: 'due_for_maintenance', label: 'Due Maintenance' },
] as const;

export function VehicleDashboardPage() {
    const { tenantId } = useTenant();
    const dashboardQuery = useVehicleDashboard(tenantId);

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Link to="/vehicles">
                            <Button variant="secondary">Vehicle List</Button>
                        </Link>
                        <Link to="/vehicles/create">
                            <Button>Add Vehicle</Button>
                        </Link>
                    </div>
                }
                breadcrumbs={[{ label: 'Vehicle' }, { label: 'Dashboard' }]}
                description="Registry-level summary widgets from the Vehicle dashboard API."
                title="Vehicle Dashboard"
            />

            {dashboardQuery.isPending ? (
                <LoadingState lines={8} />
            ) : dashboardQuery.isError ? (
                isForbiddenError(dashboardQuery.error) ? (
                    <ProtectedErrorState description={dashboardQuery.error.message} />
                ) : (
                    <ErrorState description={dashboardQuery.error.message} title="Unable to load vehicle dashboard" />
                )
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {dashboardCards.map((card) => (
                            <ContentCard key={card.key}>
                                <p className="text-xs uppercase tracking-[0.14em] text-stone-500">{card.label}</p>
                                <p className="mt-3 text-3xl font-semibold text-stone-950">{dashboardQuery.data.totals?.[card.key] ?? 0}</p>
                            </ContentCard>
                        ))}
                    </div>

                    <ContentCard>
                        <div className="flex flex-col gap-1 border-b border-stone-200/80 pb-4">
                            <h2 className="text-lg font-semibold text-stone-950">Expiring Documents</h2>
                            <p className="text-sm text-stone-600">Document alerts returned by the Vehicle dashboard endpoint.</p>
                        </div>

                        {(dashboardQuery.data.expiring_documents ?? []).length > 0 ? (
                            <div className="mt-4 divide-y divide-stone-200/80">
                                {(dashboardQuery.data.expiring_documents ?? []).map((document, index) => (
                                    <div className="flex flex-col gap-2 py-4 md:flex-row md:items-center md:justify-between" key={`${document.vehicle_id ?? 'vehicle'}-${document.document_type ?? index}`}>
                                        <div>
                                            <p className="font-medium text-stone-950">{humanizeVehicleStatus(document.document_type ?? 'Document')}</p>
                                            <p className="mt-1 text-sm text-stone-500">
                                                Vehicle {document.vehicle_id ? `#${document.vehicle_id}` : '-'} {document.document_number ? `- ${document.document_number}` : ''}
                                            </p>
                                        </div>
                                        <StatusBadge tone="warning">{`Expires ${formatDate(document.expires_at)}`}</StatusBadge>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyState className="mt-4" description="No expiring vehicle documents are available from the current dashboard response." title="No document alerts" />
                        )}
                    </ContentCard>
                </>
            )}
        </div>
    );
}
