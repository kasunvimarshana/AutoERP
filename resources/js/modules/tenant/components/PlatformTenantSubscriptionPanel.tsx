import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import {
    assignTenantSubscription,
    cancelTenantSubscription,
    correctTenantSubscription,
    extendTenantSubscription,
    getTenantSubscriptionReadiness,
    listSubscriptionPlanRevisions,
    renewTenantSubscription,
} from '../tenantApi';
import {
    formatPlanMoney,
    formatTenantDateTime,
    humanize,
} from '../tenantPresentation';
import type {
    TenantCurrentSubscription,
    TenantPlan,
    TenantPlanRevision,
    TenantRecord,
    TenantSubscriptionContractStatus,
    TenantSubscriptionReadiness,
} from '../tenantTypes';
import { TenantPlanLookupSelect } from './TenantPlanLookupSelect';
import { TenantSubscriptionHistory } from './TenantSubscriptionHistory';
import {
    CurrentSubscriptionSummary,
    PermissionNotice,
    ReadinessResult,
    SubscriptionComparison,
} from './TenantSubscriptionPresentation';
import {
    availableSubscriptionActions,
    defaultSubscriptionAction,
    normalizedReason,
    requiresPeriod,
    requiresPlan,
    subscriptionActionLabel,
    subscriptionAssignmentPayload,
    toIso,
    toLocalDateTime,
    validateSubscriptionAction,
    type SubscriptionAction,
} from './tenantSubscriptionRules';

interface Props {
    tenant: TenantRecord;
    canManage: boolean;
    disabled?: boolean;
    canAudit: boolean;
    initialPlan?: TenantPlan | null;
    initialAction?: 'assign' | null;
    onChanged: () => void;
}


export function PlatformTenantSubscriptionPanel({
    tenant,
    canManage,
    canAudit,
    initialPlan = null,
    initialAction = null,
    disabled = false,
    onChanged,
}: Props) {
    const [currentOverride, setCurrentOverride] = useState<TenantCurrentSubscription | null>(null);
    const current = currentOverride ?? tenant.current_subscription;
    const [action, setAction] = useState<SubscriptionAction>(initialAction === 'assign' ? 'assign' : defaultSubscriptionAction(current));
    const [plan, setPlan] = useState<TenantPlan | null>(initialPlan);
    const [revisionId, setRevisionId] = useState<number | null>(initialPlan?.current_revision?.id ?? initialPlan?.latest_revision?.id ?? null);
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
        (signal) => listSubscriptionPlanRevisions(plan?.id ?? 0, signal),
        [plan?.id],
        plan !== null,
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
    const validationMessage = validateSubscriptionAction(
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
    const actionOptions = availableSubscriptionActions(current);

    function changeAction(next: SubscriptionAction) {
        setAction(next);
        setReadinessCheck(null);
        setError(null);
        setSuccess(null);
        setReason('');
        setStartsAt('');
        setTrialEndsAt('');
        setEndsAt('');

        if (next === 'extend') setEndsAt(toLocalDateTime(current?.ends_at));
        if (next === 'correct') {
            setContractStatus(current?.contract_status ?? 'active');
            setStartsAt(toLocalDateTime(current?.starts_at));
            setTrialEndsAt(toLocalDateTime(current?.trial_ends_at));
            setEndsAt(toLocalDateTime(current?.ends_at));
        }
    }

    function changeContractStatus(next: TenantSubscriptionContractStatus) {
        setContractStatus(next);
        setReadinessCheck(null);
        if (next === 'trial') setEndsAt('');
        else setTrialEndsAt('');
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
        if (validationMessage || (action !== 'cancel' && readiness?.ready !== true)) return;
        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            let updated: TenantCurrentSubscription;
            if (action === 'assign' && selectedRevision) {
                updated = await assignTenantSubscription(
                    tenant,
                    subscriptionAssignmentPayload(selectedRevision.id, contractStatus, startsAt, trialEndsAt, endsAt, reason),
                );
            } else if (action === 'renew' && current && selectedRevision) {
                updated = await renewTenantSubscription(
                    tenant,
                    current,
                    subscriptionAssignmentPayload(selectedRevision.id, contractStatus, startsAt, trialEndsAt, endsAt, reason),
                );
            } else if (action === 'correct' && current && selectedRevision) {
                updated = await correctTenantSubscription(tenant, current, {
                    ...subscriptionAssignmentPayload(selectedRevision.id, contractStatus, startsAt, trialEndsAt, endsAt, reason),
                    starts_at: toIso(startsAt),
                    reason: reason.trim(),
                });
            } else if (action === 'extend' && current) {
                updated = await extendTenantSubscription(tenant, current, toIso(endsAt), normalizedReason(reason));
            } else if (action === 'cancel' && current) {
                updated = await cancelTenantSubscription(tenant, current, reason.trim());
            } else {
                throw new Error('The selected subscription action is no longer available. Refresh and try again.');
            }

            setCurrentOverride(updated);
            setSuccess(`${humanize(action)} completed. The immutable subscription record was updated.`);
            resetEditor(updated);
            onChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    function resetEditor(updated: TenantCurrentSubscription | null) {
        setPlan(null);
        setRevisionId(null);
        setReadinessCheck(null);
        setReason('');
        setStartsAt('');
        setTrialEndsAt('');
        setEndsAt('');
        setContractStatus(updated?.contract_status ?? 'active');
        setAction(defaultSubscriptionAction(updated));
    }

    return (
        <section id="tenant-subscription-step" className="scroll-mt-24 space-y-4" aria-labelledby="tenant-subscription-title">
            <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Step 4</p>
                <h3 id="tenant-subscription-title" className="mt-1 font-semibold text-slate-900">Manage subscription</h3>
                <p className="mt-1 text-sm text-slate-500">
                    Assign a plan or record an explicit lifecycle command. Existing commercial snapshots are never edited.
                </p>
            </div>

            <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
            <ErrorAlert error={revisions.error} title="Unable to load plan revisions" />
            <ErrorAlert error={error} title="Subscription action failed" />

            {current ? <CurrentSubscriptionSummary subscription={current} /> : (
                <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                    No subscription is assigned. Assign an active plan before tenant activation.
                </p>
            )}

            {!canManage ? (
                <PermissionNotice message="You have read-only access. Managing subscriptions requires the platform tenant-subscription management permission." />
            ) : null}
            {tenant.status === 'archived' ? (
                <PermissionNotice message="Archived tenants are read-only. Subscription commands are blocked by the backend." />
            ) : null}

            {canManage && tenant.status !== 'archived' && actionOptions.length > 0 ? (
                <div className="space-y-4 rounded-lg border border-slate-200 p-4">
                    <Select
                        label="Subscription action"
                        value={action}
                        onChange={(event) => changeAction(event.target.value as SubscriptionAction)}
                        options={actionOptions.map((value) => ({ value, label: subscriptionActionLabel(value) }))}
                        disabled={mutationDisabled}
                        hint="Each completed action creates an auditable immutable revision or current-state transition."
                    />

                    {requiresPlan(action) ? (
                        <>
                            <TenantPlanLookupSelect value={plan} onChange={changePlan} disabled={mutationDisabled} />
                            {revisions.loading && plan ? <LoadingState label="Loading plan revision history..." /> : null}
                            {plan && availableRevisions.length > 0 ? (
                                <Select
                                    label="Plan revision"
                                    value={selectedRevision?.id ? String(selectedRevision.id) : ''}
                                    onChange={(event) => {
                                        setRevisionId(Number(event.target.value));
                                        setReadinessCheck(null);
                                    }}
                                    options={availableRevisions.map((revision) => ({
                                        value: revision.id,
                                        label: `Revision ${revision.revision_number} · effective ${formatTenantDateTime(revision.effective_at)} · ${formatPlanMoney(revision)}`,
                                    }))}
                                    hint="Only plan revisions already effective can be assigned."
                                    disabled={mutationDisabled}
                                />
                            ) : null}
                        </>
                    ) : null}

                    {requiresPeriod(action) ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <Select
                                label="Contract type"
                                value={contractStatus}
                                onChange={(event) => changeContractStatus(event.target.value as TenantSubscriptionContractStatus)}
                                options={[{ value: 'active', label: 'Active contract' }, { value: 'trial', label: 'Trial contract' }]}
                                disabled={mutationDisabled}
                            />
                            <Input
                                label="Starts at"
                                type="datetime-local"
                                value={startsAt}
                                max={toLocalDateTime(new Date().toISOString())}
                                onChange={(event) => {
                                    setStartsAt(event.target.value);
                                    setReadinessCheck(null);
                                }}
                                hint="Leave empty to begin immediately. Future scheduling is not supported until a dedicated scheduled-revision workflow exists."
                                disabled={mutationDisabled}
                                required={action === 'correct'}
                            />
                            {contractStatus === 'trial' ? (
                                <Input
                                    label="Trial ends at"
                                    type="datetime-local"
                                    value={trialEndsAt}
                                    onChange={(event) => {
                                        setTrialEndsAt(event.target.value);
                                        setReadinessCheck(null);
                                    }}
                                    hint="A trial has one authoritative expiry date."
                                    required
                                    disabled={mutationDisabled}
                                />
                            ) : (
                                <Input
                                    label="Contract ends at"
                                    type="datetime-local"
                                    value={endsAt}
                                    onChange={(event) => {
                                        setEndsAt(event.target.value);
                                        setReadinessCheck(null);
                                    }}
                                    hint="Leave empty for an open-ended active contract."
                                    disabled={mutationDisabled}
                                />
                            )}
                        </div>
                    ) : null}

                    {action === 'extend' ? (
                        <Input
                            label="New contract end"
                            type="datetime-local"
                            value={endsAt}
                            onChange={(event) => {
                                setEndsAt(event.target.value);
                                setReadinessCheck(null);
                            }}
                            hint="Only fixed-term active contracts can be extended. The new end must be later than the existing end."
                            disabled={mutationDisabled}
                            required
                        />
                    ) : null}

                    <Textarea
                        label={action === 'cancel' || action === 'correct' ? 'Reason' : 'Change note'}
                        value={reason}
                        onChange={(event) => {
                            setReason(event.target.value);
                            setReadinessCheck(null);
                        }}
                        disabled={mutationDisabled}
                        required={action === 'cancel' || action === 'correct'}
                        hint={action === 'cancel' || action === 'correct'
                            ? 'Required for the audit trail.'
                            : 'Optional context for the audit trail.'}
                    />

                    {selectedRevision && requiresPlan(action) ? (
                        <SubscriptionComparison current={current} proposed={selectedRevision} readiness={readiness} />
                    ) : null}
                    {validationMessage ? <p className="text-sm text-rose-600">{validationMessage}</p> : null}
                    <div className="flex flex-wrap justify-end gap-2">
                        {action !== 'cancel' ? (
                            <Button
                                variant="secondary"
                                loading={checking}
                                disabled={mutationDisabled || !proposedRevisionId || Boolean(validationMessage)}
                                onClick={() => void checkReadiness()}
                            >
                                Check impact
                            </Button>
                        ) : null}
                        <Button
                            variant={action === 'cancel' ? 'danger' : 'primary'}
                            loading={saving}
                            disabled={mutationDisabled || Boolean(validationMessage) || (action !== 'cancel' && readiness?.ready !== true)}
                            onClick={() => void submit()}
                        >
                            {subscriptionActionLabel(action)}
                        </Button>
                    </div>
                </div>
            ) : null}

            {checking ? <LoadingState label="Checking tenant usage and plan impact..." /> : null}
            {readiness ? <ReadinessResult readiness={readiness} /> : null}
            <TenantSubscriptionHistory
                tenantId={tenant.id}
                subscriptionVersion={current?.row_version ?? null}
                canAudit={canAudit}
            />
        </section>
    );
}

function uniqueRevisions(revisions: TenantPlanRevision[]): TenantPlanRevision[] {
    return [...new Map(revisions.map((revision) => [revision.id, revision])).values()]
        .sort((left, right) => right.revision_number - left.revision_number);
}
