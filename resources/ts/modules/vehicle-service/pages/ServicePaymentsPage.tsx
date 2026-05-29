import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { servicePayments } from '../mock/vehicleServiceMock';

export function ServicePaymentsPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Vehicle Service"
                subtitle="Module-local service payments and allocation previews. Backend owns allocation, balance, settlement, finance postings, and reversals."
                title="Service Payments"
            />
            <SearchFilterBar placeholder="Search service payments, invoices, or customers..." />
            <DataTable
                columns={[
                    { header: 'Payment', key: 'paymentNumber' },
                    { header: 'Source', key: 'source' },
                    { header: 'Customer', key: 'customer' },
                    { header: 'Preview', key: 'previewStatus' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                ]}
                getRowKey={(row) => row.id}
                rows={servicePayments}
            />
            <PreviewPanel title="Payment allocation preview" rows={[{ label: 'Allocation result', value: 'Displayed from backend preview only' }]} />
        </div>
    );
}
