import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { jobCards } from '../mock/vehicleServiceMock';

export function JobCardListPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <>
                        <Link to="/vehicle-service/history">
                            <Button variant="secondary">Service History</Button>
                        </Link>
                        <Link to="/vehicle-service/job-cards/new">
                            <Button>New Job Card</Button>
                        </Link>
                    </>
                }
                eyebrow="Vehicle Service"
                subtitle="Operational service jobs with backend-owned workflow status, stock effects, invoice previews, payments, audit, and history."
                title="Job Cards"
            />
            <div className="grid gap-4 md:grid-cols-3">
                {[
                    ['Open job cards', '18', 'Mock operational list'],
                    ['Awaiting preview', '6', 'Backend previews later'],
                    ['Supervisor review', '4', 'Backend workflow status'],
                ].map(([label, value, helper]) => (
                    <Card className="p-5" key={label}>
                        <p className="text-sm text-slate-500">{label}</p>
                        <p className="mt-2 text-2xl font-bold text-slate-950">{value}</p>
                        <p className="mt-1 text-xs text-slate-400">{helper}</p>
                    </Card>
                ))}
            </div>
            <SearchFilterBar placeholder="Search job number, customer, vehicle, advisor..." />
            <DataTable
                columns={[
                    { header: 'Job card', key: 'jobNumber', render: (row) => <Link className="font-semibold text-slate-950" to={`/vehicle-service/job-cards/${row.id}`}>{row.jobNumber}</Link> },
                    { header: 'Customer', key: 'customer' },
                    { header: 'Vehicle', key: 'vehicle' },
                    { header: 'Advisor', key: 'serviceAdvisor' },
                    { header: 'Expected', key: 'expectedCompletion' },
                    { header: 'Preview', key: 'previewStatus' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                ]}
                getRowKey={(row) => row.id}
                rows={jobCards}
            />
            <PreviewPanel
                rows={[
                    { label: 'Invoice preview', value: 'Backend service invoice endpoint placeholder' },
                    { label: 'Stock preview', value: 'Backend stock availability endpoint placeholder' },
                    { label: 'Labour preview', value: 'Backend labour/incentive endpoint placeholder' },
                ]}
                title="Service backend previews"
            />
        </div>
    );
}
