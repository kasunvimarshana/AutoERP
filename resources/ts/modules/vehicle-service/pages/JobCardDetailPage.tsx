import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Tabs } from '../../../shared/components/ui/Tabs';
import {
    CustomerSuppliedItemsSection,
    DiagnosticsPanel,
    ExternalServicesSection,
    InspectionPanel,
    JobCardLineTable,
    LabourAssignmentPanel,
    NonInventoryItemsSection,
    ServiceInvoiceDocumentPanel,
    ServiceInvoicePreviewPanel,
    ServicePaymentPanel,
    SparePartsSection,
    StockAvailabilityPanel,
    VehicleServiceActivityTimeline,
    VehicleServiceFinancePostingPanel,
    VehicleServicePageHeader,
    VehicleServicePartyContextPanel,
    VehicleServiceWorkflowActions,
} from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceJobCard } from '../types/vehicleService.types';

const detailTabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Job Lines', value: 'lines' },
    { label: 'Labour & Assignments', value: 'labour' },
    { label: 'Diagnostics', value: 'diagnostics' },
    { label: 'Inspections', value: 'inspections' },
    { label: 'Inventory / Stock Usage', value: 'inventory' },
    { label: 'Invoice / Payments', value: 'invoice' },
    { label: 'Documents', value: 'documents' },
    { label: 'Workflow / History', value: 'history' },
    { label: 'Audit', value: 'audit' },
];

export function JobCardDetailPage() {
    const { id = '' } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [jobCard, setJobCard] = useState<VehicleServiceJobCard>();
    const [error, setError] = useState<string>();

    function reload(): void {
        if (!id) {
            return;
        }

        vehicleServiceApi.jobCards.get(id)
            .then((response) => setJobCard(response.data))
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load job card.'));
    }

    useEffect(() => {
        reload();
    }, [id]);

    if (error) {
        return <EmptyState description={error} title="Job card unavailable" />;
    }

    if (!jobCard) {
        return <EmptyState description="Loading job card from backend..." title="Loading job card" />;
    }

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<><Link to={`/vehicle-service/job-cards/${jobCard.id}/diagnostics`}><Button variant="secondary">Diagnostics</Button></Link><Link to={`/vehicle-service/job-cards/${jobCard.id}/inspections`}><Button variant="secondary">Inspections</Button></Link><Link to={`/vehicle-service/job-cards/${jobCard.id}/edit`}><Button>Edit</Button></Link></>}
                subtitle="Job card detail keeps workshop workflow separate from Sales. Backend owns calculations, workflow state, inventory effects, document generation, payment allocation, and finance posting."
                title={jobCard.jobCardNumber}
            />
            <Card className="p-5">
                <Tabs active={activeTab} items={detailTabs} onChange={setActiveTab} />
            </Card>

            {activeTab === 'overview' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                    <Card className="p-5">
                        <div className="grid gap-4 md:grid-cols-2">
                            {[
                                ['Service customer', jobCard.partyContext.serviceCustomer.name],
                                ['Billing customer', jobCard.partyContext.billingCustomer.name],
                                ['Payer', jobCard.partyContext.payer.name],
                                ['Vehicle', jobCard.vehicle],
                                ['Service type', jobCard.serviceType],
                                ['Advisor', jobCard.serviceAdvisor],
                                ['Supervisor', jobCard.supervisor],
                                ['Odometer', jobCard.odometer],
                                ['Opened', jobCard.openedAt],
                                ['Expected completion', jobCard.expectedCompletion],
                            ].map(([label, value]) => (
                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4" key={label}>
                                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
                                    <p className="mt-1 font-semibold text-slate-900">{value}</p>
                                </div>
                            ))}
                        </div>
                        <div className="mt-5 grid gap-4 md:grid-cols-2">
                            <div>
                                <p className="text-sm font-bold text-slate-900">Complaint</p>
                                <p className="mt-1 text-sm text-slate-600">{jobCard.customerComplaint}</p>
                            </div>
                            <div>
                                <p className="text-sm font-bold text-slate-900">Initial diagnosis</p>
                                <p className="mt-1 text-sm text-slate-600">{jobCard.initialDiagnosis}</p>
                            </div>
                        </div>
                    </Card>
                    <div className="space-y-5">
                        <VehicleServicePartyContextPanel jobCard={jobCard} />
                        <VehicleServiceWorkflowActions jobCard={jobCard} onChanged={reload} />
                    </div>
                </div>
            ) : null}

            {activeTab === 'lines' ? <div className="space-y-5"><JobCardLineTable rows={jobCard.lines} /><SparePartsSection lines={jobCard.lines} /><NonInventoryItemsSection lines={jobCard.lines} /><CustomerSuppliedItemsSection lines={jobCard.lines} /><ExternalServicesSection lines={jobCard.lines} /></div> : null}
            {activeTab === 'labour' ? <LabourAssignmentPanel jobCard={jobCard} /> : null}
            {activeTab === 'diagnostics' ? <DiagnosticsPanel rows={jobCard.diagnostics} /> : null}
            {activeTab === 'inspections' ? <InspectionPanel rows={jobCard.inspections} /> : null}
            {activeTab === 'inventory' ? <StockAvailabilityPanel jobCard={jobCard} /> : null}
            {activeTab === 'invoice' ? <div className="space-y-5"><ServiceInvoicePreviewPanel jobCard={jobCard} /><ServicePaymentPanel payments={jobCard.payments} /><VehicleServiceFinancePostingPanel jobCard={jobCard} /></div> : null}
            {activeTab === 'documents' ? <ServiceInvoiceDocumentPanel jobCard={jobCard} /> : null}
            {activeTab === 'history' ? <VehicleServiceActivityTimeline rows={jobCard.audit} /> : null}
            {activeTab === 'audit' ? <VehicleServiceActivityTimeline rows={jobCard.audit} /> : null}
        </div>
    );
}
