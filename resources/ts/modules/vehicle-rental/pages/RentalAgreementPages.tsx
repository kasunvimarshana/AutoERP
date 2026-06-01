import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { DataToolbar, type DataToolbarFilterConfig } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import {
    BreakdownPanel,
    RentalAgreementForm,
    RentalAgreementTable,
    RentalBillingPreviewPanel,
    RentalExtraChargesTable,
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

const filters: DataToolbarFilterConfig[] = [
    { id: 'status', label: 'Status', options: [{ label: 'Draft', value: 'draft' }, { label: 'Active', value: 'active' }, { label: 'Running', value: 'running' }, { label: 'Invoiceable', value: 'invoiceable' }, { label: 'Closed', value: 'closed' }], type: 'select' },
    { id: 'agreement_role', label: 'Role', options: [{ label: 'Customer', value: 'customer' }, { label: 'Provider', value: 'provider' }], type: 'select' },
];

export function RentalAgreementListPage() {
    const [rows, setRows] = useState<VehicleRentalAgreement[]>([]);
    const [search, setSearch] = useState('');
    const [filterValues, setFilterValues] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string>();

    async function load(): Promise<void> {
        setLoading(true);
        setError(undefined);
        try {
            const response = await vehicleRentalApi.agreements.list({ search, ...filterValues });
            setRows(response.data);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to load rental agreements.');
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        void load();
    }, [search, filterValues]);

    const activeFilters = useMemo(() => Object.entries(filterValues).filter(([, value]) => value).map(([key, value]) => ({ key, label: key.replaceAll('_', ' '), value })), [filterValues]);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/agreements/new"><Button>New Agreement</Button></Link>}
                subtitle="Backend owns availability, rental billing, provider payable, payment allocation, finance posting, documents and workflow status."
                title="Rental Agreements"
            />
            <DataToolbar
                activeFilterChips={activeFilters.map((filter) => ({ id: filter.key, label: filter.label, value: filter.value }))}
                disabled={loading}
                filters={filters}
                filterValues={filterValues}
                isLoading={loading}
                onFilterChange={(name, value) => setFilterValues((current) => ({ ...current, [name]: String(value ?? '') }))}
                onRemoveFilter={(name) => setFilterValues((current) => ({ ...current, [name]: '' }))}
                onResetFilters={() => setFilterValues({})}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved views are not backed by a Vehicle Rental preference API yet."
                searchPlaceholder="Search agreement, customer, vehicle, provider..."
                searchValue={search}
            />
            {error ? <EmptyState description={error} title="Agreements unavailable" /> : <RentalAgreementTable rows={rows} />}
        </div>
    );
}

export function RentalAgreementCreatePage() {
    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/agreements"><Button variant="secondary">Cancel</Button></Link>}
                subtitle="Create a rental agreement using real customer, rental vehicle, driver/provider, item and UOM APIs."
                title="New Rental Agreement"
            />
            <RentalAgreementForm />
        </div>
    );
}

export function RentalAgreementEditPage() {
    const { id = '' } = useParams();
    const [agreement, setAgreement] = useState<VehicleRentalAgreement>();
    const [error, setError] = useState<string>();

    useEffect(() => {
        vehicleRentalApi.agreements.get(id).then((response) => setAgreement(response.data)).catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load agreement.'));
    }, [id]);

    if (error) {
        return <EmptyState description={error} title="Agreement unavailable" />;
    }

    if (!agreement) {
        return <EmptyState description="Loading agreement from backend..." title="Loading agreement" />;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to={`/vehicle-rental/agreements/${agreement.id}`}><Button variant="secondary">View</Button></Link>}
                subtitle="Edit rental agreement inputs. Backend remains authoritative for availability, rates, billing, provider payable, payments, and finance."
                title={`Edit ${agreement.agreementNumber}`}
            />
            <RentalAgreementForm agreement={agreement} mode="edit" />
        </div>
    );
}

const detailTabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Rental Period & Availability', value: 'terms' },
    { label: 'Rates / Charges', value: 'rates' },
    { label: 'Checkout / Check-in', value: 'running' },
    { label: 'Invoices', value: 'invoices' },
    { label: 'Deposits / Payments', value: 'payments' },
    { label: 'Provider Payables', value: 'provider' },
    { label: 'Replacements', value: 'replacements' },
    { label: 'Breakdowns', value: 'breakdowns' },
    { label: 'Documents', value: 'documents' },
    { label: 'History / Audit', value: 'history' },
];

export function RentalAgreementDetailPage() {
    const { id = '' } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [agreement, setAgreement] = useState<VehicleRentalAgreement>();
    const [error, setError] = useState<string>();

    function load(): void {
        vehicleRentalApi.agreements.get(id).then((response) => setAgreement(response.data)).catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load agreement.'));
    }

    useEffect(load, [id]);

    if (error) {
        return <EmptyState description={error} title="Agreement unavailable" />;
    }

    if (!agreement) {
        return <EmptyState description="Loading agreement from backend..." title="Loading agreement" />;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<><Link to={`/vehicle-rental/agreements/${agreement.id}/edit`}><Button>Edit</Button></Link><Link to="/vehicle-rental/invoices/new"><Button variant="blue">Generate Invoice</Button></Link></>}
                subtitle="Agreement detail keeps rental workflow separate from core modules. Backend owns availability, billing, provider payable, payments, documents, and finance."
                title={agreement.agreementNumber}
            />
            <Card className="p-5"><Tabs active={activeTab} items={detailTabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                    <Card className="p-5">
                        <div className="grid gap-4 md:grid-cols-2">
                            {[
                                ['Customer', agreement.customer],
                                ['Vehicle', agreement.vehicle],
                                ['Vehicle source', agreement.vehicleSource],
                                ['Driver', agreement.driver],
                                ['Provider / payee', agreement.provider],
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
                    <VehicleRentalWorkflowActions agreement={agreement} onChanged={load} />
                </div>
            ) : null}
            {activeTab === 'terms' ? <PreviewPanel rows={[{ label: 'Mode', value: agreement.mode }, { label: 'Rental unit', value: agreement.rentalUnit }, { label: 'Rate plan', value: agreement.ratePlan.name }, { label: 'Availability', value: agreement.availabilityPreview.calculated.availabilityDecision }, { label: 'Status', value: agreement.status }]} title="Rental Period & Availability" /> : null}
            {activeTab === 'rates' ? <div className="space-y-5"><RentalBillingPreviewPanel agreement={agreement} /><RentalRateRulesTable agreement={agreement} /><RentalExtraChargesTable rows={agreement.lines} /></div> : null}
            {activeTab === 'running' ? <div className="space-y-5">{agreement.runningCharts.length ? agreement.runningCharts.map((chart) => <RunningChartLineTable chart={chart} key={chart.id} />) : <EmptyState description="No checkout/check-in running chart has been created for this agreement." title="No running charts" />}</div> : null}
            {activeTab === 'invoices' ? <RentalInvoicePanel rows={agreement.invoices} /> : null}
            {activeTab === 'payments' ? <RentalPaymentPanel rows={agreement.payments} /> : null}
            {activeTab === 'provider' ? <div className="space-y-5"><RentalProviderPanel agreement={agreement} /><RentalProviderPayablePanel rows={agreement.providerPayables} /></div> : null}
            {activeTab === 'replacements' ? <ReplacementVehiclePanel rows={agreement.replacements} /> : null}
            {activeTab === 'breakdowns' ? <BreakdownPanel rows={agreement.breakdowns} /> : null}
            {activeTab === 'documents' ? <PreviewPanel rows={[{ label: 'Document', value: agreement.documentPreview.documentNumber }, { label: 'Template', value: agreement.documentPreview.template }, { label: 'Status', value: agreement.documentPreview.status }]} title="Document Integration" /> : null}
            {activeTab === 'history' ? <div className="space-y-5"><VehicleRentalActivityTimeline rows={agreement.activity} /><VehicleRentalFinancePostingPanel agreement={agreement} /></div> : null}
        </div>
    );
}
