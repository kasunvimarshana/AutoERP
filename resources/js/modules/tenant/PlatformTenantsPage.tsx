import { useState } from 'react';
import { listActiveReferenceRecords } from '@/modules/reference-data/referenceDataApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
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
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';
import { PlatformTenantDomainsPanel } from './components/PlatformTenantDomainsPanel';
import { PlatformTenantForm } from './components/PlatformTenantForm';
import { PlatformTenantOnboardingPanel } from './components/PlatformTenantOnboardingPanel';
import { PlatformTenantSubscriptionPanel } from './components/PlatformTenantSubscriptionPanel';
import { PLATFORM_PERMISSION } from '@/app/access/platformPermissions';
import {
    changeTenantStatus,
    createPlatformTenant,
    getPlatformTenant,
    listPlatformTenants,
    updatePlatformTenant,
} from './tenantApi';
import type { TenantRecord } from './tenantTypes';

type LifecycleAction = 'activate' | 'suspend' | 'deactivate' | 'archive';

const TENANT_STATUSES = ['draft', 'active', 'inactive', 'suspended', 'archived'] as const;
const ACTION_LABELS: Record<LifecycleAction, string> = {
    activate: 'Activate',
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

    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const debouncedSearch = useDebounce(search);
    const [editing, setEditing] = useState<TenantRecord | null>(null);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [reason, setReason] = useState('');
    const [saving, setSaving] = useState(false);
    const [busyAction, setBusyAction] = useState<LifecycleAction | null>(null);
    const [pendingAction, setPendingAction] = useState<LifecycleAction | null>(null);
    const [createRevision, setCreateRevision] = useState(0);
    const [error, setError] = useState<ApiError | null>(null);

    const tenants = useApi(
        (signal) => listPlatformTenants({ page, per_page: 20, search: debouncedSearch || undefined, status: status || undefined }, signal),
        [page, debouncedSearch, status],
    );
    const currencies = useApi((signal) => listActiveReferenceRecords('currencies', signal), []);
    const selectedTenant = useApi(
        (signal) => getPlatformTenant(selectedId as number, signal),
        [selectedId],
        selectedId !== null,
    );
    const selected = selectedTenant.data;

    function refreshSelected() {
        selectedTenant.reload();
        tenants.reload();
    }

    function selectTenant(id: number) {
        setSelectedId(id);
        setReason('');
        setPendingAction(null);
        setEditing(null);
        setError(null);
    }

    async function save(payload: FormData) {
        setSaving(true);
        setError(null);
        try {
            const saved = editing
                ? await updatePlatformTenant(editing.id, payload)
                : await createPlatformTenant(payload);

            setEditing(null);
            setSelectedId(saved.id);
            selectedTenant.setData(saved);
            setReason('');
            setCreateRevision((current) => current + 1);
            tenants.reload();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    function requestTransition(action: LifecycleAction) {
        if (!selected || !reason.trim() || busyAction) return;
        setPendingAction(action);
    }

    async function confirmTransition() {
        if (!selected || !pendingAction || !reason.trim()) return;
        const action = pendingAction;
        setPendingAction(null);
        setBusyAction(action);
        setError(null);

        try {
            const updated = await changeTenantStatus(selected.id, action, selected.row_version, reason.trim());
            selectedTenant.setData(updated);
            setReason('');
            tenants.reload();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            refreshSelected();
        } finally {
            setBusyAction(null);
        }
    }

    const formKey = editing
        ? `edit-${editing.id}-${editing.row_version}`
        : `create-${createRevision}`;
    const allMutationsDisabled = saving || busyAction !== null;

    return (
        <>
            <ContentHeader
                title="SaaS tenants"
                description="Create tenant identities, provision their foundation, verify domains, assign immutable subscriptions, and activate only after readiness checks pass."
            />
            <div className="space-y-5">
                <ErrorAlert error={tenants.error ?? currencies.error ?? selectedTenant.error ?? error} />
                {(canCreate || (editing && canUpdate)) ? (
                    <Panel title={editing ? `Edit ${editing.name}` : 'Create a draft tenant'}>
                        <PlatformTenantForm
                            key={formKey}
                            tenant={editing}
                            currencies={currencies.data ?? []}
                            saving={saving}
                            onCancel={() => setEditing(null)}
                            onSubmit={save}
                        />
                    </Panel>
                ) : null}

                <Panel title="Tenant directory">
                    <div className="mb-4 grid gap-3 sm:grid-cols-2">
                        <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Name, code, or slug" />
                        <Select label="Status" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} options={TENANT_STATUSES.map((value) => ({ value, label: capitalize(value) }))} placeholder="All statuses" />
                    </div>
                    {tenants.loading && !tenants.data ? <LoadingState label="Loading tenants..." /> : (
                        <div className="space-y-3">
                            {(tenants.data?.data ?? []).map((tenant) => (
                                <button
                                    key={tenant.id}
                                    type="button"
                                    onClick={() => selectTenant(tenant.id)}
                                    aria-pressed={selectedId === tenant.id}
                                    className="flex w-full flex-col justify-between gap-3 rounded-lg border border-slate-200 p-4 text-left transition hover:border-blue-300 hover:bg-blue-50/30 aria-pressed:border-blue-500 aria-pressed:bg-blue-50 sm:flex-row sm:items-center"
                                >
                                    <div>
                                        <p className="font-semibold text-slate-900">{tenant.name}</p>
                                        <p className="mt-1 text-sm text-slate-500">
                                            {tenant.code} · {tenant.current_subscription?.revision.plan?.name ?? 'No subscription'} · {tenant.base_currency?.code ?? 'Base currency required'}
                                        </p>
                                    </div>
                                    <StatusBadge status={tenant.status} />
                                </button>
                            ))}
                            {(tenants.data?.data ?? []).length === 0 ? <p className="py-8 text-center text-sm text-slate-500">No tenants match the current filters.</p> : null}
                        </div>
                    )}
                    <Pagination meta={tenants.data?.meta} onPageChange={setPage} />
                </Panel>

                {selectedId !== null && selectedTenant.loading && !selected ? <LoadingState label="Loading tenant details..." /> : null}
                {selected ? (
                    <>
                        <Panel title={selected.name}>
                            <div className="grid gap-4 lg:grid-cols-[1fr_22rem]">
                                <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                    <Detail label="Code" value={selected.code} />
                                    <Detail label="Status" value={capitalize(selected.status)} />
                                    <Detail label="Plan" value={selected.current_subscription?.revision.plan?.name ?? 'Not assigned'} />
                                    <Detail label="Subscription" value={selected.current_subscription ? capitalize(selected.current_subscription.status) : 'Not assigned'} />
                                    <Detail label="Base currency" value={selected.base_currency ? `${selected.base_currency.code} — ${selected.base_currency.name}` : 'Required before activation'} />
                                    <Detail label="Activated" value={formatDate(selected.activated_at)} />
                                </dl>
                                <div className="space-y-3">
                                    {canUpdate ? <Button variant="secondary" className="w-full" disabled={allMutationsDisabled} onClick={() => setEditing(selected)}>Edit tenant identity</Button> : null}
                                    {canLifecycle && selected.status !== 'archived' ? (
                                        <>
                                            <Textarea label="Lifecycle reason" value={reason} onChange={(event) => setReason(event.target.value)} disabled={allMutationsDisabled} placeholder="Explain the operational reason for this change." />
                                            <div className="grid grid-cols-2 gap-2">
                                                {['draft', 'inactive', 'suspended'].includes(selected.status) ? <Button disabled={!reason.trim() || allMutationsDisabled} onClick={() => requestTransition('activate')}>Activate</Button> : null}
                                                {selected.status === 'active' ? <Button variant="secondary" disabled={!reason.trim() || allMutationsDisabled} onClick={() => requestTransition('suspend')}>Suspend</Button> : null}
                                                {['active', 'suspended'].includes(selected.status) ? <Button variant="secondary" disabled={!reason.trim() || allMutationsDisabled} onClick={() => requestTransition('deactivate')}>Deactivate</Button> : null}
                                                {['draft', 'inactive', 'suspended'].includes(selected.status) ? <Button variant="danger" disabled={!reason.trim() || allMutationsDisabled} onClick={() => requestTransition('archive')}>Archive</Button> : null}
                                            </div>
                                        </>
                                    ) : null}
                                </div>
                            </div>
                        </Panel>

                        <Panel title="Tenant activation setup">
                            <div className="space-y-8 divide-y divide-slate-200">
                                <PlatformTenantOnboardingPanel key={`onboarding-${selected.id}`} tenant={selected} canProvision={canOnboard} disabled={allMutationsDisabled} onTenantChanged={refreshSelected} />
                                <div className="pt-8"><PlatformTenantDomainsPanel key={`domains-${selected.id}`} tenant={selected} canManage={canManageDomains} disabled={allMutationsDisabled} onChanged={refreshSelected} /></div>
                                <div className="pt-8"><PlatformTenantSubscriptionPanel key={`subscription-${selected.id}`} tenant={selected} canManage={canManageSubscriptions} disabled={allMutationsDisabled} onChanged={refreshSelected} /></div>
                            </div>
                        </Panel>
                    </>
                ) : null}
            </div>

            <ConfirmDialog
                open={Boolean(selected && pendingAction)}
                title={pendingAction ? `${ACTION_LABELS[pendingAction]} tenant` : 'Change tenant status'}
                message={selected && pendingAction ? (
                    <p>
                        Confirm <strong>{ACTION_LABELS[pendingAction].toLowerCase()}</strong> for <strong>{selected.name}</strong>.
                        The backend will enforce readiness and record this reason: “{reason.trim()}”.
                    </p>
                ) : null}
                confirmLabel={pendingAction ? ACTION_LABELS[pendingAction] : 'Confirm'}
                danger={pendingAction !== 'activate'}
                loading={busyAction !== null}
                onCancel={() => setPendingAction(null)}
                onConfirm={() => void confirmTransition()}
            />
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return <div className="rounded-lg bg-slate-50 p-3"><dt className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</dt><dd className="mt-1 font-medium text-slate-900">{value}</dd></div>;
}

function formatDate(value: string | null): string {
    return formatBusinessDateTime(value, 'Not set');
}

function capitalize(value: string): string {
    return value.length === 0 ? value : value[0].toUpperCase() + value.slice(1).replaceAll('_', ' ');
}
