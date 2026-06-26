import { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PLATFORM_PERMISSION } from '@/app/access/platformPermissions';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { listActiveReferenceRecords } from '@/modules/reference-data/referenceDataApi';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { PlatformTenantActivationPanel } from './components/PlatformTenantActivationPanel';
import { PlatformTenantDomainsPanel } from './components/PlatformTenantDomainsPanel';
import { PlatformTenantForm } from './components/PlatformTenantForm';
import { PlatformTenantOnboardingPanel } from './components/PlatformTenantOnboardingPanel';
import { PlatformTenantSubscriptionPanel } from './components/PlatformTenantSubscriptionPanel';
import { TenantDirectoryCard } from './components/TenantDirectoryCard';
import { TenantIdentitySummary } from './components/TenantIdentitySummary';
import {
    BlockedTenantSetupStep,
    resolveTenantSetupStep,
    TenantSetupNavigation,
    tenantFoundationProvisioned,
} from './components/TenantSetupNavigation';
import { TenantPlanLookupSelect } from './components/TenantPlanLookupSelect';
import {
    changeTenantStatus,
    createPlatformTenant,
    getPlatformTenant,
    getSubscriptionPlan,
    listPlatformTenants,
    updatePlatformTenant,
} from './tenantApi';
import { humanize } from './tenantPresentation';
import type { TenantRecord } from './tenantTypes';
import { platformAuditHref } from '@/modules/platform-administration/platformAdministrationPresentation';

type LifecycleAction = 'suspend' | 'deactivate' | 'archive';
type FormMode = 'create' | 'edit' | null;

const TENANT_STATUSES = ['draft', 'active', 'inactive', 'suspended', 'archived'] as const;
const ONBOARDING_STATUSES = ['pending', 'provisioning', 'awaiting_administrator', 'awaiting_domain', 'ready', 'completed', 'failed'] as const;
const DOMAIN_OPERATIONAL_STATUSES = ['pending', 'checking', 'ready', 'failed', 'disabled'] as const;
const SUBSCRIPTION_STATES = ['assigned', 'cancelled', 'expired'] as const;
const SUBSCRIPTION_EFFECTIVE_STATUSES = ['scheduled', 'trial', 'active', 'expired', 'cancelled'] as const;
const EXPIRY_WINDOWS = [7, 14, 30, 60, 90] as const;

const ACTION_LABELS: Record<LifecycleAction, string> = {
    suspend: 'Suspend',
    deactivate: 'Deactivate',
    archive: 'Archive',
};

export default function PlatformTenantsPage() {
    const auth = useAuth();
    const canCreate = hasPermission(auth, PLATFORM_PERMISSION.tenantsCreate);
    const canUpdate = hasPermission(auth, PLATFORM_PERMISSION.tenantsUpdate);
    const canOnboard = hasPermission(auth, PLATFORM_PERMISSION.tenantsOnboard);
    const canManageDomains = hasPermission(auth, PLATFORM_PERMISSION.tenantDomainsManage);
    const canManageSubscriptions = hasPermission(auth, PLATFORM_PERMISSION.tenantSubscriptionsManage);
    const canLifecycle = hasPermission(auth, PLATFORM_PERMISSION.tenantsLifecycle);
    const canAudit = hasPermission(auth, PLATFORM_PERMISSION.auditView);
    const [searchParams, setSearchParams] = useSearchParams();
    const page = positiveInteger(searchParams.get('page')) ?? 1;
    const selectedId = positiveInteger(searchParams.get('tenant'));
    const search = searchParams.get('search') ?? '';
    const status = searchParams.get('status') ?? '';
    const onboardingStatus = searchParams.get('onboarding_status') ?? '';
    const domainOperationalStatus = searchParams.get('domain_operational_status') ?? '';
    const subscriptionState = searchParams.get('subscription_state') ?? '';
    const subscriptionEffectiveStatus = searchParams.get('subscription_effective_status') ?? '';
    const activeStep = resolveTenantSetupStep(searchParams.get('step'));
    const subscriptionAction = searchParams.get('subscription_action') === 'assign' ? 'assign' : null;
    const planId = positiveInteger(searchParams.get('plan_id'));
    const expiresWithinDays = positiveInteger(searchParams.get('expires_within_days'));
    const debouncedSearch = useDebounce(search);
    const [formMode, setFormMode] = useState<FormMode>(null);
    const [formRevision, setFormRevision] = useState(0);
    const [formError, setFormError] = useState<ApiError | null>(null);
    const [pageError, setPageError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);
    const [lifecycleReason, setLifecycleReason] = useState('');
    const [pendingAction, setPendingAction] = useState<LifecycleAction | null>(null);
    const [busyAction, setBusyAction] = useState<LifecycleAction | null>(null);

    const tenants = useApi(
        (signal) => listPlatformTenants({
            page,
            per_page: 20,
            search: debouncedSearch || undefined,
            status: status || undefined,
            onboarding_status: onboardingStatus || undefined,
            domain_operational_status: domainOperationalStatus || undefined,
            subscription_state: subscriptionState || undefined,
            subscription_effective_status: subscriptionEffectiveStatus || undefined,
            plan_id: planId ?? undefined,
            expires_within_days: expiresWithinDays ?? undefined,
        }, signal),
        [page, debouncedSearch, status, onboardingStatus, domainOperationalStatus, subscriptionState, subscriptionEffectiveStatus, planId, expiresWithinDays],
        true,
        false,
    );
    const selectedPlan = useApi(
        (signal) => getSubscriptionPlan(planId ?? 0, signal),
        [planId],
        planId !== null && canManageSubscriptions,
        false,
    );
    const currencies = useApi((signal) => listActiveReferenceRecords('currencies', signal), [], true, false);
    const selectedTenant = useApi(
        (signal) => getPlatformTenant(selectedId ?? 0, signal),
        [selectedId],
        selectedId !== null,
        false,
    );
    const selected = selectedTenant.data;
    const formTenant = formMode === 'edit' ? selected : null;
    const allMutationsDisabled = saving || busyAction !== null;

    function updateQuery(updates: Record<string, string | null>) {
        const next = new URLSearchParams(searchParams);
        for (const [key, value] of Object.entries(updates)) {
            if (!value) next.delete(key);
            else next.set(key, value);
        }
        setSearchParams(next, { replace: true });
    }

    function refreshSelected() {
        selectedTenant.reload();
        tenants.reload();
    }

    function selectTenant(id: number) {
        updateQuery({ tenant: String(id) });
        setFormMode(null);
        setFormError(null);
        setPageError(null);
        setSuccess(null);
        setLifecycleReason('');
        setPendingAction(null);
    }

    async function save(payload: FormData) {
        setSaving(true);
        setFormError(null);
        setSuccess(null);
        try {
            const saved = formMode === 'edit' && selected
                ? await updatePlatformTenant(selected.id, payload)
                : await createPlatformTenant(payload);
            setFormMode(null);
            setFormRevision((current) => current + 1);
            updateQuery({ tenant: String(saved.id) });
            selectedTenant.setData(saved);
            tenants.reload();
            setSuccess(formMode === 'edit'
                ? `${saved.name} identity was updated.`
                : `${saved.name} was created as a draft tenant. Continue with foundation provisioning.`);
        } catch (requestError: unknown) {
            setFormError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function confirmLifecycleAction() {
        if (!selected || !pendingAction || lifecycleReason.trim().length < 10) return;
        const action = pendingAction;
        setPendingAction(null);
        setBusyAction(action);
        setPageError(null);
        setSuccess(null);
        try {
            const updated = await changeTenantStatus(selected.id, action, selected.row_version, lifecycleReason.trim());
            selectedTenant.setData(updated);
            tenants.reload();
            setLifecycleReason('');
            setSuccess(`${updated.name} was ${pastTense(action)} successfully.`);
        } catch (requestError: unknown) {
            setPageError(toApiError(requestError));
            refreshSelected();
        } finally {
            setBusyAction(null);
        }
    }

    const formKey = formMode === 'edit' && selected
        ? `edit-${selected.id}-${selected.row_version}`
        : `create-${formRevision}`;

    return (
        <>
            <ContentHeader
                title="SaaS tenants"
                description="Create a tenant identity, complete each controlled setup step, review authoritative readiness, and activate only when every requirement passes."
                actions={canCreate ? (
                    <Button onClick={() => { setFormMode('create'); setFormError(null); setSuccess(null); }} disabled={formMode === 'create'}>
                        Create tenant
                    </Button>
                ) : null}
            />

            <div className="space-y-5">
                <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
                <ErrorAlert error={tenants.error} title="Unable to load tenant directory" />
                <ErrorAlert error={currencies.error} title="Unable to load accounting currencies" />
                <ErrorAlert error={selectedTenant.error} title="Unable to load the selected tenant" />
                <ErrorAlert error={pageError} title="Tenant action failed" />

                {formMode && (formMode === 'create' ? canCreate : canUpdate && selected) ? (
                    <Panel title={formMode === 'edit' && selected ? `Edit ${selected.name} identity` : 'Create a draft tenant'}>
                        <ErrorAlert error={formError} title="Tenant identity could not be saved" />
                        <div className="mt-4">
                            <PlatformTenantForm
                                key={formKey}
                                tenant={formTenant}
                                currencies={currencies.data ?? []}
                                saving={saving}
                                error={formError}
                                onCancel={() => { setFormMode(null); setFormError(null); }}
                                onSubmit={save}
                            />
                        </div>
                    </Panel>
                ) : null}

                <Panel title="Tenant directory">
                    <div className="mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <Input
                            label="Search tenants"
                            value={search}
                            onChange={(event) => updateQuery({ search: event.target.value, page: null })}
                            placeholder="Name, code, slug, or domain"
                        />
                        <Select
                            label="Lifecycle status"
                            value={status}
                            onChange={(event) => updateQuery({ status: event.target.value, page: null })}
                            options={TENANT_STATUSES.map((value) => ({ value, label: humanize(value) }))}
                            placeholder="All lifecycle statuses"
                        />
                        <Select
                            label="Foundation progress"
                            value={onboardingStatus}
                            onChange={(event) => updateQuery({ onboarding_status: event.target.value, page: null })}
                            options={ONBOARDING_STATUSES.map((value) => ({ value, label: humanize(value) }))}
                            placeholder="All foundation states"
                        />
                        <Select
                            label="Primary domain health"
                            value={domainOperationalStatus}
                            onChange={(event) => updateQuery({ domain_operational_status: event.target.value, page: null })}
                            options={DOMAIN_OPERATIONAL_STATUSES.map((value) => ({ value, label: humanize(value) }))}
                            placeholder="All domain states"
                        />
                        <Select
                            label="Assignment state"
                            value={subscriptionState}
                            onChange={(event) => updateQuery({ subscription_state: event.target.value, page: null })}
                            options={SUBSCRIPTION_STATES.map((value) => ({ value, label: humanize(value) }))}
                            placeholder="All assignment states"
                        />
                        <Select
                            label="Effective subscription status"
                            value={subscriptionEffectiveStatus}
                            onChange={(event) => updateQuery({ subscription_effective_status: event.target.value, page: null })}
                            options={SUBSCRIPTION_EFFECTIVE_STATUSES.map((value) => ({ value, label: humanize(value) }))}
                            placeholder="All effective statuses"
                        />
                        <Select
                            label="Subscription expiry"
                            value={expiresWithinDays ? String(expiresWithinDays) : ''}
                            onChange={(event) => updateQuery({ expires_within_days: event.target.value, page: null })}
                            options={EXPIRY_WINDOWS.map((days) => ({ value: String(days), label: `Within ${days} days` }))}
                            placeholder="Any expiry date"
                        />
                        {canManageSubscriptions ? (
                            <TenantPlanLookupSelect
                                label="Assigned plan"
                                value={selectedPlan.data ?? null}
                                onChange={(plan) => updateQuery({ plan_id: plan ? String(plan.id) : null, page: null })}
                                error={selectedPlan.error?.message}
                            />
                        ) : null}
                        <div className="flex items-end">
                            <Button
                                variant="ghost"
                                disabled={!search && !status && !onboardingStatus && !domainOperationalStatus && !subscriptionState && !subscriptionEffectiveStatus && planId === null && expiresWithinDays === null}
                                onClick={() => updateQuery({
                                    search: null,
                                    status: null,
                                    onboarding_status: null,
                                    domain_operational_status: null,
                                    subscription_state: null,
                                    subscription_effective_status: null,
                                    plan_id: null,
                                    expires_within_days: null,
                                    page: null,
                                })}
                            >
                                Clear filters
                            </Button>
                        </div>
                    </div>
                    {tenants.loading && !tenants.data ? <LoadingState label="Loading tenants..." /> : null}
                    {tenants.loading && tenants.data ? <p className="mb-3 text-sm text-slate-500">Refreshing tenant directory...</p> : null}
                    <div className="space-y-3">
                        {(tenants.data?.data ?? []).map((tenant) => <TenantDirectoryCard key={tenant.id} tenant={tenant} selected={selectedId === tenant.id} onSelect={() => selectTenant(tenant.id)} />)}
                        {(tenants.data?.data ?? []).length === 0 && !tenants.loading ? <p className="py-8 text-center text-sm text-slate-500">No tenants match the current filters.</p> : null}
                    </div>
                    <Pagination meta={tenants.data?.meta} onPageChange={(nextPage) => updateQuery({ page: nextPage === 1 ? null : String(nextPage) })} />
                </Panel>

                {selectedId !== null && selectedTenant.loading && !selected ? <LoadingState label="Loading tenant details..." /> : null}
                {selected ? (
                    <div className="space-y-5">
                        <Panel>
                            <div className="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="text-xl font-semibold text-slate-950">{selected.name}</h2>
                                        <StatusBadge status={selected.status} />
                                    </div>
                                    <p className="mt-1 text-sm text-slate-500">{selected.code} · {selected.primary_domain?.domain ?? 'Primary domain required'}</p>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button variant="ghost" onClick={() => updateQuery({ tenant: null })}>Close details</Button>
                                    {canAudit ? <LinkButton variant="secondary" to={platformAuditHref({ tenant_id: selected.id, subject_type: 'tenant', subject_id: selected.id })}>View audit history</LinkButton> : null}
                                    {canUpdate && selected.status !== 'archived' ? <Button variant="secondary" disabled={allMutationsDisabled} onClick={() => { setFormMode('edit'); setFormError(null); updateQuery({ step: 'identity' }); }}>Edit identity</Button> : null}
                                </div>
                            </div>
                            <TenantSetupNavigation
                                tenant={selected}
                                activeStep={activeStep}
                                onSelect={(step) => updateQuery({ step })}
                            />
                        </Panel>

                        {activeStep === 'identity' ? (
                            <Panel>
                                <TenantIdentitySummary tenant={selected} />
                            </Panel>
                        ) : null}

                        {activeStep === 'foundation' ? (
                            <Panel>
                                {selected.base_currency ? (
                                    <PlatformTenantOnboardingPanel
                                        key={`onboarding-${selected.id}-${selected.onboarding?.row_version ?? 0}`}
                                        tenant={selected}
                                        canProvision={canOnboard}
                                        disabled={allMutationsDisabled}
                                        onTenantChanged={refreshSelected}
                                    />
                                ) : <BlockedTenantSetupStep message="Select the tenant base currency in Step 1 before provisioning the foundation." />}
                            </Panel>
                        ) : null}

                        {activeStep === 'domain' ? (
                            <Panel>
                                {tenantFoundationProvisioned(selected) ? (
                                    <PlatformTenantDomainsPanel
                                        key={`domains-${selected.id}`}
                                        tenant={selected}
                                        canManage={canManageDomains}
                                        canAudit={canAudit}
                                        disabled={allMutationsDisabled}
                                        onChanged={refreshSelected}
                                    />
                                ) : <BlockedTenantSetupStep message="Provision the tenant foundation in Step 2 before configuring production domains." />}
                            </Panel>
                        ) : null}

                        {activeStep === 'subscription' ? (
                            <Panel>
                                <PlatformTenantSubscriptionPanel
                                    key={`subscription-${selected.id}-${selected.current_subscription?.row_version ?? 0}-${selectedPlan.data?.id ?? planId ?? 0}`}
                                    tenant={selected}
                                    canManage={canManageSubscriptions}
                                    canAudit={canAudit}
                                    initialPlan={selectedPlan.data}
                                    initialAction={subscriptionAction}
                                    disabled={allMutationsDisabled}
                                    onChanged={refreshSelected}
                                />
                            </Panel>
                        ) : null}

                        {activeStep === 'readiness' ? (
                            <Panel>
                                <PlatformTenantActivationPanel
                                    key={`activation-${selected.id}`}
                                    tenant={selected}
                                    canActivate={canLifecycle}
                                    disabled={allMutationsDisabled}
                                    onChanged={(updated) => { selectedTenant.setData(updated); tenants.reload(); }}
                                />
                            </Panel>
                        ) : null}

                        {activeStep === 'readiness' && canLifecycle && selected.status !== 'archived' ? (
                            <Panel title="Other lifecycle actions">
                                <p className="mb-4 text-sm text-slate-500">Suspension, deactivation, and archival restrict access. Activation is intentionally available only in the final readiness step above.</p>
                                <Textarea
                                    label="Lifecycle reason"
                                    value={lifecycleReason}
                                    onChange={(event) => setLifecycleReason(event.target.value)}
                                    disabled={allMutationsDisabled}
                                    placeholder="Describe the operational reason and expected impact."
                                    hint="Enter at least 10 characters. This reason is retained in the audit trail."
                                />
                                <div className="mt-3 flex flex-wrap justify-end gap-2">
                                    {selected.status === 'active' ? <Button variant="secondary" disabled={lifecycleReason.trim().length < 10 || allMutationsDisabled} onClick={() => setPendingAction('suspend')}>Suspend access</Button> : null}
                                    {['active', 'suspended'].includes(selected.status) ? <Button variant="secondary" disabled={lifecycleReason.trim().length < 10 || allMutationsDisabled} onClick={() => setPendingAction('deactivate')}>Deactivate tenant</Button> : null}
                                    {['draft', 'inactive', 'suspended'].includes(selected.status) ? <Button variant="danger" disabled={lifecycleReason.trim().length < 10 || allMutationsDisabled} onClick={() => setPendingAction('archive')}>Archive tenant</Button> : null}
                                </div>
                            </Panel>
                        ) : null}
                    </div>
                ) : null}
            </div>

            <ConfirmDialog
                open={Boolean(selected && pendingAction)}
                title={pendingAction ? `${ACTION_LABELS[pendingAction]} tenant` : 'Change tenant status'}
                message={selected && pendingAction ? lifecycleMessage(selected, pendingAction, lifecycleReason.trim()) : null}
                confirmLabel={pendingAction ? ACTION_LABELS[pendingAction] : 'Confirm'}
                danger
                loading={busyAction !== null}
                onCancel={() => setPendingAction(null)}
                onConfirm={() => void confirmLifecycleAction()}
            />
        </>
    );
}

function lifecycleMessage(tenant: TenantRecord, action: LifecycleAction, reason: string) {
    const effect = action === 'suspend'
        ? 'Users will immediately lose workspace access until the tenant is reactivated.'
        : action === 'deactivate'
            ? 'The subscription and data remain, but users cannot access the tenant until a later activation passes readiness again.'
            : 'The tenant becomes an archived historical record and cannot be modified or activated.';
    return <div className="space-y-2"><p>Confirm <strong>{ACTION_LABELS[action].toLowerCase()}</strong> for <strong>{tenant.name}</strong>.</p><p>{effect}</p><p>Audit reason: “{reason}”.</p></div>;
}

function pastTense(action: LifecycleAction): string {
    return action === 'suspend' ? 'suspended' : action === 'deactivate' ? 'deactivated' : 'archived';
}

function positiveInteger(value: string | null): number | null {
    const parsed = Number(value);
    return Number.isSafeInteger(parsed) && parsed > 0 ? parsed : null;
}
