import { PageHeader } from '../../../shared/components/business/PageHeader';
import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { jobCards } from '../mock/vehicleServiceMock';

export function ServiceHistoryPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Vehicle Service"
                subtitle="Customer and vehicle service history timeline. Backend audit/history endpoints remain authoritative."
                title="Service History"
            />
            <SearchFilterBar placeholder="Search vehicle plate, customer, job card, or service item..." />
            <DataTable
                columns={[
                    { header: 'Job Card', key: 'jobNumber' },
                    { header: 'Customer', key: 'customer' },
                    { header: 'Vehicle', key: 'vehicle' },
                    { header: 'Opened', key: 'openedAt' },
                    { header: 'Advisor', key: 'serviceAdvisor' },
                ]}
                getRowKey={(row) => row.id}
                rows={jobCards}
            />
            <AuditTimeline />
        </div>
    );
}
