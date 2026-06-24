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
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { TenantPlanEditor } from './components/TenantPlanEditor';
import { TenantPlanRevisionHistory } from './components/TenantPlanRevisionHistory';
import {
    activateTenantPlan,
    createTenantPlan,
    deactivateTenantPlan,
    listTenantPlans,
    updateTenantPlan,
} from './tenantApi';
import {
    formatLimitLabel,
    formatPlanMoney,
    formatTenantDateTime,
} from './tenantPresentation';
import type { TenantPlan } from './tenantTypes';

type LifecycleRequest = { action: 'activate' | 'deactivate'; plan: TenantPlan } | null;

export default function TenantPlansPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, PLATFORM_PERMISSION.plansManage);
    const [searchParams, setSearchParams] = useSearchParams();
    const search = searchParams.get('search') ?? '';
    const status = searchParams.get('status') ?? '';
    const page = positiveInteger(searchParams.get('page')) ?? 1;
    const debouncedSearch = useDebounce(search);
    const plans = useApi(
        (signal) => listTenantPlans({
            page,
            per_page: 20,
            search: debouncedSearch || undefined,
            is_active: status === 'active' ? true : status === 'inactive' ? false : undefined,
        }, signal),
        [page, debouncedSearch, status],
        true,
        false,
    );
    const currencies = useApi((signal) => listActiveReferenceRecords('currencies', signal), [], true, false);
    const [editor, setEditor] = useState<TenantPlan | 'create' | null>(null);
    const [editorRevision, setEditorRevision] = useState(0);
    const [historyPlan, setHistoryPlan] = useState<TenantPlan | null>(null);
    const [lifecycleRequest, setLifecycleRequest] = useState<LifecycleRequest>(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    function updateQuery(updates: Record<string, string | null>) {
        const next = new URLSearchParams(searchParams);
        for (const [key, value] of Object.entries(updates)) {
            if (!value) next.delete(key);
            else next.set(key, value);
        }
        setSearchParams(next, { replace: true });
    }

    async function save(payload: Record<string, unknown>) {
        const plan = editor === 'create' ? null : editor;
        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            const saved = plan ? await updateTenantPlan(plan, payload) : await createTenantPlan(payload);
            setEditor(null);
            setEditorRevision((current) => current + 1);
            plans.reload();
            setSuccess(plan
                ? `${saved.name} was updated. Commercial changes were stored as an immutable revision when required.`
                : `${saved.name} was created with its first immutable revision.`);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function applyLifecycle() {
        if (!lifecycleRequest) return;
        const request = lifecycleRequest;
        setLifecycleRequest(null);
        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            const updated = request.action === 'activate'
                ? await activateTenantPlan(request.plan)
                : await deactivateTenantPlan(request.plan);
            plans.reload();
            setSuccess(`${updated.name} is now ${updated.is_active ? 'available for new assignments' : 'unavailable for new assignments'}. Existing subscriptions were not changed.`);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            plans.reload();
        } finally {
            setSaving(false);
        }
    }

    const editingPlan = editor === 'create' ? null : editor;
    const editorKey = editor === 'create'
        ? `create-${editorRevision}`
        : editor ? `edit-${editor.id}-${editor.row_version}` : 'closed';

    return (
        <>
            <ContentHeader
                title="Tenant plans"
                description="Manage plan identities, immutable commercial revisions, assignment availability, and historical subscription impact as separate concerns."
                actions={canManage ? <Button disabled={editor === 'create'} onClick={() => { setEditor('create'); setError(null); setSuccess(null); }}>Create plan</Button> : null}
            />

            <div className="space-y-5">
                <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
                <ErrorAlert error={plans.error} title="Unable to load tenant plans" />
                <ErrorAlert error={currencies.error} title="Unable to load billing currencies" />
                <ErrorAlert error={error} title="Tenant plan action failed" />

                {editor !== null && canManage ? (
                    <Panel title={editingPlan ? `Revise ${editingPlan.name}` : 'Create a tenant plan'}>
                        <TenantPlanEditor
                            key={editorKey}
                            plan={editingPlan}
                            currencies={currencies.data ?? []}
                            saving={saving}
                            error={error}
                            onCancel={() => { setEditor(null); setError(null); }}
                            onSubmit={save}
                        />
                    </Panel>
                ) : null}

                <Panel title="Plan catalogue">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Search plans"
                            value={search}
                            onChange={(event) => updateQuery({ search: event.target.value, page: null })}
                            placeholder="Plan name or slug"
                        />
                        <Select
                            label="Assignment availability"
                            value={status}
                            onChange={(event) => updateQuery({ status: event.target.value, page: null })}
                            options={[{ value: 'active', label: 'Available' }, { value: 'inactive', label: 'Deactivated' }]}
                            placeholder="All plans"
                        />
                    </div>
                    {plans.loading && !plans.data ? <LoadingState label="Loading tenant plans..." /> : null}
                    {plans.loading && plans.data ? <p className="mt-3 text-sm text-slate-500">Refreshing plan catalogue...</p> : null}
                    <div className="mt-4 space-y-3">
                        {(plans.data?.data ?? []).map((plan) => (
                            <PlanCard
                                key={plan.id}
                                plan={plan}
                                canManage={canManage}
                                disabled={saving}
                                onRevise={() => { setEditor(plan); setError(null); setSuccess(null); }}
                                onHistory={() => setHistoryPlan(plan)}
                                onLifecycle={() => setLifecycleRequest({ action: plan.is_active ? 'deactivate' : 'activate', plan })}
                            />
                        ))}
                        {(plans.data?.data ?? []).length === 0 && !plans.loading ? <p className="py-8 text-center text-sm text-slate-500">No plans match the current filters.</p> : null}
                    </div>
                    <Pagination meta={plans.data?.meta} onPageChange={(nextPage) => updateQuery({ page: nextPage === 1 ? null : String(nextPage) })} />
                </Panel>
            </div>

            <TenantPlanRevisionHistory plan={historyPlan} onClose={() => setHistoryPlan(null)} />
            <ConfirmDialog
                open={lifecycleRequest !== null}
                title={lifecycleRequest?.action === 'activate' ? 'Activate tenant plan' : 'Deactivate tenant plan'}
                message={lifecycleRequest ? lifecycleMessage(lifecycleRequest) : null}
                confirmLabel={lifecycleRequest?.action === 'activate' ? 'Activate plan' : 'Deactivate plan'}
                danger={lifecycleRequest?.action === 'deactivate'}
                loading={saving}
                onCancel={() => setLifecycleRequest(null)}
                onConfirm={() => void applyLifecycle()}
            />
        </>
    );
}

function PlanCard({ plan, canManage, disabled, onRevise, onHistory, onLifecycle }: {
    plan: TenantPlan;
    canManage: boolean;
    disabled: boolean;
    onRevise: () => void;
    onHistory: () => void;
    onLifecycle: () => void;
}) {
    const revision = plan.latest_revision;
    return (
        <article className="rounded-lg border border-slate-200 p-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="font-semibold text-slate-900">{plan.name}</p>
                        <StatusBadge status={plan.is_active ? 'available' : 'deactivated'} />
                    </div>
                    <p className="mt-1 text-sm text-slate-500">{plan.slug}</p>
                </div>
                {canManage ? (
                    <div className="flex flex-wrap gap-2">
                        <Button variant="secondary" disabled={disabled} onClick={onHistory}>Revision history</Button>
                        <Button variant="secondary" disabled={disabled} onClick={onRevise}>Revise</Button>
                        <Button variant={plan.is_active ? 'danger' : 'primary'} disabled={disabled} onClick={onLifecycle}>{plan.is_active ? 'Deactivate' : 'Activate'}</Button>
                    </div>
                ) : <Button variant="secondary" onClick={onHistory}>View revisions</Button>}
            </div>
            {revision ? (
                <div className="mt-4 grid gap-3 text-sm md:grid-cols-4">
                    <Metric label="Latest contract" value={`Revision ${revision.revision_number}`} />
                    <Metric label="Pricing" value={formatPlanMoney(revision)} />
                    <Metric label="Effective" value={formatTenantDateTime(revision.effective_at)} />
                    <Metric label="Current tenants" value={String(plan.current_subscription_count)} />
                </div>
            ) : <p className="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">No plan revision exists. This plan cannot be assigned.</p>}
            <div className="mt-3 grid gap-3 text-xs text-slate-600 sm:grid-cols-3">
                <span><strong>{plan.revisions_count}</strong> revision(s)</span>
                <span><strong>{plan.historical_subscription_count}</strong> historical subscription(s)</span>
                <span><strong>{revision?.features.enabled_modules.length ?? 0}</strong> enabled module(s)</span>
            </div>
            {revision && Object.keys(revision.limits).length > 0 ? (
                <p className="mt-3 text-xs text-slate-500">{Object.entries(revision.limits).map(([key, value]) => `${formatLimitLabel(key)}: ${value}`).join(' · ')}</p>
            ) : null}
        </article>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return <div className="rounded-lg bg-slate-50 p-3"><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><p className="mt-1 font-medium text-slate-900">{value}</p></div>;
}

function lifecycleMessage(request: Exclude<LifecycleRequest, null>) {
    const plan = request.plan;
    if (request.action === 'activate') {
        return <p>Make <strong>{plan.name}</strong> available for new tenant assignments? Historical and current subscriptions are not modified.</p>;
    }
    return (
        <div className="space-y-2">
            <p>Stop new assignments to <strong>{plan.name}</strong>?</p>
            <p><strong>{plan.current_subscription_count}</strong> current tenant subscription(s) and <strong>{plan.historical_subscription_count}</strong> historical subscription(s) retain their immutable revisions.</p>
        </div>
    );
}

function positiveInteger(value: string | null): number | null {
    const parsed = Number(value);
    return Number.isSafeInteger(parsed) && parsed > 0 ? parsed : null;
}
