import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { serviceInvoices } from '../mock/vehicleServiceMock';

export function ServiceInvoicesPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Vehicle Service"
                subtitle="Service invoices generated from job cards. Backend owns tax, discounts, totals, postings, status transitions, and document rendering."
                title="Service Invoices"
            />
            <SearchFilterBar placeholder="Search service invoices, customers, or job cards..." />
            <DataTable
                columns={[
                    { header: 'Invoice', key: 'invoiceNumber' },
                    { header: 'Job Card', key: 'jobNumber' },
                    { header: 'Customer', key: 'customer' },
                    { header: 'Preview', key: 'previewStatus' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                ]}
                getRowKey={(row) => row.id}
                rows={serviceInvoices}
            />
            <PreviewPanel title="Invoice calculation preview" rows={[{ label: 'Calculated values', value: 'Displayed from backend preview only' }]} />
        </div>
    );
}
