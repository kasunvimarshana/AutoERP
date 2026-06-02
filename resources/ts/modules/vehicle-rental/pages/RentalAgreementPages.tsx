import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Tabs } from '../../../shared/components/ui/Tabs';
import {
    BreakdownPanel,
    RentalAgreementForm,
    RentalAgreementTable,
    RentalBillingPreviewPanel,
    RentalInvoicePanel,
    RentalPaymentPanel,
    RentalProviderPanel,
    RentalProviderPayablePanel,
    RentalRateRulesTable,
    ReplacementVehiclePanel,
    RunningChartLineTable,
    VehicleRentalActivityTimeline,
    VehicleRentalFinancePostingPanel,
    VehicleRentalPageHeader,
    VehicleRentalWorkflowActions,
} from '../components/VehicleRentalComponents';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type { VehicleRentalAgreement } from '../types/vehicleRental.types';

export function RentalAgreementListPage() {
    const [rows, setRows] = useState<VehicleRentalAgreement[]>([]);

    useEffect(() => {
        vehicleRentalApi.agreements.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/agreements/new"><Button>New Agreement</Button></Link>}
                subtitle="Rental agreements own the rental workflow. Backend owns availability, billing, provider payable, payment allocation, and status."
                title="Rental Agreements"
            />
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px_180px]">
                    <Input placeholder="Search agreement, customer, vehicle, provider..." />
                    <Select options={[{ label: 'Any status', value: '' }, { label: 'Draft', value: 'draft' }, { label: 'Active', value: 'active' }, { label: 'Running', value: 'running' }, { label: 'Closed', value: 'closed' }]} />
                    <Select options={[{ label: 'Any mode', value: '' }, { label: 'With driver', value: 'with_driver' }, { label: 'Without driver', value: 'without_driver' }]} />
                    <Button variant="secondary">Filter</Button>
                </div>
            </Card>
            <RentalAgreementTable rows={rows} />
        </div>
    );
}

export function RentalAgreementCreatePage() {
    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/agreements"><Button variant="secondary">Cancel</Button></Link>}
                subtitle="Create linked lessee and lessor agreements. Backend generates agreement numbers and keeps receivable/payable sides separate."
                title="New Dual Agreement"
            />
            <RentalAgreementForm />
        </div>
    );
}

export function RentalAgreementEditPage() {
    const { id = '' } = useParams();
    const [agreement, setAgreement] = useState<VehicleRentalAgreement>();

    useEffect(() => {
        if (id) vehicleRentalApi.agreements.get(id).then((response) => setAgreement(response.data));
    }, [id]);

    if (!agreement) {
        return <div className="space-y-6"><VehicleRentalPageHeader title="Loading agreement" /></div>;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to={`/vehicle-rental/agreements/${agreement.id}`}><Button variant="secondary">View</Button></Link>}
                subtitle="Edit rental agreement inputs. Backend remains authoritative for availability, rates, billing, provider payable, payments, and finance."
                title={`Edit ${agreement.agreementNumber}`}
            />
            <RentalAgreementForm agreement={agreement} />
        </div>
    );
}

const detailTabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Rental Terms', value: 'terms' },
    { label: 'Rates & Charges', value: 'rates' },
    { label: 'Running Charts', value: 'running' },
    { label: 'Invoices', value: 'invoices' },
    { label: 'Payments', value: 'payments' },
    { label: 'Provider Payables', value: 'provider' },
    { label: 'Replacements', value: 'replacements' },
    { label: 'Breakdowns', value: 'breakdowns' },
    { label: 'Documents', value: 'documents' },
    { label: 'Workflow / History', value: 'history' },
    { label: 'Audit', value: 'audit' },
];

export function RentalAgreementDetailPage() {
    const { id = '' } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [agreement, setAgreement] = useState<VehicleRentalAgreement>();

    useEffect(() => {
        if (id) vehicleRentalApi.agreements.get(id).then((response) => setAgreement(response.data));
    }, [id]);

    if (!agreement) {
        return <div className="space-y-6"><VehicleRentalPageHeader title="Loading agreement" /></div>;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to={`/vehicle-rental/agreements/${agreement.id}/edit`}><Button>Edit</Button></Link>}
                subtitle="Agreement detail keeps rental workflow separate from Sales. Backend owns availability, rental billing, provider payable, payment allocation, workflow, documents, and finance."
                title={agreement.agreementNumber}
            />
            <Card className="p-5"><Tabs active={activeTab} items={detailTabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                    <Card className="p-5">
                        <div className="grid gap-4 md:grid-cols-2">
                            {[
                                ['Side', agreement.agreementRole],
                                ['Lessee', agreement.customer],
                                ['Vehicle', agreement.vehicle],
                                ['Vehicle source', agreement.vehicleSource],
                                ['Driver', agreement.driver],
                                ['Lessor / Provider', agreement.provider],
                                ['Linked lessee agreement', agreement.lesseeAgreementId ?? 'Not linked'],
                                ['Linked lessor agreement', agreement.lessorAgreementId ?? 'Not linked'],
                                ['Rental unit', agreement.rentalUnit],
                                ['Start', agreement.startAt],
                                ['End', agreement.endAt],
                            ].map(([label, value]) => (
                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4" key={label}>
                                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
                                    <p className="mt-1 font-semibold text-slate-900">{value}</p>
                                </div>
                            ))}
                        </div>
                    </Card>
                    <VehicleRentalWorkflowActions agreement={agreement} />
                </div>
            ) : null}
            {activeTab === 'terms' ? <PreviewPanel rows={[{ label: 'Mode', value: agreement.mode }, { label: 'Rental unit', value: agreement.rentalUnit }, { label: 'Rate plan', value: agreement.ratePlan.name }, { label: 'Status', value: agreement.status }]} title="Rental Terms" /> : null}
            {activeTab === 'rates' ? <div className="space-y-5"><RentalBillingPreviewPanel agreement={agreement} /><RentalRateRulesTable agreement={agreement} /></div> : null}
            {activeTab === 'running' ? <div className="space-y-5">{agreement.runningCharts.map((chart) => <RunningChartLineTable chart={chart} key={chart.id} />)}</div> : null}
            {activeTab === 'invoices' ? <RentalInvoicePanel rows={agreement.invoices} /> : null}
            {activeTab === 'payments' ? <RentalPaymentPanel rows={agreement.payments} /> : null}
            {activeTab === 'provider' ? <div className="space-y-5"><RentalProviderPanel agreement={agreement} /><RentalProviderPayablePanel rows={agreement.providerPayables} /></div> : null}
            {activeTab === 'replacements' ? <ReplacementVehiclePanel rows={agreement.replacements} /> : null}
            {activeTab === 'breakdowns' ? <BreakdownPanel rows={agreement.breakdowns} /> : null}
            {activeTab === 'documents' ? <PreviewPanel rows={[{ label: 'Document', value: agreement.documentPreview.documentNumber }, { label: 'Template', value: agreement.documentPreview.template }, { label: 'Status', value: agreement.documentPreview.status }]} title="Document Integration" /> : null}
            {activeTab === 'history' ? <VehicleRentalActivityTimeline rows={agreement.activity} /> : null}
            {activeTab === 'audit' ? <div className="space-y-5"><VehicleRentalActivityTimeline rows={agreement.activity} /><VehicleRentalFinancePostingPanel agreement={agreement} /></div> : null}
        </div>
    );
}
