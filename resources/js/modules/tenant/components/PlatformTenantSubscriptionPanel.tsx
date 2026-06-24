import { useMemo, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { useApi } from '@/shared/hooks/useApi';
import {
    assignTenantSubscription,
    getTenantSubscriptionReadiness,
    listTenantPlanRevisions,
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
    TenantSubscriptionReadiness,
} from '../tenantTypes';
import { TenantPlanLookupSelect } from './TenantPlanLookupSelect';

interface Props {
    tenant: TenantRecord;
    canManage: boolean;
    disabled?: boolean;
    onChanged: () => void;
}

export function PlatformTenantSubscriptionPanel({ tenant, canManage, disabled = false, onChanged }: Props) {
    const [plan, setPlan] = useState<TenantPlan | null>(null);
    const [revisionId, setRevisionId] = useState<number | null>(null);
    const [status, setStatus] = useState<'trial' | 'active'>('active');
    const [startsAt, setStartsAt] = useState('');
    const [trialEndsAt, setTrialEndsAt] = useState('');
    const [endsAt, setEndsAt] = useState('');
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
    const availableRevisions = uniqueRevisions([
        ...(revisions.data ?? []),
        ...(plan?.latest_revision ? [plan.latest_revision] : []),
    ]);
    const selectedRevision = availableRevisions.find((revision) => revision.id === revisionId)
        ?? plan?.latest_revision
        ?? null;
    const current = tenant.current_subscription;
    const readinessFingerprint = [selectedRevision?.id ?? '', status, startsAt, trialEndsAt, endsAt].join('|');
    const readiness = readinessCheck?.fingerprint === readinessFingerprint ? readinessCheck.result : null;
    const validationMessage = useMemo(
        () => validatePeriod(status, startsAt, trialEndsAt, endsAt),
        [status, startsAt, trialEndsAt, endsAt],
    );
    const mutationDisabled = disabled || saving || checking || tenant.status === 'archived';

    function changePlan(nextPlan: TenantPlan | null) {
        setPlan(nextPlan);
        setRevisionId(nextPlan?.latest_revision?.id ?? null);
        setReadinessCheck(null);
        setError(null);
        setSuccess(null);
    }

    async function checkReadiness() {
        if (!selectedRevision) return;
        setChecking(true);
        setError(null);
        setSuccess(null);
        try {
            const result = await getTenantSubscriptionReadiness(tenant.id, selectedRevision.id);
            setReadinessCheck({ fingerprint: readinessFingerprint, result });
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            setReadinessCheck(null);
        } finally {
            setChecking(false);
        }
    }

    async function assign() {
        if (!selectedRevision || validationMessage || readiness?.ready !== true) return;
        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            await assignTenantSubscription(tenant, {
                tenant_plan_revision_id: selectedRevision.id,
                status,
                starts_at: toIsoOrNull(startsAt),
                trial_ends_at: status === 'trial' ? toIsoOrNull(trialEndsAt) : null,
                ends_at: toIsoOrNull(endsAt),
            });
            setSuccess(`${plan?.name ?? 'The selected plan'} revision ${selectedRevision.revision_number} was assigned successfully.`);
            setPlan(null);
            setRevisionId(null);
            setReadinessCheck(null);
            onChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    return (
        <section id="tenant-subscription-step" className="scroll-mt-24 space-y-4" aria-labelledby="tenant-subscription-title">
            <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Step 4</p>
                <h3 id="tenant-subscription-title" className="mt-1 font-semibold text-slate-900">Assign an immutable subscription revision</h3>
                <p className="mt-1 text-sm text-slate-500">Choose the exact plan revision, review its impact, then assign it. Historical pricing and entitlements remain unchanged.</p>
            </div>

            <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
            <ErrorAlert error={revisions.error} title="Unable to load plan revisions" />
            <ErrorAlert error={error} title="Subscription action failed" />

            {current ? <CurrentSubscriptionSummary tenant={tenant} /> : (
                <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No subscription is assigned. A current trial or active subscription is required before activation.</p>
            )}

            {canManage && tenant.status !== 'archived' ? (
                <div className="space-y-4 rounded-lg border border-slate-200 p-4">
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
                            hint="Existing tenants may remain on older revisions. Select the exact commercial contract to assign."
                            disabled={mutationDisabled}
                        />
                    ) : null}

                    {selectedRevision ? <SubscriptionComparison current={current?.revision ?? null} proposed={selectedRevision} readiness={readiness} /> : null}

                    <div className="grid gap-4 md:grid-cols-2">
                        <Select
                            label="Subscription status"
                            value={status}
                            onChange={(event) => { setStatus(event.target.value as 'trial' | 'active'); setReadinessCheck(null); }}
                            options={[{ value: 'active', label: 'Active' }, { value: 'trial', label: 'Trial' }]}
                            disabled={mutationDisabled}
                        />
                        <Input
                            label="Starts at"
                            type="datetime-local"
                            value={startsAt}
                            onChange={(event) => { setStartsAt(event.target.value); setReadinessCheck(null); }}
                            hint="Leave empty to start immediately. Future scheduling is not supported by this endpoint."
                            disabled={mutationDisabled}
                        />
                        {status === 'trial' ? (
                            <Input
                                label="Trial ends at"
                                type="datetime-local"
                                value={trialEndsAt}
                                onChange={(event) => { setTrialEndsAt(event.target.value); setReadinessCheck(null); }}
                                required
                                disabled={mutationDisabled}
                            />
                        ) : null}
                        <Input
                            label="Subscription ends at"
                            type="datetime-local"
                            value={endsAt}
                            onChange={(event) => { setEndsAt(event.target.value); setReadinessCheck(null); }}
                            hint="Leave empty for no fixed end date. Dates are submitted as UTC."
                            disabled={mutationDisabled}
                        />
                    </div>
                    {validationMessage ? <p className="text-sm text-rose-600">{validationMessage}</p> : null}
                    <div className="flex flex-wrap justify-end gap-2">
                        <Button
                            variant="secondary"
                            loading={checking}
                            disabled={mutationDisabled || !selectedRevision || Boolean(validationMessage)}
                            onClick={() => void checkReadiness()}
                        >
                            Check assignment impact
                        </Button>
                        <Button
                            loading={saving}
                            disabled={disabled || checking || readiness?.ready !== true || Boolean(validationMessage)}
                            onClick={() => void assign()}
                        >
                            Assign selected revision
                        </Button>
                    </div>
                </div>
            ) : null}

            {checking ? <LoadingState label="Checking current usage and downgrade impact..." /> : null}
            {readiness ? <ReadinessResult readiness={readiness} /> : null}
        </section>
    );
}

function CurrentSubscriptionSummary({ tenant }: { tenant: TenantRecord }) {
    const current = tenant.current_subscription;
    if (!current) return null;
    return (
        <div className="rounded-lg border border-slate-200 p-4 text-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-semibold text-slate-900">{current.revision.plan?.name ?? 'Subscription plan'} · revision {current.revision.revision_number}</p>
                    <p className="mt-1 text-slate-500">{formatPlanMoney(current.revision)}</p>
                    <p className="mt-1 text-xs text-slate-500">Started {formatTenantDateTime(current.starts_at)}{current.trial_ends_at ? ` · trial ends ${formatTenantDateTime(current.trial_ends_at)}` : ''}{current.ends_at ? ` · ends ${formatTenantDateTime(current.ends_at)}` : ''}</p>
                </div>
                <StatusBadge status={current.status} />
            </div>
        </div>
    );
}

function SubscriptionComparison({ current, proposed, readiness }: {
    current: TenantPlanRevision | null;
    proposed: TenantPlanRevision;
    readiness: TenantSubscriptionReadiness | null;
}) {
    const currentModules = new Set(current?.features.enabled_modules ?? []);
    const proposedModules = new Set(proposed.features.enabled_modules);
    const added = [...proposedModules].filter((module) => !currentModules.has(module));
    const removed = [...currentModules].filter((module) => !proposedModules.has(module));
    const limitKeys = [...new Set([
        ...Object.keys(current?.limits ?? {}),
        ...Object.keys(proposed.limits),
        ...Object.keys(readiness?.usage ?? {}),
    ])].sort();

    return (
        <div className="space-y-4 rounded-lg bg-slate-50 p-4 text-sm">
            <div className="grid gap-3 md:grid-cols-2">
                <ComparisonValue label="Current contract" value={current ? `Revision ${current.revision_number} · ${formatPlanMoney(current)}` : 'No current subscription'} />
                <ComparisonValue label="Proposed contract" value={`Revision ${proposed.revision_number} · ${formatPlanMoney(proposed)}`} />
            </div>
            <div className="grid gap-3 md:grid-cols-2">
                <ModuleChanges title="Modules added" values={added} empty="No modules added" tone="emerald" />
                <ModuleChanges title="Modules removed" values={removed} empty="No modules removed" tone="amber" />
            </div>
            {limitKeys.length > 0 ? (
                <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th className="px-3 py-2">Limit</th><th className="px-3 py-2">Current</th><th className="px-3 py-2">Proposed</th><th className="px-3 py-2">Usage</th></tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {limitKeys.map((key) => (
                                <tr key={key}>
                                    <td className="px-3 py-2 font-medium text-slate-900">{formatLimitLabel(key)}</td>
                                    <td className="px-3 py-2">{displayLimit(current?.limits[key as keyof typeof current.limits])}</td>
                                    <td className="px-3 py-2">{displayLimit(proposed.limits[key as keyof typeof proposed.limits])}</td>
                                    <td className="px-3 py-2">{readiness?.usage[key] ?? 'Check impact'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : null}
        </div>
    );
}

function ReadinessResult({ readiness }: { readiness: TenantSubscriptionReadiness }) {
    return (
        <div className={`rounded-lg border p-4 text-sm ${readiness.ready ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950'}`}>
            <p className="font-semibold">{readiness.ready ? 'Safe to assign' : 'Assignment blocked'}</p>
            {readiness.removed_modules.length > 0 ? <p className="mt-2">Modules removed: {readiness.removed_modules.map(humanize).join(', ')}</p> : null}
            {readiness.blockers.length > 0 ? (
                <ul className="mt-2 space-y-1">
                    {readiness.blockers.map((blocker) => <li key={`${blocker.code}-${blocker.message}`}>• {blocker.message}</li>)}
                </ul>
            ) : <p className="mt-2">Current tenant usage fits the selected limits and no required module closeout is pending.</p>}
        </div>
    );
}

function ComparisonValue({ label, value }: { label: string; value: string }) {
    return <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><p className="mt-1 font-medium text-slate-900">{value}</p></div>;
}

function ModuleChanges({ title, values, empty, tone }: { title: string; values: string[]; empty: string; tone: 'emerald' | 'amber' }) {
    const toneClass = tone === 'emerald' ? 'text-emerald-700' : 'text-amber-700';
    return <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{title}</p><p className={`mt-1 ${toneClass}`}>{values.length > 0 ? values.map(humanize).join(', ') : empty}</p></div>;
}

function uniqueRevisions(revisions: TenantPlanRevision[]): TenantPlanRevision[] {
    return [...new Map(revisions.map((revision) => [revision.id, revision])).values()]
        .sort((left, right) => right.revision_number - left.revision_number);
}

function displayLimit(value: number | undefined): string {
    return value === undefined ? 'Unlimited' : String(value);
}

function validatePeriod(status: 'trial' | 'active', startsAt: string, trialEndsAt: string, endsAt: string): string | null {
    const start = startsAt === '' ? new Date() : new Date(startsAt);
    if (Number.isNaN(start.getTime())) return 'Select a valid subscription start date.';
    if (startsAt !== '' && start.getTime() > Date.now() + 60_000) return 'Scheduled future subscriptions are not supported.';

    const trialEnd = trialEndsAt === '' ? null : new Date(trialEndsAt);
    const end = endsAt === '' ? null : new Date(endsAt);
    if (status === 'trial' && trialEnd === null) return 'A trial end date is required.';
    if (trialEnd && (Number.isNaN(trialEnd.getTime()) || trialEnd.getTime() <= start.getTime())) return 'Trial end must be later than the start date.';
    if (end && (Number.isNaN(end.getTime()) || end.getTime() <= start.getTime())) return 'Subscription end must be later than the start date.';
    if (trialEnd && end && end.getTime() < trialEnd.getTime()) return 'Subscription end cannot be earlier than the trial end.';
    return null;
}

function toIsoOrNull(value: string): string | null {
    return value === '' ? null : new Date(value).toISOString();
}
