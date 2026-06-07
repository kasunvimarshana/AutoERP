import { lazy, Suspense, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { EntityDetailLayout } from '@/shared/components/EntityDetailLayout';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { compareDecimalStrings } from '@/shared/utils/decimal';
import { VehicleServiceSummaryPanel } from '../components/VehicleServiceSummaryPanel';
import { VehicleServiceStatusBadge } from '../components/VehicleServiceStatusBadge';
import { cancelVehicleServiceJob, completeVehicleServiceJob, deleteVehicleServiceJob, getVehicleServiceJob, inspectVehicleServiceJob, startVehicleServiceJob } from '../vehicleServiceApi';

const InspectionTab = lazy(() => import('../components/VehicleServiceInspectionTab'));
const LinesTab = lazy(() => import('../components/VehicleServiceLineEditor'));
const WorkforceTab = lazy(() => import('../components/VehicleServiceEmployeeAssignmentTab'));
const InventoryTab = lazy(() => import('../components/VehicleServiceInventoryIssueTab'));
const InvoiceTab = lazy(() => import('../components/VehicleServiceInvoiceTab'));
const PaymentTab = lazy(() => import('../components/VehicleServicePaymentTab'));
const DocumentTab = lazy(() => import('../components/VehicleServiceDocumentTab'));
const StatusHistoryTab = lazy(() => import('../components/VehicleServiceStatusHistoryTab'));

type Tab = 'summary' | 'inspection' | 'lines' | 'workforce' | 'inventory' | 'invoice' | 'payments' | 'documents' | 'history';

export default function VehicleServiceJobDetailPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const result = useApi((signal) => getVehicleServiceJob(id, signal), [id]);
    const tabs = useOnDemandTab<Tab>('summary');
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const job = result.data;

    const action = async (name: 'inspect' | 'start' | 'complete' | 'cancel' | 'delete') => {
        setBusy(true);
        setActionError(null);
        try {
            if (name === 'inspect') await inspectVehicleServiceJob(job.id, {});
            if (name === 'start') await startVehicleServiceJob(job.id);
            if (name === 'complete') await completeVehicleServiceJob(job.id);
            if (name === 'cancel') await cancelVehicleServiceJob(job.id);
            if (name === 'delete') {
                await deleteVehicleServiceJob(job.id);
                navigate('/vehicle-service/jobs');
                return;
            }
            result.reload();
        } catch (requestError) {
            setActionError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    const headerActions = (
        <div className="flex flex-wrap gap-2">
            {['draft', 'inspected', 'in_progress'].includes(job.status) && <Link to={`/vehicle-service/jobs/${job.id}/edit`}><Button type="button" variant="secondary">Edit</Button></Link>}
            {job.status === 'draft' && <Button type="button" loading={busy} onClick={() => action('inspect')}>Mark inspected</Button>}
            {['draft', 'inspected'].includes(job.status) && <Button type="button" loading={busy} onClick={() => action('start')}>Start work</Button>}
            {job.status === 'in_progress' && <Button type="button" loading={busy} onClick={() => action('complete')}>Complete</Button>}
            {!['paid', 'cancelled'].includes(job.status) && <Button type="button" variant="danger" loading={busy} onClick={() => action('cancel')}>Cancel</Button>}
            {job.status === 'draft' && <Button type="button" variant="danger" loading={busy} onClick={() => action('delete')}>Delete</Button>}
        </div>
    );
    const sideSummary = (
        <Panel className="rounded-lg">
            <div className="space-y-4">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Workshop status</p>
                    <div className="mt-2"><VehicleServiceStatusBadge status={job.status} /></div>
                </div>
                <DetailGrid items={[
                    { label: 'Customer', value: readableRelation(job.customer) },
                    { label: 'Registration', value: job.vehicle?.registration_number ?? readableRelation(job.vehicle) },
                    { label: 'Make / model', value: `${job.vehicle?.make?.name ?? '-'} / ${job.vehicle?.model?.name ?? '-'}` },
                    { label: 'Vehicle owner', value: readableRelation(job.vehicle?.customer ?? job.customer) },
                    { label: 'Odometer', value: `${job.odometer_reading ?? job.vehicle?.odometer_reading ?? '-'} ${job.vehicle?.odometer_unit ?? ''}`.trim() },
                    { label: 'Supervisor', value: readableRelation(job.supervisor) },
                    { label: 'Expected delivery', value: formatDate(job.expected_delivery_date) },
                    { label: 'Grand total', value: <MoneyDisplay value={job.grand_total} /> },
                ]} />
            </div>
        </Panel>
    );

    return (
        <>
            <ContentHeader title={job.job_number} description={`${job.customer?.name ?? 'Customer'} / ${job.vehicle?.name ?? 'Vehicle'}`} actions={headerActions} />
            <ErrorAlert error={actionError ?? result.error} />
            <EntityDetailLayout
                summary={sideSummary}
                actions={
                    <>
                        <Button type="button" variant="secondary" className="w-full" onClick={() => tabs.openTab('inspection')}>Open inspection</Button>
                        <Button type="button" variant="secondary" className="w-full" onClick={() => tabs.openTab('lines')}>Open job lines</Button>
                        <Button type="button" variant="secondary" className="w-full" onClick={() => tabs.openTab('workforce')}>Assign workforce</Button>
                        {['completed', 'invoiced'].includes(job.status) && <Link to={`/vehicle-service/jobs/${job.id}/invoice`}><Button type="button" variant="secondary" className="w-full">Create invoice</Button></Link>}
                        {(job.invoice_links ?? []).some((link) => link.status === 'active' && compareDecimalStrings(link.balance_due ?? '0', '0') > 0) && <Link to={`/vehicle-service/jobs/${job.id}/payment`}><Button type="button" variant="secondary" className="w-full">Receive payment</Button></Link>}
                    </>
                }
            >
                <Panel className="p-0">
                    <Tabs tabs={[
                        { id: 'summary', label: 'Overview' },
                        { id: 'inspection', label: 'Inspection' },
                        { id: 'lines', label: 'Job lines' },
                        { id: 'workforce', label: 'Workforce' },
                        { id: 'inventory', label: 'Inventory' },
                        { id: 'invoice', label: 'Invoice' },
                        { id: 'payments', label: 'Payments' },
                        { id: 'documents', label: 'Documents' },
                        { id: 'history', label: 'Timeline' },
                    ]} active={tabs.activeTab} onChange={tabs.openTab} />
                    <div className="p-5">
                        <Suspense fallback={<LoadingState />}>
                            {tabs.activeTab === 'summary' && <VehicleServiceSummaryPanel job={job} />}
                            {tabs.openedTabs.has('inspection') && tabs.activeTab === 'inspection' && <InspectionTab jobId={job.id} />}
                            {tabs.openedTabs.has('lines') && tabs.activeTab === 'lines' && <LinesTab jobId={job.id} />}
                            {tabs.openedTabs.has('workforce') && tabs.activeTab === 'workforce' && <WorkforceTab jobId={job.id} />}
                            {tabs.openedTabs.has('inventory') && tabs.activeTab === 'inventory' && <InventoryTab jobId={job.id} />}
                            {tabs.openedTabs.has('invoice') && tabs.activeTab === 'invoice' && <InvoiceTab job={job} />}
                            {tabs.openedTabs.has('payments') && tabs.activeTab === 'payments' && <PaymentTab job={job} />}
                            {tabs.openedTabs.has('documents') && tabs.activeTab === 'documents' && <DocumentTab jobId={job.id} />}
                            {tabs.openedTabs.has('history') && tabs.activeTab === 'history' && <StatusHistoryTab jobId={job.id} />}
                        </Suspense>
                    </div>
                </Panel>
            </EntityDetailLayout>
        </>
    );
}
