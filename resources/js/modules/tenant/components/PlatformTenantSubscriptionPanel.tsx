import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import {
    assignTenantSubscription,
    cancelTenantSubscription,
    correctTenantSubscription,
    extendTenantSubscription,
    getTenantSubscriptionReadiness,
    listTenantPlanRevisions,
    listTenantSubscriptionHistory,
    renewTenantSubscription,
} from '../tenantApi';
import {
    formatLimitLabel,
    formatPlanMoney,
    formatTenantDateTime,
    humanize,
} from '../tenantPresentation';
import type {
    TenantPlan,
    TenantPlanRevision,
    TenantRecord,
    TenantSubscription,
    TenantSubscriptionContractStatus,
    TenantSubscriptionReadiness,
} from '../tenantTypes';
import { TenantPlanLookupSelect } from './TenantPlanLookupSelect';
import { platformAuditHref } from '@/modules/platform-administration/platformAdministrationPresentation';

interface Props {
    tenant: TenantRecord;
    canManage: boolean;
    canAudit: boolean;
    disabled?: boolean;
    onChanged: () => void;
}

type SubscriptionAction = 'assign' | 'renew' | 'extend' | 'correct' | 'cancel';

export function PlatformTenantSubscriptionPanel({ tenant, canManage, canAudit, disabled = false, onChanged }: Props) {
    const current = tenant.current_subscription;
    const [action, setAction] = useState<SubscriptionAction>(defaultAction(current));
    const [plan, setPlan] = useState<TenantPlan | null>(null);
    const [revisionId, setRevisionId] = useState<number | null>(null);
    const [contractStatus, setContractStatus] = useState<TenantSubscriptionContractStatus>(current?.contract_status ?? 'active');
    const [startsAt, setStartsAt] = useState('');
    const [trialEndsAt, setTrialEndsAt] = useState('');
    const [endsAt, setEndsAt] = useState('');
    const [reason, setReason] = useState('');
    const [readinessCheck, setReadinessCheck] = useState<{ fingerprint: string; result: TenantSubscriptionReadiness } | null>(null);
    const [checking, setChecking] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const revisions = useApi(
        (signal) => listTenantPlanRevisions(plan?.id ?? 0, signal),
        [plan?.id],
        plan !== null,
        false,
    );
    const history = useApi(
        (signal) => listTenantSubscriptionHistory(tenant.id, { page: 1, per_page: 20 }, signal),
        [tenant.id, current?.row_version],
        true,
        false,
    );
    const availableRevisions = uniqueRevisions([
        ...(revisions.data ?? []),
        ...(plan?.latest_revision ? [plan.latest_revision] : []),
        ...(plan?.current_revision ? [plan.current_revision] : []),
    ]);
    const selectedRevision = availableRevisions.find((revision) => revision.id === revisionId)
        ?? plan?.current_revision
        ?? plan?.latest_revision
        ?? null;
    const proposedRevisionId = action === 'extend' ? current?.tenant_plan_revision_id ?? null : selectedRevision?.id ?? null;
    const fingerprint = [action, proposedRevisionId ?? '', contractStatus, startsAt, trialEndsAt, endsAt, reason].join('|');
    const readiness = readinessCheck?.fingerprint === fingerprint ? readinessCheck.result : null;
    const validationMessage = validateAction(
        action,
        current,
        selectedRevision,
        contractStatus,
        startsAt,
        trialEndsAt,
        endsAt,
        reason,
    );
    const mutationDisabled = disabled || saving || checking || tenant.status === 'archived';
    const actionOptions = availableActions(current);

    function changeAction(next: SubscriptionAction) {
        setAction(next);
        setReadinessCheck(null);
        setError(null);
        setSuccess(null);
        setReason('');
        if (next === 'extend') setEndsAt(toLocalDateTime(current?.ends_at));
        if (next === 'correct') {
            setContractStatus(current?.contract_status ?? 'active');
            setStartsAt(toLocalDateTime(current?.starts_at));
            setTrialEndsAt(toLocalDateTime(current?.trial_ends_at));
            setEndsAt(toLocalDateTime(current?.ends_at));
        }
    }

    function changePlan(nextPlan: TenantPlan | null) {
        setPlan(nextPlan);
        setRevisionId(nextPlan?.current_revision?.id ?? nextPlan?.latest_revision?.id ?? null);
        setReadinessCheck(null);
        setError(null);
    }

    async function checkReadiness() {
        if (!proposedRevisionId || action === 'cancel') return;
        setChecking(true);
        setError(null);
        setSuccess(null);
        try {
            const result = await getTenantSubscriptionReadiness(tenant.id, proposedRevisionId);
            setReadinessCheck({ fingerprint, result });
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            setReadinessCheck(null);
        } finally {
            setChecking(false);
        }
    }

    async function submit() {
        if (validationMessage) return;
        if (action !== 'cancel' && readiness?.ready !== true) return;
        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            if (action === 'assign' && selectedRevision) {
                await assignTenantSubscription(tenant, assignmentPayload(selectedRevision.id, contractStatus, startsAt, trialEndsAt, endsAt, reason));
            } else if (action === 'renew' && current && selectedRevision) {
                await renewTenantSubscription(tenant, current, assignmentPayload(selectedRevision.id, contractStatus, startsAt, trialEndsAt, endsAt, reason));
            } else if (action === 'correct' && current && selectedRevision) {
                await correctTenantSubscription(tenant, current, {
                    ...assignmentPayload(selectedRevision.id, contractStatus, startsAt, trialEndsAt, endsAt, reason),
                    starts_at: toIso(startsAt),
                    reason: reason.trim(),
                });
            } else if (action === 'extend' && current) {
                await extendTenantSubscription(tenant, current, toIso(endsAt), normalizedReason(reason));
            } else if (action === 'cancel' && current) {
                await cancelTenantSubscription(tenant, current, reason.trim());
            } else {
                throw new Error('The selected subscription action is no longer available. Refresh and try again.');
            }
            setSuccess(`${humanize(action)} completed. A new immutable subscription revision or state transition was recorded.`);
            resetEditor(current);
            history.reload();
            onChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    function resetEditor(previous: TenantSubscription | null) {
        setPlan(null);
        setRevisionId(null);
        setReadinessCheck(null);
        setReason('');
        setStartsAt('');
        setTrialEndsAt('');
        setEndsAt('');
        setAction(defaultAction(previous));
    }

    return (
        <section id="tenant-subscription-step" className="scroll-mt-24 space-y-4" aria-labelledby="tenant-subscription-title">
            <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Step 4</p>
                <h3 id="tenant-subscription-title" className="mt-1 font-semibold text-slate-900">Manage immutable subscription revisions</h3>
                <p className="mt-1 text-sm text-slate-500">Assign, renew, extend, correct, or cancel through explicit commands. Existing commercial snapshots are never edited.</p>
            </div>

            <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
            <ErrorAlert error={revisions.error ?? history.error} title="Unable to load subscription data" />
            {revisions.error || history.error ? null : <ErrorAlert error={error} title="Subscription action failed" />}

            {current ? <CurrentSubscriptionSummary subscription={current} /> : (
                <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No subscription is assigned. A usable current subscription is required before activation.</p>
            )}

            {canManage && tenant.status !== 'archived' && actionOptions.length > 0 ? (
                <div className="space-y-4 rounded-lg border border-slate-200 p-4">
                    <Select
                        label="Subscription action"
                        value={action}
                        onChange={(event) => changeAction(event.target.value as SubscriptionAction)}
                        options={actionOptions.map((value) => ({ value, label: actionLabel(value) }))}
                        disabled={mutationDisabled}
                        hint="Each completed action creates an auditable immutable revision or explicit current-state transition."
                    />

                    {requiresPlan(action) ? (
                        <>
                            <TenantPlanLookupSelect value={plan} onChange={changePlan} disabled={mutationDisabled} />
                            {revisions.loading && plan ? <LoadingState label="Loading plan revision history..." /> : null}
                            {plan && availableRevisions.length > 0 ? (
                                <Select
                                    label="Plan revision"
                                    value={selectedRevision?.id ? String(selectedRevision.id) : ''}
                                    onChange={(event) => { setRevisionId(Number(event.target.value)); setReadinessCheck(null); }}
                                    options={availableRevisions.map((revision) => ({
                                        value: revision.id,
                                        label: `Revision ${revision.revision_number} · effective ${formatTenantDateTime(revision.effective_at)} · ${formatPlanMoney(revision)}`,
                                    }))}
                                    hint="Select the exact commercial revision. Future-effective revisions can only be used from their effective date."
                                    disabled={mutationDisabled}
                                />
                            ) : null}
                        </>
                    ) : null}

                    {requiresPeriod(action) ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <Select
                                label="Contract status"
                                value={contractStatus}
                                onChange={(event) => { setContractStatus(event.target.value as TenantSubscriptionContractStatus); setReadinessCheck(null); }}
                                options={[{ value: 'active', label: 'Active contract' }, { value: 'trial', label: 'Trial contract' }]}
                                disabled={mutationDisabled}
                            />
                            <Input
                                label="Starts at"
                                type="datetime-local"
                                value={startsAt}
                                onChange={(event) => { setStartsAt(event.target.value); setReadinessCheck(null); }}
                                hint={action === 'renew' ? 'Leave empty to begin when the current fixed period ends.' : 'Leave empty to begin immediately. Future scheduling is supported.'}
                                disabled={mutationDisabled}
                                required={action === 'correct'}
                            />
                            {contractStatus === 'trial' ? (
                                <Input label="Trial ends at" type="datetime-local" value={trialEndsAt} onChange={(event) => { setTrialEndsAt(event.target.value); setReadinessCheck(null); }} required disabled={mutationDisabled} />
                            ) : null}
                            <Input label="Contract ends at" type="datetime-local" value={endsAt} onChange={(event) => { setEndsAt(event.target.value); setReadinessCheck(null); }} hint="Leave empty for an open-ended active contract." disabled={mutationDisabled} />
                        </div>
                    ) : null}

                    {action === 'extend' ? (
                        <Input label="New contract end" type="datetime-local" value={endsAt} onChange={(event) => { setEndsAt(event.target.value); setReadinessCheck(null); }} hint="Must be later than the existing end date." disabled={mutationDisabled} required />
                    ) : null}

                    <Textarea
                        label={action === 'cancel' || action === 'correct' ? 'Reason' : 'Change note'}
                        value={reason}
                        onChange={(event) => { setReason(event.target.value); setReadinessCheck(null); }}
                        disabled={mutationDisabled}
                        required={action === 'cancel' || action === 'correct'}
                        hint={action === 'cancel' || action === 'correct' ? 'Required for the audit trail.' : 'Optional context for the audit trail.'}
                    />

                    {selectedRevision && requiresPlan(action) ? <SubscriptionComparison current={current} proposed={selectedRevision} readiness={readiness} /> : null}
                    {validationMessage ? <p className="text-sm text-rose-600">{validationMessage}</p> : null}
                    <div className="flex flex-wrap justify-end gap-2">
                        {action !== 'cancel' ? (
                            <Button variant="secondary" loading={checking} disabled={mutationDisabled || !proposedRevisionId || Boolean(validationMessage)} onClick={() => void checkReadiness()}>
                                Check impact
                            </Button>
                        ) : null}
                        <Button variant={action === 'cancel' ? 'danger' : 'primary'} loading={saving} disabled={mutationDisabled || Boolean(validationMessage) || (action !== 'cancel' && readiness?.ready !== true)} onClick={() => void submit()}>
                            {actionLabel(action)}
                        </Button>
                    </div>
                </div>
            ) : null}

            {checking ? <LoadingState label="Checking tenant usage and plan impact..." /> : null}
            {readiness ? <ReadinessResult readiness={readiness} /> : null}
            <SubscriptionHistory tenantId={tenant.id} subscriptions={history.data?.data ?? []} loading={history.loading} canAudit={canAudit} />
        </section>
    );
}

function CurrentSubscriptionSummary({ subscription }: { subscription: TenantSubscription }) {
    return (
        <div className="rounded-lg border border-slate-200 p-4 text-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-semibold text-slate-900">{subscription.plan_name} · subscription revision {subscription.revision_number}</p>
                    <p className="mt-1 text-slate-500">{subscription.price} {subscription.currency_code ?? ''} / {humanize(subscription.billing_interval)}</p>
                    <p className="mt-1 text-xs text-slate-500">Started {formatTenantDateTime(subscription.starts_at)}{subscription.trial_ends_at ? ` · trial ends ${formatTenantDateTime(subscription.trial_ends_at)}` : ''}{subscription.ends_at ? ` · ends ${formatTenantDateTime(subscription.ends_at)}` : ' · open ended'}</p>
                    {subscription.current_state_reason ? <p className="mt-2 text-xs text-slate-600">{subscription.current_state_reason}</p> : null}
                </div>
                <div className="flex gap-2"><StatusBadge status={subscription.current_state} /><StatusBadge status={subscription.effective_status} /></div>
            </div>
        </div>
    );
}

function SubscriptionComparison({ current, proposed, readiness }: {
    current: TenantSubscription | null;
    proposed: TenantPlanRevision;
    readiness: TenantSubscriptionReadiness | null;
}) {
    const currentModules = new Set(current?.plan_features.enabled_modules ?? []);
    const proposedModules = new Set(proposed.features.enabled_modules);
    const added = [...proposedModules].filter((module) => !currentModules.has(module));
    const removed = [...currentModules].filter((module) => !proposedModules.has(module));
    const currentLimits = current?.plan_limits ?? {};
    const limitKeys = [...new Set([...Object.keys(currentLimits), ...Object.keys(proposed.limits), ...Object.keys(readiness?.usage ?? {})])].sort();

    return (
        <div className="space-y-4 rounded-lg bg-slate-50 p-4 text-sm">
            <div className="grid gap-3 md:grid-cols-2">
                <ComparisonValue label="Current contract" value={current ? `${current.plan_name} · subscription revision ${current.revision_number}` : 'No current subscription'} />
                <ComparisonValue label="Proposed contract" value={`Plan revision ${proposed.revision_number} · ${formatPlanMoney(proposed)}`} />
            </div>
            <div className="grid gap-3 md:grid-cols-2">
                <ModuleChanges title="Modules added" values={added} empty="No modules added" tone="emerald" />
                <ModuleChanges title="Modules removed" values={removed} empty="No modules removed" tone="amber" />
            </div>
            {limitKeys.length > 0 ? (
                <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-3 py-2">Limit</th><th className="px-3 py-2">Current</th><th className="px-3 py-2">Proposed</th><th className="px-3 py-2">Usage</th></tr></thead>
                        <tbody className="divide-y divide-slate-100">
                            {limitKeys.map((key) => <tr key={key}><td className="px-3 py-2 font-medium text-slate-900">{formatLimitLabel(key)}</td><td className="px-3 py-2">{displayLimit(currentLimits[key as keyof typeof currentLimits])}</td><td className="px-3 py-2">{displayLimit(proposed.limits[key as keyof typeof proposed.limits])}</td><td className="px-3 py-2">{readiness?.usage[key] ?? 'Check impact'}</td></tr>)}
                        </tbody>
                    </table>
                </div>
            ) : null}
        </div>
    );
}

function ReadinessResult({ readiness }: { readiness: TenantSubscriptionReadiness }) {
    return <div className={`rounded-lg border p-4 text-sm ${readiness.ready ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950'}`}><p className="font-semibold">{readiness.ready ? 'Safe to continue' : 'Action blocked'}</p>{readiness.removed_modules.length > 0 ? <p className="mt-2">Modules removed: {readiness.removed_modules.map(humanize).join(', ')}</p> : null}{readiness.blockers.length > 0 ? <ul className="mt-2 space-y-1">{readiness.blockers.map((blocker) => <li key={`${blocker.code}-${blocker.message}`}>• {blocker.message}</li>)}</ul> : <p className="mt-2">Current usage fits the selected limits and no required module closeout is pending.</p>}</div>;
}

function SubscriptionHistory({ tenantId, subscriptions, loading, canAudit }: { tenantId: number; subscriptions: TenantSubscription[]; loading: boolean; canAudit: boolean }) {
    return <div className="space-y-3"><h4 className="font-semibold text-slate-900">Subscription history</h4>{loading ? <LoadingState label="Loading subscription history..." /> : subscriptions.length === 0 ? <p className="text-sm text-slate-500">No subscription revisions have been recorded.</p> : <div className="overflow-x-auto rounded-lg border border-slate-200"><table className="min-w-full text-left text-sm"><thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-3 py-2">Revision</th><th className="px-3 py-2">Operation</th><th className="px-3 py-2">Plan snapshot</th><th className="px-3 py-2">Period</th><th className="px-3 py-2">Reason</th>{canAudit ? <th className="px-3 py-2 text-right">Audit</th> : null}</tr></thead><tbody className="divide-y divide-slate-100">{subscriptions.map((subscription) => <tr key={subscription.id}><td className="px-3 py-2">#{subscription.revision_number}</td><td className="px-3 py-2"><StatusBadge status={subscription.operation} /></td><td className="px-3 py-2">{subscription.plan_name}<br /><span className="text-xs text-slate-500">{subscription.price} {subscription.currency_code ?? ''}</span></td><td className="px-3 py-2 text-xs">{formatTenantDateTime(subscription.starts_at)}<br />{subscription.ends_at ? `to ${formatTenantDateTime(subscription.ends_at)}` : 'Open ended'}</td><td className="max-w-xs px-3 py-2 text-xs text-slate-600">{subscription.change_reason ?? '—'}</td>{canAudit ? <td className="px-3 py-2 text-right"><LinkButton className="min-h-8 px-3 py-1 text-xs" variant="secondary" to={platformAuditHref({ tenant_id: tenantId, subject_type: 'tenant_subscription', subject_id: subscription.id })}>View</LinkButton></td> : null}</tr>)}</tbody></table></div>}</div>;
}

function ComparisonValue({ label, value }: { label: string; value: string }) { return <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><p className="mt-1 font-medium text-slate-900">{value}</p></div>; }
function ModuleChanges({ title, values, empty, tone }: { title: string; values: string[]; empty: string; tone: 'emerald' | 'amber' }) { return <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{title}</p><p className={`mt-1 ${tone === 'emerald' ? 'text-emerald-700' : 'text-amber-700'}`}>{values.length > 0 ? values.map(humanize).join(', ') : empty}</p></div>; }
function uniqueRevisions(revisions: TenantPlanRevision[]): TenantPlanRevision[] { return [...new Map(revisions.map((revision) => [revision.id, revision])).values()].sort((left, right) => right.revision_number - left.revision_number); }
function displayLimit(value: number | undefined): string { return value === undefined ? 'Unlimited' : String(value); }
function requiresPlan(action: SubscriptionAction): boolean { return action === 'assign' || action === 'renew' || action === 'correct'; }
function requiresPeriod(action: SubscriptionAction): boolean { return requiresPlan(action); }
function defaultAction(current: TenantSubscription | null): SubscriptionAction { return !current || current.current_state === 'cancelled' || current.current_state === 'expired' ? 'assign' : 'renew'; }
function availableActions(current: TenantSubscription | null): SubscriptionAction[] { return !current || current.current_state === 'cancelled' || current.current_state === 'expired' ? ['assign'] : ['renew', 'extend', 'correct', 'cancel']; }
function actionLabel(action: SubscriptionAction): string { return ({ assign: 'Assign subscription', renew: 'Renew subscription', extend: 'Extend end date', correct: 'Correct subscription', cancel: 'Cancel subscription' })[action]; }
function normalizedReason(value: string): string | null { const trimmed = value.trim(); return trimmed === '' ? null : trimmed; }
function toIsoOrNull(value: string): string | null { return value === '' ? null : new Date(value).toISOString(); }
function toIso(value: string): string { return new Date(value).toISOString(); }
function toLocalDateTime(value: string | null | undefined): string { if (!value) return ''; const date = new Date(value); if (Number.isNaN(date.getTime())) return ''; const offset = date.getTimezoneOffset() * 60_000; return new Date(date.getTime() - offset).toISOString().slice(0, 16); }
function assignmentPayload(revisionId: number, contractStatus: TenantSubscriptionContractStatus, startsAt: string, trialEndsAt: string, endsAt: string, reason: string) { return { tenant_plan_revision_id: revisionId, contract_status: contractStatus, starts_at: toIsoOrNull(startsAt), trial_ends_at: contractStatus === 'trial' ? toIsoOrNull(trialEndsAt) : null, ends_at: toIsoOrNull(endsAt), reason: normalizedReason(reason) }; }
function validateAction(action: SubscriptionAction, current: TenantSubscription | null, revision: TenantPlanRevision | null, status: TenantSubscriptionContractStatus, startsAt: string, trialEndsAt: string, endsAt: string, reason: string): string | null {
    if (action === 'cancel') return current && reason.trim().length > 0 ? null : 'A cancellation reason is required.';
    if (action === 'extend') { if (!current) return 'A current subscription is required.'; if (endsAt === '') return 'Select the new contract end date.'; const next = new Date(endsAt); const previous = current.ends_at ? new Date(current.ends_at) : null; if (Number.isNaN(next.getTime())) return 'Select a valid contract end date.'; if (previous && next <= previous) return 'The new end date must be later than the current end date.'; return null; }
    if (!revision) return 'Select a plan and an exact revision.';
    if (action === 'correct' && startsAt === '') return 'A correction requires an explicit start date.';
    if (action === 'correct' && reason.trim().length === 0) return 'A correction reason is required.';
    const start = startsAt === '' ? new Date() : new Date(startsAt); if (Number.isNaN(start.getTime())) return 'Select a valid start date.';
    const trialEnd = trialEndsAt === '' ? null : new Date(trialEndsAt); const end = endsAt === '' ? null : new Date(endsAt);
    if (status === 'trial' && trialEnd === null) return 'A trial end date is required.';
    if (trialEnd && (Number.isNaN(trialEnd.getTime()) || trialEnd <= start)) return 'Trial end must be later than the start date.';
    if (end && (Number.isNaN(end.getTime()) || end <= start)) return 'Contract end must be later than the start date.';
    if (trialEnd && end && end < trialEnd) return 'Contract end cannot be earlier than the trial end.';
    return null;
}
