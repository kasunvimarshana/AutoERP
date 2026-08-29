import { lazy, Suspense, useCallback, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { ApiError, toApiError, type ApiError as ApiErrorShape } from '@/shared/api/apiError';
import { ActionMenu } from '@/shared/components/ActionMenu';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { TabPanel, Tabs } from '@/shared/components/Tabs';
import { WorkflowHeader } from '@/shared/components/WorkflowHeader';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { compareDecimalStrings } from '@/shared/utils/decimal';
import { VehicleServiceJobDiscountValue } from '../components/VehicleServiceJobDiscountValue';
import { VehicleServiceSummaryPanel } from '../components/VehicleServiceSummaryPanel';
import { VehicleServiceStatusBadge } from '../components/VehicleServiceStatusBadge';
import type { VehicleServiceInspection, VehicleServiceJob, VehicleServiceJobLine, VehicleServiceJobStatus, VehicleServiceJobTotals } from '../vehicleServiceTypes';
import { cancelVehicleServiceJob, completeVehicleServiceJob, deleteVehicleServiceJob, getVehicleServiceJob, inspectVehicleServiceJob, listEmployeeAssignableLines, startVehicleServiceJob } from '../vehicleServiceApi';
import { createVehicleServiceJobStore } from '../state/vehicleServiceJobStore';

const InspectionTab = lazy(() => import('../components/VehicleServiceInspectionTab'));
const LinesTab = lazy(() => import('../components/VehicleServiceLineEditor'));
const WorkforceTab = lazy(() => import('../components/VehicleServiceEmployeeAssignmentTab'));
const InvoiceTab = lazy(() => import('../components/VehicleServiceInvoiceTab'));
const PaymentTab = lazy(() => import('../components/VehicleServicePaymentTab'));
const DocumentTab = lazy(() => import('../components/VehicleServiceDocumentTab'));
const StatusHistoryTab = lazy(() => import('../components/VehicleServiceStatusHistoryTab'));

type Tab = 'summary' | 'inspection' | 'lines' | 'workforce' | 'invoice' | 'payments' | 'documents' | 'history';
const INSPECT_WORKFORCE_REQUIRED_MESSAGE = 'Assign at least one labour employee in Workforce before marking this job as inspected.';

export default function VehicleServiceJobDetailPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const jobStore = useMemo(() => createVehicleServiceJobStore(id), [id]);
    const result = useApi((signal) => getVehicleServiceJob(id, signal), [id], true, false);
    const job = result.data;
    const setJob = result.setData;
    const tabs = useOnDemandTab<Tab>('summary');
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiErrorShape | null>(null);
    const [historyRefreshKey, setHistoryRefreshKey] = useState(0);

    const bumpHistory = useCallback(() => {
        setHistoryRefreshKey((current) => current + 1);
    }, []);

    const refreshJobSilently = useCallback(async () => {
        const fresh = await getVehicleServiceJob(id);
        setJob(fresh);
        return fresh;
    }, [id, setJob]);

    const updateJobVersion = useCallback((nextVersion: number) => {
        setJob((current) => current ? { ...current, row_version: nextVersion } : current);
    }, [setJob]);

    const handleInspectionSaved = useCallback((inspection: VehicleServiceInspection, nextVersion: number) => {
        setJob((current) => current ? {
            ...current,
            inspection,
            row_version: nextVersion,
        } : current);
    }, [setJob]);

    const handleLinesChanged = useCallback((lines: VehicleServiceJobLine[], nextVersion: number, totals?: VehicleServiceJobTotals) => {
        setJob((current) => current ? withJobLines(current, lines, nextVersion, totals) : current);
    }, [setJob]);

    if (result.loading && job === null) return <LoadingState />;
    if (!job) return <ErrorAlert error={result.error} />;
    const expectedVersion = job.row_version ?? 0;

    const action = async (name: 'inspect' | 'start' | 'complete' | 'cancel' | 'delete') => {
        setBusy(true);
        setActionError(null);
        try {
            if (name === 'inspect') {
                const workforceLines = await listEmployeeAssignableLines(job.id);
                if (requiresWorkforceBeforeInspect(workforceLines)) {
                    tabs.openTab('workforce');
                    setActionError(new ApiError(
                        INSPECT_WORKFORCE_REQUIRED_MESSAGE,
                        422,
                        'VEHICLE_SERVICE_WORKFORCE_REQUIRED',
                        'validation',
                    ));
                    return;
                }
                await inspectVehicleServiceJob(job.id, { expected_version: expectedVersion });
                await refreshJobSilently();
                bumpHistory();
                return;
            }
            if (name === 'start') {
                const updated = await startVehicleServiceJob(job.id, expectedVersion);
                setJob((current) => current ? withStatusUpdate(current, updated.status, updated.row_version ?? expectedVersion + 1, updated.completed_at ?? null) : current);
                bumpHistory();
                return;
            }
            if (name === 'complete') {
                const updated = await completeVehicleServiceJob(job.id, expectedVersion);
                setJob((current) => current ? withStatusUpdate(current, updated.status, updated.row_version ?? expectedVersion + 1, updated.completed_at ?? null) : current);
                bumpHistory();
                return;
            }
            if (name === 'cancel') {
                const updated = await cancelVehicleServiceJob(job.id, expectedVersion);
                setJob((current) => current ? withStatusUpdate(current, updated.status, updated.row_version ?? expectedVersion + 1, updated.completed_at ?? current.completed_at ?? null) : current);
                bumpHistory();
                return;
            }
            if (name === 'delete') {
                await deleteVehicleServiceJob(job.id, expectedVersion);
                navigate('/vehicle-service/jobs');
                return;
            }
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
            <Panel className="p-0">
                <Tabs id="service-job-tabs" tabs={[
                    { id: 'summary', label: 'Overview' },
                    { id: 'inspection', label: 'Inspection' },
                    { id: 'lines', label: 'Job lines' },
                    { id: 'workforce', label: 'Workforce' },
                    { id: 'invoice', label: 'Invoice' },
                    { id: 'payments', label: 'Payments' },
                    { id: 'documents', label: 'Documents' },
                    { id: 'history', label: 'Timeline' },
                ]} active={tabs.activeTab} onChange={tabs.openTab} />
                <div className="p-5">
                    <Suspense fallback={<LoadingState />}>
                        <TabPanel tabsId="service-job-tabs" tabId="summary" active={tabs.activeTab} keepMounted><VehicleServiceSummaryPanel job={job} /></TabPanel>
                        {tabs.openedTabs.has('inspection') && <TabPanel tabsId="service-job-tabs" tabId="inspection" active={tabs.activeTab} keepMounted><InspectionTab jobId={job.id} expectedVersion={expectedVersion} initialValue={job.inspection ?? null} onSaved={handleInspectionSaved} /></TabPanel>}
                        {tabs.openedTabs.has('lines') && (
                            <TabPanel tabsId="service-job-tabs" tabId="lines" active={tabs.activeTab} keepMounted>
                                <div className="space-y-5">
                                    <LinesTab jobId={job.id} expectedVersion={expectedVersion} onChanged={handleLinesChanged} onVersionChanged={updateJobVersion} jobStore={jobStore} />
                                    <VehicleServiceJobDiscountValue job={job} onChanged={setJob} />
                                </div>
                            </TabPanel>
                        )}
                        {tabs.openedTabs.has('workforce') && <TabPanel tabsId="service-job-tabs" tabId="workforce" active={tabs.activeTab} keepMounted><WorkforceTab jobId={job.id} expectedVersion={expectedVersion} onChanged={updateJobVersion} active={tabs.activeTab === 'workforce'} jobStore={jobStore} /></TabPanel>}
                        {tabs.openedTabs.has('invoice') && <TabPanel tabsId="service-job-tabs" tabId="invoice" active={tabs.activeTab} keepMounted><InvoiceTab job={job} /></TabPanel>}
                        {tabs.openedTabs.has('payments') && <TabPanel tabsId="service-job-tabs" tabId="payments" active={tabs.activeTab} keepMounted><PaymentTab job={job} /></TabPanel>}
                        {tabs.openedTabs.has('documents') && <TabPanel tabsId="service-job-tabs" tabId="documents" active={tabs.activeTab} keepMounted><DocumentTab jobId={job.id} expectedVersion={expectedVersion} onChanged={updateJobVersion} /></TabPanel>}
                        {tabs.openedTabs.has('history') && <TabPanel tabsId="service-job-tabs" tabId="history" active={tabs.activeTab} keepMounted><StatusHistoryTab jobId={job.id} refreshKey={historyRefreshKey} /></TabPanel>}
                    </Suspense>
                </div>
            </Panel>
            <section className="mt-5 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <h2 className="text-xs font-semibold uppercase tracking-wide text-slate-500">Quick actions</h2>
                <div className="flex flex-wrap items-center gap-2">
                    <Button type="button" variant="secondary" onClick={() => tabs.openTab('inspection')}>Open inspection</Button>
                    <Button type="button" variant="secondary" onClick={() => tabs.openTab('lines')}>Open job lines</Button>
                    <Button type="button" variant="secondary" onClick={() => tabs.openTab('workforce')}>Assign workforce</Button>
                    {['completed', 'invoiced'].includes(job.status) && <LinkButton to={`/vehicle-service/jobs/${job.id}/invoice`} variant="secondary">Create & post invoice</LinkButton>}
                    {(job.invoice_links ?? []).some((link) => link.status === 'active' && compareDecimalStrings(link.balance_due ?? '0', '0') > 0) && <LinkButton to={`/vehicle-service/jobs/${job.id}/payment`} variant="secondary">Receive payment</LinkButton>}
                </div>
            </section>
        </>
    );
}

function withStatusUpdate(
    job: VehicleServiceJob,
    status: VehicleServiceJobStatus,
    rowVersion: number,
    completedAt: string | null,
): VehicleServiceJob {
    return {
        ...job,
        status,
        status_label: humanizeStatus(status),
        row_version: rowVersion,
        completed_at: completedAt,
        lines: status === 'completed'
            ? (job.lines ?? []).map((line) => line.status === 'cancelled' ? line : { ...line, status: 'completed' })
            : job.lines,
    };
}

export function withJobLines(
    job: VehicleServiceJob,
    lines: VehicleServiceJobLine[],
    rowVersion: number,
    totals?: VehicleServiceJobTotals,
): VehicleServiceJob {
    return {
        ...job,
        row_version: rowVersion,
        lines,
        ...(totals ?? {}),
        job_discount: job.job_discount && totals ? {
            ...job.job_discount,
            calculation_base: totals.job_discount_base,
            calculated_amount: totals.job_discount_amount,
        } : job.job_discount,
    };
}

function humanizeStatus(status: VehicleServiceJobStatus): string {
    return status
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

export function requiresWorkforceBeforeInspect(lines: VehicleServiceJobLine[]): boolean {
    return lines.length > 0 && !lines.some((line) =>
        (line.employee_assignments ?? []).some((assignment) => assignment.status !== 'cancelled'));
}
