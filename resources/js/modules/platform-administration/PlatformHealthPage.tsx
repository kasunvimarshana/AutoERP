import { useState, type ReactNode } from 'react';
import { PLATFORM_HOME_PATH } from '@/app/routePaths';
import { PLATFORM_PERMISSION } from '@/app/access/platformPermissions';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { listPlatformTenantTargets } from '@/modules/tenant/tenantApi';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { platformAdministrationApi } from './platformAdministrationApi';
import { formatBytes, formatPlatformDateTime, humanizePlatformValue, platformAuditHref } from './platformAdministrationPresentation';
import type {
    PlatformHealthOverview,
    PlatformOutboxFailure,
    PlatformStorageFailure,
    PlatformTenantHealthDetail,
} from './platformAdministrationTypes';

interface TenantOption extends NamedResource {
    code: string;
}

type RecoveryAction =
    | { type: 'outbox'; failure: PlatformOutboxFailure }
    | { type: 'storage'; failure: PlatformStorageFailure }
    | { type: 'domains'; tenantId: number | null }
    | null;

export default function PlatformHealthPage() {
    const auth = useAuth();
    const canRecover = hasPermission(auth, PLATFORM_PERMISSION.healthManage);
    const overview = useApi((signal) => platformAdministrationApi.getHealth(signal), [], true, false);
    const [tenant, setTenant] = useState<TenantOption | null>(null);
    const tenantHealth = useApi(
        (signal) => platformAdministrationApi.getTenantHealth(tenant?.id ?? 0, signal),
        [tenant?.id ?? null],
        tenant !== null,
        true,
    );
    const [action, setAction] = useState<RecoveryAction>(null);
    const [reason, setReason] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    async function recover() {
        if (!action) return;
        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            const count = action.type === 'outbox'
                ? await platformAdministrationApi.retryOutbox(action.failure.event_uuid, reason.trim())
                : action.type === 'storage'
                    ? await platformAdministrationApi.retryStorageCleanup(action.failure.job_id, reason.trim())
                    : await platformAdministrationApi.retryFailedDomains(action.tenantId, reason.trim());
            setSuccess(`${count} failed operation${count === 1 ? '' : 's'} returned to the retry queue.`);
            setAction(null);
            setReason('');
            overview.reload();
            tenantHealth.reload();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            overview.reload();
        } finally {
            setSaving(false);
        }
    }

    const data = overview.data;
    return (
        <>
            <ContentHeader
                title="Platform health"
                description="Monitor tenant onboarding, domain readiness, subscriptions, durable operations, and tracked private storage from one control-plane view."
                actions={<Button variant="secondary" disabled={overview.loading} onClick={overview.reload}>Refresh health</Button>}
            />

            <div className="space-y-5">
                <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
                <ErrorAlert error={overview.error} title="Unable to load platform health" />
                <ErrorAlert error={tenantHealth.error} title="Unable to load tenant health" />
                <ErrorAlert error={error} title="Recovery action failed" />

                {overview.loading && !data ? <LoadingState label="Loading platform health..." /> : null}
                {data ? (
                    <>
                        <section className={`rounded-xl border p-4 ${data.alerts.requires_attention ? 'border-amber-300 bg-amber-50' : 'border-emerald-200 bg-emerald-50'}`}>
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="font-semibold text-slate-950">{data.alerts.requires_attention ? 'Platform operations require attention' : 'No tracked platform failures'}</p>
                                    <p className="mt-1 text-sm text-slate-700">
                                        Generated {formatPlatformDateTime(data.generated_at)} · {data.release.environment} · {humanizePlatformValue(data.release.database_strategy)}
                                    </p>
                                </div>
                                <div className="text-right text-xs text-slate-600">
                                    <p>Release: {data.release.release_id ?? 'Not configured'}</p>
                                    <p>Commit: {data.release.commit ?? 'Not configured'}</p>
                                </div>
                            </div>
                        </section>

                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <Metric title="Active tenants" value={data.tenants.active ?? 0} supporting={`${data.tenants.draft ?? 0} draft · ${data.tenants.suspended ?? 0} suspended`} />
                            <Metric title="Onboarding failures" value={data.alerts.onboarding_failures} supporting={`${data.onboarding.ready ?? 0} ready · ${data.onboarding.completed ?? 0} completed`} attention={data.alerts.onboarding_failures > 0} />
                            <Metric title="Domain failures" value={data.alerts.domain_failures} supporting={`${data.domains.operational.ready ?? 0} operationally ready`} attention={data.alerts.domain_failures > 0} />
                            <Metric title="Tracked storage" value={formatBytes(data.storage.tracked_document_bytes)} supporting={`${data.storage.tracked_document_count} document(s)`} />
                            <Metric title="Dead outbox events" value={data.alerts.dead_outbox_events} supporting={`${data.operations.outbox.pending ?? 0} pending`} attention={data.alerts.dead_outbox_events > 0} />
                            <Metric title="Dead storage cleanup" value={data.alerts.dead_storage_cleanup_jobs} supporting={`${data.operations.storage_cleanup.pending ?? 0} pending`} attention={data.alerts.dead_storage_cleanup_jobs > 0} />
                            <Metric title="Assigned subscriptions" value={data.subscriptions.assigned ?? 0} supporting={`${data.subscriptions.expired ?? 0} expired · ${data.subscriptions.cancelled ?? 0} cancelled`} />
                            <Metric title="Verified domains" value={data.domains.ownership.verified ?? 0} supporting={`${data.domains.ownership.pending ?? 0} pending ownership`} />
                        </div>

                        <InfrastructureHealth health={data.infrastructure} />
                        <FailurePanels overview={data} canRecover={canRecover} onRecover={(next) => { setAction(next); setReason(''); setError(null); }} />
                    </>
                ) : null}

                <Panel title="Tenant health drill-down">
                    <div className="grid gap-4 lg:grid-cols-[minmax(280px,1fr)_auto] lg:items-end">
                        <GenericLookupSelect
                            label="Tenant"
                            value={tenant}
                            onChange={(selected) => { setTenant(selected); return true; }}
                            search={searchTenants}
                            formatLabel={(selected) => `${selected.name} · ${selected.code}`}
                            placeholder="Search tenant name or code"
                            minSearchLength={0}
                            loadOnOpen
                        />
                        {tenant ? <LinkButton variant="secondary" to={`${PLATFORM_HOME_PATH}?tenant=${tenant.id}`}>Open tenant administration</LinkButton> : null}
                    </div>
                    {tenantHealth.loading ? <LoadingState label="Loading tenant health..." /> : null}
                    {tenantHealth.data ? <TenantHealth detail={tenantHealth.data} canAudit={hasPermission(auth, PLATFORM_PERMISSION.auditView)} /> : tenant ? null : <p className="mt-4 text-sm text-slate-500">Select a tenant to inspect onboarding, domain, subscription, storage, and outbox state.</p>}
                </Panel>
            </div>

            <Modal open={action !== null} title="Retry failed platform operation" onClose={() => setAction(null)} closeDisabled={saving}>
                <div className="space-y-5">
                    <p className="text-sm text-slate-700">
                        {action?.type === 'domains'
                            ? 'This queues a bounded batch of failed operational domain checks. DNS ownership failures still require a fresh guided challenge on the tenant domain screen.'
                            : 'This returns the selected durable operation to its retry queue. It does not bypass validation or mark the operation successful.'}
                    </p>
                    <Textarea
                        label="Recovery reason"
                        required
                        minLength={10}
                        maxLength={500}
                        value={reason}
                        onChange={(event) => setReason(event.target.value)}
                        hint="At least 10 characters. The recovery action and reason are recorded in the platform audit trail."
                    />
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button>
                        <Button loading={saving} disabled={reason.trim().length < 10} onClick={recover}>{action?.type === 'domains' ? 'Queue domain rechecks' : 'Return to retry queue'}</Button>
                    </div>
                </div>
            </Modal>
        </>
    );
}

function FailurePanels({ overview, canRecover, onRecover }: {
    overview: PlatformHealthOverview;
    canRecover: boolean;
    onRecover: (action: Exclude<RecoveryAction, null>) => void;
}) {
    return (
        <div className="grid gap-5 xl:grid-cols-2">
            <Panel title="Onboarding failures">
                <FailureList
                    rows={overview.failures.onboarding}
                    empty="No failed tenant onboarding workflows."
                    render={(failure) => (
                        <div>
                            <FailureHeader tenantName={failure.tenant_name} tenantCode={failure.tenant_code} />
                            <p className="mt-1 text-sm text-slate-700">{humanizePlatformValue(failure.failed_step)} · {failure.error_message ?? failure.error_code ?? 'Safe failure details unavailable'}</p>
                            <LinkButton className="mt-3 min-h-8 px-3 py-1 text-xs" variant="secondary" to={`${PLATFORM_HOME_PATH}?tenant=${failure.tenant_id}`}>Open tenant</LinkButton>
                        </div>
                    )}
                />
            </Panel>
            <Panel title="Domain failures">
                {canRecover && overview.failures.domains.some((failure) => failure.operational_status === 'failed' && failure.ownership_status === 'verified') ? (
                    <div className="mb-3 flex justify-end">
                        <Button variant="secondary" className="min-h-8 px-3 py-1 text-xs" onClick={() => onRecover({ type: 'domains', tenantId: null })}>
                            Recheck failed operational domains
                        </Button>
                    </div>
                ) : null}
                <FailureList
                    rows={overview.failures.domains}
                    empty="No failed tenant-domain checks."
                    render={(failure) => (
                        <div>
                            <FailureHeader tenantName={failure.tenant_name} tenantCode={failure.tenant_code} />
                            <p className="mt-1 font-medium text-slate-900">{failure.domain}</p>
                            <p className="text-sm text-slate-700">Ownership: {humanizePlatformValue(failure.ownership_status)} · Operational: {humanizePlatformValue(failure.operational_status)}</p>
                            <p className="mt-1 text-xs text-slate-500">{failure.error_message ?? failure.error_code ?? 'Safe failure details unavailable'}</p>
                            <LinkButton className="mt-3 min-h-8 px-3 py-1 text-xs" variant="secondary" to={`${PLATFORM_HOME_PATH}?tenant=${failure.tenant_id}`}>Review domain</LinkButton>
                        </div>
                    )}
                />
            </Panel>
            <Panel title="Dead outbox events">
                <FailureList
                    rows={overview.failures.outbox}
                    empty="No dead tenant outbox events."
                    render={(failure) => (
                        <div>
                            <FailureHeader tenantName={failure.tenant_name} tenantCode={failure.tenant_code} />
                            <p className="mt-1 font-medium text-slate-900">{humanizePlatformValue(failure.event_type)}</p>
                            <p className="text-sm text-slate-700">{failure.attempts} delivery attempt(s) · failed {formatPlatformDateTime(failure.failed_at)}</p>
                            <p className="mt-1 text-xs text-slate-500">{failure.error_message ?? failure.error_code ?? 'Safe failure details unavailable'}</p>
                            {canRecover ? <Button className="mt-3 min-h-8 px-3 py-1 text-xs" onClick={() => onRecover({ type: 'outbox', failure })}>Retry event</Button> : null}
                        </div>
                    )}
                />
            </Panel>
            <Panel title="Dead storage cleanup jobs">
                <FailureList
                    rows={overview.failures.storage_cleanup}
                    empty="No dead private-storage cleanup jobs."
                    render={(failure) => (
                        <div>
                            <FailureHeader tenantName={failure.tenant_name} tenantCode={failure.tenant_code} />
                            <p className="mt-1 font-medium text-slate-900">{humanizePlatformValue(failure.reason)}</p>
                            <p className="text-sm text-slate-700">{failure.attempts} cleanup attempt(s) · failed {formatPlatformDateTime(failure.failed_at)}</p>
                            <p className="mt-1 text-xs text-slate-500">{failure.error_message ?? failure.error_code ?? 'Safe failure details unavailable'}</p>
                            {canRecover ? <Button className="mt-3 min-h-8 px-3 py-1 text-xs" onClick={() => onRecover({ type: 'storage', failure })}>Retry cleanup</Button> : null}
                        </div>
                    )}
                />
            </Panel>
        </div>
    );
}

function TenantHealth({ detail, canAudit }: { detail: PlatformTenantHealthDetail; canAudit: boolean }) {
    return (
        <div className="mt-5 space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-4">
                <div>
                    <p className="font-semibold text-slate-950">{detail.tenant.name}</p>
                    <p className="text-sm text-slate-600">{detail.tenant.code}</p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    {canAudit ? <LinkButton variant="secondary" to={platformAuditHref({ tenant_id: detail.tenant.id, subject_type: 'tenant', subject_id: detail.tenant.id })}>View tenant audit</LinkButton> : null}
                    <StatusBadge status={detail.tenant.status} />
                </div>
            </div>
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <SmallMetric label="Onboarding" value={detail.onboarding?.status ?? 'Not started'} />
                <SmallMetric label="Subscription" value={detail.subscription ? `${detail.subscription.plan ?? 'Plan'} · ${detail.subscription.state}` : 'Not assigned'} />
                <SmallMetric label="Domains" value={`${detail.domains.length} configured`} />
                <SmallMetric label="Private documents" value={`${detail.storage.tracked_document_count} · ${formatBytes(detail.storage.tracked_document_bytes)}`} />
            </div>
            <InfrastructureHealth health={detail.infrastructure} />
            <div className={`rounded-lg border p-4 ${detail.storage.reconciliation.healthy ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'}`}>
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm font-semibold text-slate-900">Private storage reconciliation</p>
                        <p className="mt-1 text-xs text-slate-600">
                            Measured {formatPlatformDateTime(detail.storage.reconciliation.measured_at)} against the tenant document prefix.
                        </p>
                    </div>
                    <StatusBadge status={detail.storage.reconciliation.healthy ? 'healthy' : 'attention'} />
                </div>
                {detail.storage.reconciliation.storage_reachable ? (
                    <div className="mt-3 grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <p>Tracked: {detail.storage.reconciliation.tracked_files} · {formatBytes(detail.storage.reconciliation.tracked_bytes)}</p>
                        <p>Stored: {detail.storage.reconciliation.actual_files} · {formatBytes(detail.storage.reconciliation.actual_bytes)}</p>
                        <p>Missing / orphan: {detail.storage.reconciliation.missing_files} / {detail.storage.reconciliation.orphan_files}</p>
                        <p>Mismatch / unreadable: {detail.storage.reconciliation.size_mismatches} / {detail.storage.reconciliation.unreadable_files}</p>
                    </div>
                ) : (
                    <p className="mt-3 text-sm text-amber-900">{detail.storage.reconciliation.error_message ?? 'Private storage is unavailable for reconciliation.'}</p>
                )}
                {detail.storage.reconciliation.invalid_metadata_paths > 0 ? (
                    <p className="mt-2 text-xs text-amber-900">{detail.storage.reconciliation.invalid_metadata_paths} document record(s) contain an invalid tenant storage path.</p>
                ) : null}
            </div>
            {detail.capacity ? (
                <div>
                    <div className="flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <h3 className="text-sm font-semibold text-slate-900">Plan capacity</h3>
                            <p className="mt-1 text-xs text-slate-500">Measured {formatPlatformDateTime(detail.capacity.measured_at)} from authoritative module usage contributors.</p>
                        </div>
                        {detail.capacity.blockers.length > 0 ? <StatusBadge status="attention" /> : <StatusBadge status="within_limits" />}
                    </div>
                    {Object.keys(detail.capacity.limits).length > 0 ? (
                        <div className="mt-2 grid gap-2 md:grid-cols-2">
                            {Object.entries(detail.capacity.limits).map(([key, limit]) => {
                                const usage = detail.capacity?.usage[key] ?? 0;
                                const percentage = detail.capacity?.utilization_percent[key];
                                return (
                                    <div key={key} className="rounded-lg border border-slate-200 p-3">
                                        <div className="flex items-center justify-between gap-3">
                                            <p className="text-sm font-medium text-slate-900">{humanizePlatformValue(key)}</p>
                                            <p className="text-sm text-slate-700">{usage} / {limit}</p>
                                        </div>
                                        <p className="mt-1 text-xs text-slate-500">{percentage === null || percentage === undefined ? 'Utilization unavailable' : `${percentage}% utilized`}</p>
                                    </div>
                                );
                            })}
                        </div>
                    ) : <p className="mt-2 text-sm text-slate-500">The current plan has no configured usage limits.</p>}
                    {detail.capacity.blockers.length > 0 ? (
                        <div className="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                            <p className="font-semibold">Capacity attention required</p>
                            <ul className="mt-1 list-disc space-y-1 pl-5">{detail.capacity.blockers.map((blocker) => <li key={blocker.code}>{blocker.message}</li>)}</ul>
                        </div>
                    ) : null}
                </div>
            ) : null}
            {detail.onboarding?.steps.length ? (
                <div>
                    <h3 className="text-sm font-semibold text-slate-900">Foundation steps</h3>
                    <div className="mt-2 grid gap-2 md:grid-cols-2">
                        {detail.onboarding.steps.map((step) => (
                            <div key={step.step} className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
                                <div>
                                    <p className="text-sm font-medium text-slate-900">{humanizePlatformValue(step.step)}</p>
                                    <p className="text-xs text-slate-500">Owner: {humanizePlatformValue(step.owner_module)} · {step.attempt_count} attempt(s)</p>
                                </div>
                                <StatusBadge status={step.status} />
                            </div>
                        ))}
                    </div>
                </div>
            ) : null}
            {detail.domains.length ? (
                <div>
                    <h3 className="text-sm font-semibold text-slate-900">Domains</h3>
                    <div className="mt-2 space-y-2">
                        {detail.domains.map((domain) => (
                            <div key={domain.id} className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
                                <div>
                                    <p className="text-sm font-medium text-slate-900">{domain.domain}</p>
                                    <p className="text-xs text-slate-500">Ownership: {humanizePlatformValue(domain.ownership_status)} · Operational: {humanizePlatformValue(domain.operational_status)}</p>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    {canAudit ? <LinkButton className="min-h-8 px-3 py-1 text-xs" variant="secondary" to={platformAuditHref({ tenant_id: detail.tenant.id, subject_type: 'tenant_domain', subject_id: domain.id })}>Audit</LinkButton> : null}
                                    <StatusBadge status={domain.operational_status} />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            ) : null}
            <p className="text-xs text-slate-500">Health snapshot generated {formatPlatformDateTime(detail.generated_at)}.</p>
        </div>
    );
}


function InfrastructureHealth({ health }: { health: PlatformHealthOverview['infrastructure'] }) {
    const queueDetail = health.queue.pending_jobs === null
        ? humanizePlatformValue(health.queue.connection ?? 'Not configured')
        : `${humanizePlatformValue(health.queue.connection ?? 'Not configured')} · ${health.queue.pending_jobs} pending`;

    return (
        <Panel title="Operational infrastructure readiness">
            <div className="grid gap-3 md:grid-cols-2">
                <ReadinessItem
                    title="Operational email"
                    ready={health.mail.ready}
                    detail={health.mail.mailer ? `${humanizePlatformValue(health.mail.mailer)} transport` : 'Mail transport is not configured'}
                    guidance={health.mail.ready ? 'External delivery transport and sender identity are configured.' : 'Configure an external mail transport and sender identity before email-dependent operations.'}
                />
                <ReadinessItem
                    title="Queue processing"
                    ready={health.queue.ready}
                    detail={queueDetail}
                    guidance={health.queue.ready ? `${health.queue.failed_jobs ?? 0} failed queued job(s).` : 'Use an asynchronous queue connection and run a supervised queue worker.'}
                />
            </div>
            <div className="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h3 className="text-sm font-semibold text-slate-950">Tenant isolation capabilities</h3>
                <p className="mt-1 text-xs text-slate-600">These are explicit platform capabilities, not editable Laravel configuration keys.</p>
                {health.capabilities ? (
                    <>
                        <div className="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                            <SmallMetric label="Database" value={health.capabilities.database.strategy} />
                            <SmallMetric label="Storage" value={`${health.capabilities.storage.strategy} · ${health.capabilities.storage.isolation}`} />
                            <SmallMetric label="Mail" value={health.capabilities.mail.strategy} />
                            <SmallMetric label="Configuration precedence" value={health.capabilities.configuration.precedence.join(' → ')} />
                        </div>
                        <p className="mt-3 text-xs text-slate-600">Tenant-specific database, storage-provider, and mail-provider profiles are not supported by this release. Shared-schema isolation, private tenant object keys, and the platform mailer are authoritative.</p>
                    </>
                ) : (
                    <p className="mt-3 text-sm text-amber-700">Capability data is unavailable. Deploy matching backend and frontend revisions before relying on this health result.</p>
                )}
            </div>
        </Panel>
    );
}

function ReadinessItem({ title, ready, detail, guidance }: { title: string; ready: boolean; detail: string; guidance: string }) {
    return (
        <article className={`rounded-lg border p-3 ${ready ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'}`}>
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-semibold text-slate-950">{title}</p>
                <StatusBadge status={ready ? 'ready' : 'not_ready'} />
            </div>
            <p className="mt-2 text-sm text-slate-700">{detail}</p>
            <p className="mt-1 text-xs text-slate-600">{guidance}</p>
        </article>
    );
}

function Metric({ title, value, supporting, attention = false }: { title: string; value: string | number; supporting: string; attention?: boolean }) {
    return (
        <article className={`rounded-xl border bg-white p-4 shadow-sm ${attention ? 'border-amber-300' : 'border-slate-200'}`}>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{title}</p>
            <p className={`mt-2 text-2xl font-bold ${attention ? 'text-amber-700' : 'text-slate-950'}`}>{value}</p>
            <p className="mt-1 text-xs text-slate-500">{supporting}</p>
        </article>
    );
}

function SmallMetric({ label, value }: { label: string; value: string }) {
    return <div className="rounded-lg border border-slate-200 p-3"><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><p className="mt-1 text-sm font-medium text-slate-900">{humanizePlatformValue(value)}</p></div>;
}

function FailureHeader({ tenantName, tenantCode }: { tenantName: string; tenantCode: string }) {
    return <p className="text-sm font-semibold text-slate-950">{tenantName} <span className="font-normal text-slate-500">· {tenantCode}</span></p>;
}

function FailureList<T>({ rows, empty, render }: { rows: T[]; empty: string; render: (row: T) => ReactNode }) {
    if (rows.length === 0) return <p className="text-sm text-slate-500">{empty}</p>;
    return <div className="space-y-3">{rows.map((row, index) => <article key={index} className="rounded-lg border border-slate-200 p-3">{render(row)}</article>)}</div>;
}

async function searchTenants({ search, page, perPage, signal }: { search: string; page: number; perPage: number; signal: AbortSignal }) {
    return listPlatformTenantTargets('health', { search, page, perPage, signal });
}
