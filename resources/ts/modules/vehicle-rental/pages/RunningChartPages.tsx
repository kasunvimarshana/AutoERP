import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { DataToolbar, type DataToolbarFilterConfig } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import {
    RunningChartBillingPreviewPanel,
    RunningChartForm,
    RunningChartLineTable,
    RunningChartTable,
    VehicleRentalActivityTimeline,
    VehicleRentalPageHeader,
} from '../components/VehicleRentalComponents';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type { VehicleRentalAgreement, VehicleRentalRunningChart } from '../types/vehicleRental.types';

const filters: DataToolbarFilterConfig[] = [
    { id: 'status', label: 'Status', options: [{ label: 'Draft', value: 'draft' }, { label: 'Submitted', value: 'submitted' }, { label: 'Approved', value: 'approved' }, { label: 'Invoiced', value: 'invoiced' }], type: 'select' },
];

export function RunningChartListPage() {
    const [rows, setRows] = useState<VehicleRentalRunningChart[]>([]);
    const [search, setSearch] = useState('');
    const [filterValues, setFilterValues] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string>();

    useEffect(() => {
        setLoading(true);
        vehicleRentalApi.runningCharts.list({ search, ...filterValues })
            .then((response) => setRows(response.data))
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load running charts.'))
            .finally(() => setLoading(false));
    }, [search, filterValues]);

    const activeFilterChips = useMemo(() => Object.entries(filterValues).filter(([, value]) => value).map(([id, value]) => ({ id, label: id, value })), [filterValues]);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/running-charts/new"><Button>New Running Chart</Button></Link>}
                subtitle="Running charts capture checkout/check-in usage. Backend owns usage validation, billing, provider payable and workflow."
                title="Running Charts"
            />
            <DataToolbar
                activeFilterChips={activeFilterChips}
                disabled={loading}
                filters={filters}
                filterValues={filterValues}
                isLoading={loading}
                onFilterChange={(id, value) => setFilterValues((current) => ({ ...current, [id]: String(value ?? '') }))}
                onRemoveFilter={(id) => setFilterValues((current) => ({ ...current, [id]: '' }))}
                onResetFilters={() => setFilterValues({})}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved views are not backed by a Vehicle Rental preference API yet."
                searchPlaceholder="Search chart, agreement, vehicle, driver..."
                searchValue={search}
            />
            {error ? <EmptyState description={error} title="Running charts unavailable" /> : <RunningChartTable rows={rows} />}
        </div>
    );
}

export function RunningChartCreatePage() {
    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/running-charts"><Button variant="secondary">Cancel</Button></Link>}
                subtitle="Create usage capture for an agreement. Backend owns usage, billing, provider payable, invoice and status effects."
                title="New Running Chart"
            />
            <RunningChartForm />
        </div>
    );
}

export function RunningChartEditPage() {
    const { id = '' } = useParams();
    const [chart, setChart] = useState<VehicleRentalRunningChart>();
    const [error, setError] = useState<string>();

    useEffect(() => {
        vehicleRentalApi.runningCharts.get(id).then((response) => setChart(response.data)).catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load running chart.'));
    }, [id]);

    if (error) {
        return <EmptyState description={error} title="Running chart unavailable" />;
    }

    if (!chart) {
        return <EmptyState description="Loading running chart from backend..." title="Loading running chart" />;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to={`/vehicle-rental/running-charts/${chart.id}`}><Button variant="secondary">View</Button></Link>}
                subtitle="Edit checkout/check-in usage capture. Backend owns billing and provider cost calculation."
                title={`Edit ${chart.chartNumber}`}
            />
            <RunningChartForm chart={chart} mode="edit" />
        </div>
    );
}

const runningTabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Usage Lines', value: 'lines' },
    { label: 'Billing Preview', value: 'billing' },
    { label: 'Provider Payable', value: 'provider' },
    { label: 'Invoice', value: 'invoice' },
    { label: 'History / Audit', value: 'history' },
];

export function RunningChartDetailPage() {
    const { id = '' } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [chart, setChart] = useState<VehicleRentalRunningChart>();
    const [agreement, setAgreement] = useState<VehicleRentalAgreement>();
    const [error, setError] = useState<string>();

    useEffect(() => {
        vehicleRentalApi.runningCharts.get(id)
            .then(async (response) => {
                setChart(response.data);
                if (response.data.agreementNumber) {
                    try {
                        setAgreement((await vehicleRentalApi.agreements.get(response.data.agreementNumber)).data);
                    } catch {
                        setAgreement(undefined);
                    }
                }
            })
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load running chart.'));
    }, [id]);

    if (error) {
        return <EmptyState description={error} title="Running chart unavailable" />;
    }

    if (!chart) {
        return <EmptyState description="Loading running chart from backend..." title="Loading running chart" />;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<><Link to={`/vehicle-rental/running-charts/${chart.id}/edit`}><Button>Edit</Button></Link><Button onClick={() => vehicleRentalApi.runningCharts.transition(chart.id, 'approved')} variant="blue">Approve</Button></>}
                subtitle="Running chart detail displays backend-owned usage and billing previews."
                title={chart.chartNumber}
            />
            <Card className="p-5"><Tabs active={activeTab} items={runningTabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? <PreviewPanel rows={[{ label: 'Agreement', value: chart.agreementNumber }, { label: 'Vehicle', value: chart.vehicle }, { label: 'Driver', value: chart.driver }, { label: 'Start', value: chart.startAt }, { label: 'End', value: chart.endAt }, { label: 'Status', value: chart.status }]} title="Running Chart Overview" /> : null}
            {activeTab === 'lines' ? <RunningChartLineTable chart={chart} /> : null}
            {activeTab === 'billing' ? <RunningChartBillingPreviewPanel chart={chart} /> : null}
            {activeTab === 'provider' ? <PreviewPanel rows={[{ label: 'Provider payable', value: chart.providerPayablePreview }, { label: 'AP impact', value: agreement?.financePreview.calculated.apImpact ?? 'Load agreement to preview AP impact' }]} title="Provider Payable Preview" /> : null}
            {activeTab === 'invoice' ? <PreviewPanel rows={[{ label: 'Rental invoice', value: 'Generated by backend agreement workflow' }, { label: 'Billing', value: chart.billingPreview.calculated.grandTotal }]} title="Invoice Integration" /> : null}
            {activeTab === 'history' ? <VehicleRentalActivityTimeline rows={agreement?.activity ?? []} /> : null}
        </div>
    );
}
