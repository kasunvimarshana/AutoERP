import { useMemo, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import {
    assignTenantSubscription,
    getTenantSubscriptionReadiness,
} from '../tenantApi';
import type {
    TenantPlan,
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
    const [status, setStatus] = useState<'trial' | 'active'>('active');
    const [startsAt, setStartsAt] = useState('');
    const [trialEndsAt, setTrialEndsAt] = useState('');
    const [endsAt, setEndsAt] = useState('');
    const [readinessCheck, setReadinessCheck] = useState<{
        fingerprint: string;
        result: TenantSubscriptionReadiness;
    } | null>(null);
    const [checking, setChecking] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const selectedRevision = plan?.latest_revision ?? null;
    const current = tenant.current_subscription;
    const readinessFingerprint = [
        selectedRevision?.id ?? '',
        status,
        startsAt,
        trialEndsAt,
        endsAt,
    ].join('|');
    const readiness = readinessCheck?.fingerprint === readinessFingerprint
        ? readinessCheck.result
        : null;

    const validationMessage = useMemo(() => validatePeriod(status, startsAt, trialEndsAt, endsAt), [status, startsAt, trialEndsAt, endsAt]);

    async function checkReadiness() {
        if (!selectedRevision) return;
        setChecking(true);
        setError(null);
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
        try {
            await assignTenantSubscription(tenant, {
                tenant_plan_revision_id: selectedRevision.id,
                status,
                starts_at: toIsoOrNull(startsAt),
                trial_ends_at: status === 'trial' ? toIsoOrNull(trialEndsAt) : null,
                ends_at: toIsoOrNull(endsAt),
            });
            setPlan(null);
            setReadinessCheck(null);
            onChanged();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            onChanged();
        } finally {
            setSaving(false);
        }
    }

    return (
        <section className="space-y-4" aria-labelledby="tenant-subscription-title">
            <div>
                <h3 id="tenant-subscription-title" className="font-semibold text-slate-900">3. Assign subscription</h3>
                <p className="mt-1 text-sm text-slate-500">A tenant points to an immutable plan revision. Replacing the subscription preserves historical pricing, features, and limits.</p>
            </div>
            <ErrorAlert error={error} />

            {current ? (
                <div className="rounded-lg border border-slate-200 p-4 text-sm">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p className="font-semibold text-slate-900">{current.revision.plan?.name ?? 'Subscription plan'} · revision {current.revision.revision_number}</p>
                            <p className="mt-1 text-slate-500">{formatMoney(current.revision)} · starts {formatDate(current.starts_at)}{current.ends_at ? ` · ends ${formatDate(current.ends_at)}` : ''}</p>
                        </div>
                        <StatusBadge status={current.status} />
                    </div>
                </div>
            ) : <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No subscription has been assigned.</p>}

            {canManage ? (
                <div className="space-y-4 rounded-lg border border-slate-200 p-4">
                    <TenantPlanLookupSelect value={plan} onChange={setPlan} disabled={disabled || saving || checking} />
                    {selectedRevision ? (
                        <div className="rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                            <p className="font-medium">Revision {selectedRevision.revision_number} · effective {formatDate(selectedRevision.effective_at)}</p>
                            <p className="mt-1">{formatMoney(selectedRevision)} · {selectedRevision.features.enabled_modules.length} enabled modules</p>
                        </div>
                    ) : null}
                    <div className="grid gap-4 md:grid-cols-2">
                        <Select
                            label="Subscription status"
                            value={status}
                            onChange={(event) => setStatus(event.target.value as 'trial' | 'active')}
                            options={[{ value: 'active', label: 'Active' }, { value: 'trial', label: 'Trial' }]}
                            disabled={disabled || saving || checking}
                        />
                        <Input
                            label="Starts at"
                            type="datetime-local"
                            value={startsAt}
                            onChange={(event) => setStartsAt(event.target.value)}
                            hint="Leave empty to start immediately."
                            disabled={disabled || saving || checking}
                        />
                        {status === 'trial' ? (
                            <Input
                                label="Trial ends at"
                                type="datetime-local"
                                value={trialEndsAt}
                                onChange={(event) => setTrialEndsAt(event.target.value)}
                                required
                                disabled={disabled || saving || checking}
                            />
                        ) : null}
                        <Input
                            label="Subscription ends at"
                            type="datetime-local"
                            value={endsAt}
                            onChange={(event) => setEndsAt(event.target.value)}
                            hint="Leave empty for no fixed end date."
                            disabled={disabled || saving || checking}
                        />
                    </div>
                    {validationMessage ? <p className="text-sm text-rose-600">{validationMessage}</p> : null}
                    <div className="flex flex-wrap justify-end gap-2">
                        <Button
                            variant="secondary"
                            loading={checking}
                            disabled={disabled || saving || !selectedRevision || Boolean(validationMessage)}
                            onClick={() => void checkReadiness()}
                        >
                            Check impact
                        </Button>
                        <Button
                            loading={saving}
                            disabled={disabled || checking || readiness?.ready !== true || Boolean(validationMessage)}
                            onClick={() => void assign()}
                        >
                            Assign subscription
                        </Button>
                    </div>
                </div>
            ) : null}

            {checking ? <LoadingState label="Checking usage and downgrade impact..." /> : null}
            {readiness ? (
                <div className={`rounded-lg border p-4 text-sm ${readiness.ready ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950'}`}>
                    <p className="font-semibold">{readiness.ready ? 'Safe to assign' : 'Assignment blocked'}</p>
                    {readiness.removed_modules.length > 0 ? <p className="mt-2">Modules removed by this revision: {readiness.removed_modules.join(', ')}</p> : null}
                    {readiness.blockers.length > 0 ? (
                        <ul className="mt-2 space-y-1">
                            {readiness.blockers.map((blocker) => <li key={blocker.code}>• {blocker.message}</li>)}
                        </ul>
                    ) : null}
                </div>
            ) : null}
        </section>
    );
}

function validatePeriod(status: 'trial' | 'active', startsAt: string, trialEndsAt: string, endsAt: string): string | null {
    const start = startsAt === '' ? new Date() : new Date(startsAt);
    if (Number.isNaN(start.getTime())) return 'Select a valid subscription start date.';
    if (startsAt !== '' && start.getTime() > Date.now() + 60_000) return 'Scheduled future subscriptions are not supported.';

    const trialEnd = trialEndsAt === '' ? null : new Date(trialEndsAt);
    const end = endsAt === '' ? null : new Date(endsAt);
    if (status === 'trial' && trialEnd === null) return 'A trial end date is required.';
    if (trialEnd && (Number.isNaN(trialEnd.getTime()) || trialEnd.getTime() <= start.getTime())) return 'Trial end must be later than the start date.';
    if (end && (Number.isNaN(end.getTime()) || end <= start)) return 'Subscription end must be later than the start date.';
    if (trialEnd && end && end.getTime() < trialEnd.getTime()) return 'Subscription end cannot be earlier than the trial end.';
    return null;
}

function toIsoOrNull(value: string): string | null {
    return value === '' ? null : new Date(value).toISOString();
}

function formatMoney(revision: { price: string; currency?: { code?: string | null } | null; billing_interval: string }): string {
    return `${revision.price} ${revision.currency?.code ?? ''} / ${revision.billing_interval}`.trim();
}

function formatDate(value: string): string {
    return new Date(value).toLocaleString();
}
