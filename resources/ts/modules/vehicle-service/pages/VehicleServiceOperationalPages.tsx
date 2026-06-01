import { useEffect, useState } from 'react';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceJobCard } from '../types/vehicleService.types';

function BackendGapPage({ title, reason }: { reason: string; title: string }) {
    return (
        <div className="space-y-6">
            <VehicleServicePageHeader subtitle={reason} title={title} />
            <EmptyState description={reason} title="Backend contract unavailable" />
        </div>
    );
}

export function VehicleServiceInspectionsPage() {
    const [jobCards, setJobCards] = useState<VehicleServiceJobCard[]>([]);

    useEffect(() => {
        let active = true;
        vehicleServiceApi.jobCards.list({ per_page: 25 }).then((response) => active && setJobCards(response.data));

        return () => {
            active = false;
        };
    }, []);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader subtitle="Open a job card to add/edit inspections against the real backend inspection endpoint." title="Service Inspections" />
            {jobCards.length ? (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {jobCards.map((jobCard) => (
                        <a className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300" href={`/vehicle-service/job-cards/${jobCard.id}/inspections`} key={jobCard.id}>
                            <p className="font-semibold text-slate-950">{jobCard.jobCardNumber}</p>
                            <p className="mt-1 text-sm text-slate-600">{jobCard.vehicle}</p>
                            <p className="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{jobCard.status}</p>
                        </a>
                    ))}
                </div>
            ) : <EmptyState description="No job cards are available for inspection entry." title="No inspections" />}
        </div>
    );
}

export function VehicleServiceLabourPage() {
    return <BackendGapPage reason="Technician labour assignments are currently managed inside each job card detail through the real labor-assignment endpoint. A standalone labour planning board has no backend route in this repository." title="Labour / Technicians" />;
}

export function VehicleServiceEstimatesPage() {
    return <BackendGapPage reason="No Vehicle Service estimate/approval migration, controller, or API route exists in the current backend. Invoice preview is available from saved job cards." title="Estimates" />;
}

export function VehicleServicePartsIssuesPage() {
    return <BackendGapPage reason="Parts consumption is posted from job cards through the backend inventory workflow endpoint. A standalone parts issue document route is not present in this repository." title="Parts Issues" />;
}

export function VehicleServiceRefundsPage() {
    return <BackendGapPage reason="No Vehicle Service refund index/create endpoint exists in the current backend. Payment allocation is available from the service payment screen." title="Refunds" />;
}

export function VehicleServiceReturnsPage() {
    return <BackendGapPage reason="No Vehicle Service return document migration, controller, or API route exists in the current backend. Inventory return can only be represented by backend stock reversal workflow today." title="Returns" />;
}

export function VehicleServiceWorkOrdersPage() {
    return <BackendGapPage reason="No standalone work-order/task backend exists in the current Vehicle Service module. Service, parts, non-inventory, and labour lines are persisted on job cards." title="Work Orders" />;
}
