import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Tabs } from '../../../shared/components/ui/Tabs';
import {
    RunningChartBillingPreviewPanel,
    RunningChartForm,
    RunningChartLineTable,
    VehicleRentalActivityTimeline,
    VehicleRentalPageHeader,
} from '../components/VehicleRentalComponents';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type { VehicleRentalAgreement, VehicleRentalRunningChart } from '../types/vehicleRental.types';

export function RunningChartListPage() {
    const [rows, setRows] = useState<VehicleRentalRunningChart[]>([]);

    useEffect(() => {
        vehicleRentalApi.runningCharts.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/running-charts/new"><Button>New Running Chart</Button></Link>}
                subtitle="Running charts capture usage for km/hour/day/month billing. Backend owns usage validation, billing, provider payable, and workflow."
                title="Running Charts"
            />
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px]">
                    <Input placeholder="Search chart, agreement, vehicle, driver..." />
                    <Select options={[{ label: 'Any status', value: '' }, { label: 'Draft', value: 'draft' }, { label: 'Submitted', value: 'submitted' }, { label: 'Finalized', value: 'finalized' }]} />
                    <Button variant="secondary">Filter</Button>
                </div>
            </Card>
            <div className="space-y-5">{rows.map((chart) => <Card className="p-5" key={chart.id}><div className="mb-4 flex items-center justify-between"><Link className="font-bold text-blue-700 hover:underline" to={`/vehicle-rental/running-charts/${chart.id}`}>{chart.chartNumber}</Link><span className="text-sm text-slate-500">{chart.status}</span></div><RunningChartLineTable chart={chart} /></Card>)}</div>
        </div>
    );
}

export function RunningChartCreatePage() {
    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to="/vehicle-rental/running-charts"><Button variant="secondary">Cancel</Button></Link>}
                subtitle="Create one guided usage entry and persist separate lessee and lessor running charts."
                title="New Dual Running Chart"
            />
            <RunningChartForm />
        </div>
    );
}

export function RunningChartEditPage() {
    return <RunningChartCreatePage />;
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

    useEffect(() => {
        if (!id) return;
        let active = true;
        vehicleRentalApi.runningCharts.get(id).then((response) => {
            if (!active) return;
            setChart(response.data);
            if (response.data.agreementId) {
                vehicleRentalApi.agreements.get(response.data.agreementId).then((agreementResponse) => active && setAgreement(agreementResponse.data));
            }
        });
        return () => {
            active = false;
        };
    }, [id]);

    if (!chart) {
        return <div className="space-y-6"><VehicleRentalPageHeader title="Loading running chart" /></div>;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                actions={<Link to={`/vehicle-rental/running-charts/${chart.id}/edit`}><Button>Edit</Button></Link>}
                subtitle="Running chart detail displays backend-owned usage and billing previews."
                title={chart.chartNumber}
            />
            <Card className="p-5"><Tabs active={activeTab} items={runningTabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? <PreviewPanel rows={[{ label: 'Agreement', value: chart.agreementNumber }, { label: 'Vehicle', value: chart.vehicle }, { label: 'Driver', value: chart.driver }, { label: 'Start', value: chart.startAt }, { label: 'End', value: chart.endAt }, { label: 'Status', value: chart.status }]} title="Running Chart Overview" /> : null}
            {activeTab === 'lines' ? <RunningChartLineTable chart={chart} /> : null}
            {activeTab === 'billing' ? <RunningChartBillingPreviewPanel chart={chart} /> : null}
            {activeTab === 'provider' ? <PreviewPanel rows={[{ label: 'Provider payable', value: chart.providerPayablePreview }, { label: 'AP impact', value: agreement?.financePreview.calculated.apImpact ?? 'Load agreement first' }]} title="Provider Payable Preview" /> : null}
            {activeTab === 'invoice' ? <PreviewPanel rows={[{ label: 'Rental invoice', value: 'Generated by backend workflow' }, { label: 'Billing', value: chart.billingPreview.calculated.grandTotal }]} title="Invoice Integration" /> : null}
            {activeTab === 'history' ? <VehicleRentalActivityTimeline rows={agreement?.activity ?? []} /> : null}
        </div>
    );
}
