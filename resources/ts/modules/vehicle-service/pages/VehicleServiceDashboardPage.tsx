import { useState } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { JobCardTable, ServiceInvoiceTable, VehicleServiceDashboardCards, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceDashboardMetric, VehicleServiceInvoice, VehicleServiceJobCard } from '../types/vehicleService.types';

export function VehicleServiceDashboardPage() {
    const [metrics, setMetrics] = useState<VehicleServiceDashboardMetric[]>([]);
    const [jobCards, setJobCards] = useState<VehicleServiceJobCard[]>([]);
    const [serviceInvoices, setServiceInvoices] = useState<VehicleServiceInvoice[]>([]);
    const [error, setError] = useState('');
    const [isLoaded, setIsLoaded] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    async function loadDashboardData(): Promise<void> {
        if (isLoading) return;

        setIsLoading(true);
        setError('');

        try {
            const [summaryResponse, jobCardResponse, invoiceResponse] = await Promise.all([
                vehicleServiceApi.dashboard.summary(),
                vehicleServiceApi.jobCards.list(),
                vehicleServiceApi.invoices.list(),
            ]);

            setMetrics(summaryResponse.data);
            setJobCards(jobCardResponse.data.slice(0, 5));
            setServiceInvoices(invoiceResponse.data.slice(0, 5));
            setIsLoaded(true);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to load Vehicle Service dashboard.');
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<><Link to="/vehicle-service/job-cards/new"><Button>New Job Card</Button></Link><Link to="/vehicle-service/payments/new"><Button variant="blue">Service Payment</Button></Link></>}
                subtitle="Workshop workflows for job cards, diagnostics, inspections, service/spare/labour/customer-supplied lines, invoice previews, payments, stock effects, documents, and finance posting."
                title="Vehicle Service Dashboard"
            />
            <div className="flex justify-end">
                <Button disabled={isLoading} onClick={() => void loadDashboardData()} type="button" variant="secondary">{isLoaded ? 'Refresh Dashboard Data' : 'Load Dashboard Data'}</Button>
            </div>
            {error ? <EmptyState description={error} title="Vehicle Service dashboard unavailable" /> : null}
            {!isLoaded && !error ? <EmptyState description="Workshop metrics, jobs, and invoices load only when requested." title="Dashboard data not loaded" /> : null}
            {isLoaded ? <VehicleServiceDashboardCards metrics={metrics} /> : null}
            <PreviewPanel status="Domain" title="VehicleService Workflow">
                <div className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                    {['Customer and vehicle intake', 'Diagnostics and inspections', 'Service, spare, labour, non-inventory, external, and customer-supplied lines', 'Technician assignment and backend incentive preview', 'Stock availability and spare consumption preview', 'Service invoice, payment, document, and finance integration'].map((item) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 font-semibold text-slate-700" key={item}>{item}</div>
                    ))}
                </div>
            </PreviewPanel>
            {isLoaded ? <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Open Workshop Jobs</h2>
                <JobCardTable rows={jobCards} />
            </div> : null}
            {isLoaded ? <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Service Invoices</h2>
                <ServiceInvoiceTable rows={serviceInvoices} />
            </div> : null}
        </div>
    );
}

export { VehicleServiceDashboardPage as VehicleServicePage };
