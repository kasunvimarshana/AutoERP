import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { getInvoiceById, getJobCardById } from '../mock/vehicleServiceMock';
import { ServiceInvoiceDocumentPanel, ServiceInvoicePreviewPanel, VehicleServiceFinancePostingPanel, VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceInvoice } from '../types/vehicleService.types';

export function ServiceInvoiceDetailPage() {
    const { id = 'svc-inv-001' } = useParams();
    const [invoice, setInvoice] = useState<VehicleServiceInvoice>(getInvoiceById(id));
    const jobCard = getJobCardById(invoice.jobCardNumber);

    useEffect(() => {
        vehicleServiceApi.invoices.get(id).then((response) => setInvoice(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                actions={<><Link to="/vehicle-service/invoices"><Button variant="secondary">All Invoices</Button></Link><Button variant="blue">Generate Document</Button></>}
                subtitle="Readonly service invoice view. Official totals, document rendering, finance posting, and payment allocations are backend-owned."
                title={invoice.invoiceNumber}
            />
            <PreviewPanel
                rows={[
                    { label: 'Job card', value: invoice.jobCardNumber },
                    { label: 'Billing customer', value: invoice.billingCustomer },
                    { label: 'Preview total', value: invoice.previewTotal },
                    { label: 'Document status', value: invoice.documentStatus },
                    { label: 'Status', value: invoice.status },
                ]}
                status="Invoice"
                title="Service Invoice Summary"
            />
            <div className="grid gap-5 xl:grid-cols-3">
                <ServiceInvoicePreviewPanel jobCard={jobCard} />
                <ServiceInvoiceDocumentPanel jobCard={jobCard} />
                <VehicleServiceFinancePostingPanel jobCard={jobCard} />
            </div>
        </div>
    );
}
