import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { JobCardTable, ServiceInvoiceTable, VehicleServiceDashboardCards, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceDashboardMetric, VehicleServiceInvoice, VehicleServiceJobCard } from '../types/vehicleService.types';

export function VehicleServiceDashboardPage() {
    const [metrics, setMetrics] = useState<VehicleServiceDashboardMetric[]>([]);
    const [jobCards, setJobCards] = useState<VehicleServiceJobCard[]>([]);
    const [serviceInvoices, setServiceInvoices] = useState<VehicleServiceInvoice[]>([]);

    useEffect(() => {
        vehicleServiceApi.dashboard.summary().then((response) => setMetrics(response.data));
        vehicleServiceApi.jobCards.list().then((response) => setJobCards(response.data));
        vehicleServiceApi.invoices.list().then((response) => setServiceInvoices(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<><Link to="/vehicle-service/job-cards/new"><Button>New Job Card</Button></Link><Link to="/vehicle-service/payments/new"><Button variant="blue">Service Payment</Button></Link></>}
                subtitle="Workshop workflows for job cards, diagnostics, inspections, service/spare/labour/customer-supplied lines, invoice previews, payments, stock effects, and finance posting."
                title="Vehicle Service Dashboard"
            />
            <VehicleServiceDashboardCards metrics={metrics} />
            <PreviewPanel status="Domain" title="VehicleService Workflow">
                <div className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                    {['Customer and vehicle intake', 'Diagnostics and inspections', 'Service, spare, labour, non-inventory, external, and customer-supplied lines', 'Technician assignment and backend incentive preview', 'Stock availability and spare consumption preview', 'Service invoice, payment, and finance integration'].map((item) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 font-semibold text-slate-700" key={item}>{item}</div>
                    ))}
                </div>
            </PreviewPanel>
            <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Open Workshop Jobs</h2>
                <JobCardTable rows={jobCards} />
            </div>
            <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Service Invoices</h2>
                <ServiceInvoiceTable rows={serviceInvoices} />
            </div>
        </div>
    );
}

export { VehicleServiceDashboardPage as VehicleServicePage };
