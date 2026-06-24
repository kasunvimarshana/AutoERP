import { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PLATFORM_PERMISSION } from '@/app/access/platformPermissions';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { listActiveReferenceRecords } from '@/modules/reference-data/referenceDataApi';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
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
import {
    changeTenantStatus,
    createPlatformTenant,
    getPlatformTenant,
    listPlatformTenants,
    updatePlatformTenant,
} from './tenantApi';
import {
    focusTenantStep,
    formatTenantDateTime,
    humanize,
} from './tenantPresentation';
import type { TenantRecord } from './tenantTypes';

type LifecycleAction = 'suspend' | 'deactivate' | 'archive';
type FormMode = 'create' | 'edit' | null;

const TENANT_STATUSES = ['draft', 'active', 'inactive', 'suspended', 'archived'] as const;
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
    const [searchParams, setSearchParams] = useSearchParams();
    const page = positiveInteger(searchParams.get('page')) ?? 1;
    const selectedId = positiveInteger(searchParams.get('tenant'));
    const search = searchParams.get('search') ?? '';
    const status = searchParams.get('status') ?? '';
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
        (signal) => listPlatformTenants({ page, per_page: 20, search: debouncedSearch || undefined, status: status || undefined }, signal),
        [page, debouncedSearch, status],
        true,
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
                    <div className="mb-4 grid gap-3 sm:grid-cols-2">
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
                            placeholder="All statuses"
                        />
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
                                    {canUpdate && selected.status !== 'archived' ? <Button variant="secondary" disabled={allMutationsDisabled} onClick={() => { setFormMode('edit'); setFormError(null); focusTenantStep('tenant-identity-step'); }}>Edit identity</Button> : null}
                                </div>
                            </div>
                            <div className="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                                {SETUP_STEPS.map((step) => <Button key={step.id} variant="ghost" onClick={() => focusTenantStep(step.id)}>{step.label}</Button>)}
                            </div>
                        </Panel>

                        <Panel>
                            <section id="tenant-identity-step" className="scroll-mt-24 space-y-4" aria-labelledby="tenant-identity-title">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Step 1</p>
                                    <h3 id="tenant-identity-title" className="mt-1 font-semibold text-slate-900">Tenant identity and accounting base</h3>
                                    <p className="mt-1 text-sm text-slate-500">Confirm stable identifiers and base currency before provisioning the tenant foundation.</p>
                                </div>
                                <dl className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                                    <Detail label="Tenant code" value={selected.code} />
                                    <Detail label="URL slug" value={selected.slug} />
                                    <Detail label="Base currency" value={selected.base_currency ? `${selected.base_currency.code} — ${selected.base_currency.name}` : 'Required before activation'} warning={!selected.base_currency} />
                                    <Detail label="Cross-unit transactions" value={selected.cross_org_transactions ? 'Allowed with authorization' : 'Not allowed'} />
                                    <Detail label="Tenant logo" value={selected.has_logo ? 'Configured' : 'Not configured'} />
                                    <Detail label="Last updated" value={formatTenantDateTime(selected.updated_at)} />
                                </dl>
                            </section>
                        </Panel>

                        <Panel><PlatformTenantOnboardingPanel key={`onboarding-${selected.id}`} tenant={selected} canProvision={canOnboard} disabled={allMutationsDisabled} onTenantChanged={refreshSelected} /></Panel>
                        <Panel><PlatformTenantDomainsPanel key={`domains-${selected.id}`} tenant={selected} canManage={canManageDomains} disabled={allMutationsDisabled} onChanged={refreshSelected} /></Panel>
                        <Panel><PlatformTenantSubscriptionPanel key={`subscription-${selected.id}`} tenant={selected} canManage={canManageSubscriptions} disabled={allMutationsDisabled} onChanged={refreshSelected} /></Panel>
                        <Panel><PlatformTenantActivationPanel key={`activation-${selected.id}`} tenant={selected} canActivate={canLifecycle} disabled={allMutationsDisabled} onChanged={(updated) => { selectedTenant.setData(updated); tenants.reload(); }} /></Panel>

                        {canLifecycle && selected.status !== 'archived' ? (
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

const SETUP_STEPS = [
    { id: 'tenant-identity-step', label: '1. Identity' },
    { id: 'tenant-foundation-step', label: '2. Foundation' },
    { id: 'tenant-domain-step', label: '3. Domain' },
    { id: 'tenant-subscription-step', label: '4. Subscription' },
    { id: 'tenant-activation-step', label: '5. Readiness' },
] as const;

function TenantDirectoryCard({ tenant, selected, onSelect }: { tenant: TenantRecord; selected: boolean; onSelect: () => void }) {
    const onboarding = tenant.onboarding?.status ?? 'pending';
    const subscription = tenant.current_subscription;
    return (
        <button
            type="button"
            onClick={onSelect}
            aria-pressed={selected}
            className="w-full rounded-lg border border-slate-200 p-4 text-left transition hover:border-blue-300 hover:bg-blue-50/30 aria-pressed:border-blue-500 aria-pressed:bg-blue-50"
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-semibold text-slate-900">{tenant.name}</p>
                    <p className="mt-1 text-sm text-slate-500">{tenant.code} · {tenant.primary_domain?.domain ?? 'Primary domain required'}</p>
                </div>
                <StatusBadge status={tenant.status} />
            </div>
            <div className="mt-3 grid gap-2 text-xs text-slate-600 sm:grid-cols-3">
                <span>Foundation: <strong>{humanize(onboarding)}</strong></span>
                <span>Plan: <strong>{subscription?.revision.plan?.name ?? 'Not assigned'}</strong></span>
                <span>Period: <strong>{subscription?.ends_at ? `Ends ${formatTenantDateTime(subscription.ends_at)}` : subscription ? humanize(subscription.status) : 'Required'}</strong></span>
            </div>
        </button>
    );
}

function Detail({ label, value, warning = false }: { label: string; value: string; warning?: boolean }) {
    return <div className={`rounded-lg p-3 ${warning ? 'bg-amber-50' : 'bg-slate-50'}`}><dt className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</dt><dd className={`mt-1 font-medium ${warning ? 'text-amber-800' : 'text-slate-900'}`}>{value}</dd></div>;
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
