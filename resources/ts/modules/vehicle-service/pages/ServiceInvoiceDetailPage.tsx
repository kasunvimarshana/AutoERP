import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { ServiceInvoicePreviewPanel, ServiceInvoiceRecordPanel, VehicleServiceFinancePostingPanel, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceInvoice, VehicleServiceJobCard } from '../types/vehicleService.types';

export function ServiceInvoiceDetailPage() {
    const { id = '' } = useParams();
    const [invoice, setInvoice] = useState<VehicleServiceInvoice>();
    const [jobCard, setJobCard] = useState<VehicleServiceJobCard>();
    const [error, setError] = useState<string>();

    useEffect(() => {
        vehicleServiceApi.invoices.get(id)
            .then((response) => {
                setInvoice(response.data);
                return vehicleServiceApi.jobCards.get(response.data.id);
            })
            .then((response) => setJobCard(response.data))
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load service invoice.'));
    }, [id]);

    if (error) {
        return <EmptyState description={error} title="Service invoice unavailable" />;
    }

    if (!invoice || !jobCard) {
        return <EmptyState description="Loading service invoice from backend..." title="Loading invoice" />;
    }

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<><Link to="/vehicle-service/invoices"><Button variant="secondary">All Invoices</Button></Link><Link to="/vehicle-service/invoices/new"><Button variant="blue">Generate Invoice</Button></Link></>}
                subtitle="Readonly service invoice view. Official totals, finance posting, and payment allocations are backend-owned."
                title={invoice.invoiceNumber}
            />
            <PreviewPanel
                rows={[
                    { label: 'Job card', value: invoice.jobCardNumber },
                    { label: 'Billing customer', value: invoice.billingCustomer },
                    { label: 'Preview total', value: invoice.previewTotal },
                    { label: 'Invoice status', value: invoice.invoiceStatus },
                    { label: 'Status', value: invoice.status },
                ]}
                status="Invoice"
                title="Service Invoice Summary"
            />
            <div className="grid gap-5 xl:grid-cols-3">
                <ServiceInvoicePreviewPanel jobCard={jobCard} />
                <ServiceInvoiceRecordPanel jobCard={jobCard} />
                <VehicleServiceFinancePostingPanel jobCard={jobCard} />
            </div>
        </div>
    );
}
