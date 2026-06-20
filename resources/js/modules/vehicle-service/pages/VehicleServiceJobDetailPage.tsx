import { lazy, Suspense, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ActionMenu } from '@/shared/components/ActionMenu';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { EntityDetailLayout } from '@/shared/components/EntityDetailLayout';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { TabPanel, Tabs } from '@/shared/components/Tabs';
import { WorkflowHeader } from '@/shared/components/WorkflowHeader';
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
    const currentCustomerOwner = job.vehicle?.current_ownerships?.find((ownership) => ownership.owner_type === 'customer')?.owner ?? job.customer;

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

    const nextAction = job.status === 'draft'
        ? { label: 'Mark inspected', action: 'inspect' as const }
        : job.status === 'inspected'
            ? { label: 'Start work', action: 'start' as const }
            : job.status === 'in_progress'
                ? { label: 'Complete job', action: 'complete' as const }
                : null;
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
                    { label: 'Vehicle owner', value: readableRelation(currentCustomerOwner) },
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
            <ContentHeader title={job.job_number} description={`${job.customer?.name ?? 'Customer'} / ${job.vehicle?.name ?? 'Vehicle'}`} />
            <WorkflowHeader
                status={<VehicleServiceStatusBadge status={job.status} />}
                nextAction={nextAction ? <Button type="button" loading={busy} onClick={() => action(nextAction.action)}>{nextAction.label}</Button> : undefined}
                historyAction={<Button type="button" variant="ghost" onClick={() => tabs.openTab('history')}>History</Button>}
                secondaryActions={<>
                    {['draft', 'inspected', 'in_progress'].includes(job.status) && <LinkButton to={`/vehicle-service/jobs/${job.id}/edit`} variant="secondary">Edit</LinkButton>}
                    {!['paid', 'cancelled'].includes(job.status) && (
                        <ActionMenu>
                            <Button type="button" variant="ghost" className="w-full justify-start text-rose-700" loading={busy} onClick={() => action('cancel')}>Cancel job</Button>
                            {job.status === 'draft' && <Button type="button" variant="ghost" className="w-full justify-start text-rose-700" loading={busy} onClick={() => action('delete')}>Delete draft</Button>}
                        </ActionMenu>
                    )}
                </>}
                blockedReason={job.status === 'cancelled' ? 'No further actions are available for a cancelled job.' : undefined}
            />
            <ErrorAlert error={actionError ?? result.error} />
            <EntityDetailLayout
                summary={sideSummary}
                actions={
                    <>
                        <Button type="button" variant="secondary" className="w-full" onClick={() => tabs.openTab('inspection')}>Open inspection</Button>
                        <Button type="button" variant="secondary" className="w-full" onClick={() => tabs.openTab('lines')}>Open job lines</Button>
                        <Button type="button" variant="secondary" className="w-full" onClick={() => tabs.openTab('workforce')}>Assign workforce</Button>
                        {['completed', 'invoiced'].includes(job.status) && <LinkButton to={`/vehicle-service/jobs/${job.id}/invoice`} variant="secondary" className="w-full">Create & post invoice</LinkButton>}
                        {(job.invoice_links ?? []).some((link) => link.status === 'active' && compareDecimalStrings(link.balance_due ?? '0', '0') > 0) && <LinkButton to={`/vehicle-service/jobs/${job.id}/payment`} variant="secondary" className="w-full">Receive payment</LinkButton>}
                    </>
                }
            >
                <Panel className="p-0">
                    <Tabs id="service-job-tabs" tabs={[
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
                            <TabPanel tabsId="service-job-tabs" tabId="summary" active={tabs.activeTab}><VehicleServiceSummaryPanel job={job} /></TabPanel>
                            {tabs.openedTabs.has('inspection') && <TabPanel tabsId="service-job-tabs" tabId="inspection" active={tabs.activeTab}><InspectionTab jobId={job.id} /></TabPanel>}
                            {tabs.openedTabs.has('lines') && <TabPanel tabsId="service-job-tabs" tabId="lines" active={tabs.activeTab}><LinesTab jobId={job.id} /></TabPanel>}
                            {tabs.openedTabs.has('workforce') && <TabPanel tabsId="service-job-tabs" tabId="workforce" active={tabs.activeTab}><WorkforceTab jobId={job.id} /></TabPanel>}
                            {tabs.openedTabs.has('inventory') && <TabPanel tabsId="service-job-tabs" tabId="inventory" active={tabs.activeTab}><InventoryTab jobId={job.id} /></TabPanel>}
                            {tabs.openedTabs.has('invoice') && <TabPanel tabsId="service-job-tabs" tabId="invoice" active={tabs.activeTab}><InvoiceTab job={job} /></TabPanel>}
                            {tabs.openedTabs.has('payments') && <TabPanel tabsId="service-job-tabs" tabId="payments" active={tabs.activeTab}><PaymentTab job={job} /></TabPanel>}
                            {tabs.openedTabs.has('documents') && <TabPanel tabsId="service-job-tabs" tabId="documents" active={tabs.activeTab}><DocumentTab jobId={job.id} /></TabPanel>}
                            {tabs.openedTabs.has('history') && <TabPanel tabsId="service-job-tabs" tabId="history" active={tabs.activeTab}><StatusHistoryTab jobId={job.id} /></TabPanel>}
                        </Suspense>
                    </div>
                </Panel>
            </EntityDetailLayout>
        </>
    );
}
